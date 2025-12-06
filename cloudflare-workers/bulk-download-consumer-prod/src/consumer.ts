/**
 * Cloudflare Worker: Bulk Download Consumer
 *
 * Consumes queue messages from Laravel, creates ZIP archives from R2 files,
 * stores them in temp location, and notifies Laravel via webhook.
 */

import { Zip, ZipPassThrough } from 'fflate';

interface Env {
	R2_BUCKET: R2Bucket;
	CALLBACK_URL: string;
	CALLBACK_SECRET: string;
}

interface UploadedPart {
	partNumber: number;
	etag: string;
}

interface QueueMessage {
	archive_id: string;
	files: Array<{
		storage_path: string;
		filename: string;
		size: number;
	}>;
	archive_name: string;
	callback_url: string;
	callback_secret: string;
}

/**
 * Concatenate multiple Uint8Arrays into one
 */
function concatUint8Arrays(arrays: Uint8Array[]): Uint8Array {
	const totalLength = arrays.reduce((sum, arr) => sum + arr.length, 0);
	const result = new Uint8Array(totalLength);
	let offset = 0;
	for (const arr of arrays) {
		result.set(arr, offset);
		offset += arr.length;
	}
	return result;
}

/**
 * Initialize R2 multipart upload
 */
async function initMultipartUpload(bucket: R2Bucket, key: string): Promise<string> {
	const upload = await bucket.createMultipartUpload(key);
	return upload.uploadId;
}

/**
 * Upload a single part to R2
 */
async function uploadPart(
	bucket: R2Bucket,
	key: string,
	uploadId: string,
	partNumber: number,
	data: Uint8Array
): Promise<string> {
	const upload = bucket.resumeMultipartUpload(key, uploadId);
	const part = await upload.uploadPart(partNumber, data);
	return part.etag;
}

/**
 * Complete multipart upload
 */
async function completeMultipartUpload(
	bucket: R2Bucket,
	key: string,
	uploadId: string,
	parts: UploadedPart[]
): Promise<void> {
	const upload = bucket.resumeMultipartUpload(key, uploadId);
	await upload.complete(parts);
}

/**
 * Abort incomplete multipart upload (cleanup)
 */
async function abortMultipartUpload(
	bucket: R2Bucket,
	key: string,
	uploadId: string
): Promise<void> {
	const upload = bucket.resumeMultipartUpload(key, uploadId);
	await upload.abort();
}

export default {
	async queue(batch: MessageBatch<QueueMessage>, env: Env): Promise<void> {
		for (const message of batch.messages) {
			try {
				console.log('Processing bulk download:', message.body.archive_id);
				await processArchive(message.body, env);
				message.ack();
			} catch (error) {
				console.error('Failed to process archive:', error);

				// Retry the message
				message.retry();
			}
		}
	}
};

async function processArchive(msg: QueueMessage, env: Env): Promise<void> {
	const { archive_id, files, archive_name } = msg;
	const storagePath = `temp-archives/${archive_id}.zip`;

	console.log(`Creating archive ${archive_id} with ${files.length} files (streaming mode)`);

	let uploadId: string | null = null;
	let totalSize = 0;

	try {
		// Initialize R2 multipart upload
		uploadId = await initMultipartUpload(env.R2_BUCKET, storagePath);
		console.log(`Multipart upload initialized: ${uploadId}`);

		const parts: UploadedPart[] = [];
		let partNumber = 1;
		let outputBuffer: Uint8Array[] = [];
		let bufferSize = 0;
		const PART_SIZE = 5 * 1024 * 1024; // 5MB fixed part size for R2

		// Promise to handle async operations in ZIP callback
		let uploadPromise = Promise.resolve();

		// Create streaming ZIP with callback for compressed chunks
		const zip = new Zip((err, chunk, final) => {
			if (err) {
				console.error('ZIP compression error:', err);
				throw err;
			}

			// Accumulate chunks
			outputBuffer.push(chunk);
			bufferSize += chunk.length;

			// Upload when buffer reaches exactly PART_SIZE or this is the final chunk
			if (final) {
				// Upload remaining data as final part (can be any size)
				if (bufferSize > 0) {
					const combined = concatUint8Arrays(outputBuffer);
					const currentPartNumber = partNumber;

					console.log(`Uploading part ${currentPartNumber} (${combined.length} bytes, final: true)`);

					uploadPromise = uploadPromise.then(async () => {
						const etag = await uploadPart(
							env.R2_BUCKET,
							storagePath,
							uploadId!,
							currentPartNumber,
							combined
						);
						parts.push({ partNumber: currentPartNumber, etag });
						totalSize += combined.length;
						console.log(`Part ${currentPartNumber} uploaded (etag: ${etag})`);
					});

					partNumber++;
					outputBuffer = [];
					bufferSize = 0;
				}
			} else if (bufferSize >= PART_SIZE) {
				// Upload exactly PART_SIZE bytes (except final part)
				const combined = concatUint8Arrays(outputBuffer);
				const currentPartNumber = partNumber;

				// Extract exactly PART_SIZE bytes
				const partData = combined.slice(0, PART_SIZE);
				// Keep remaining bytes in buffer for next part
				const remaining = combined.slice(PART_SIZE);

				console.log(`Uploading part ${currentPartNumber} (${partData.length} bytes, final: false)`);

				uploadPromise = uploadPromise.then(async () => {
					const etag = await uploadPart(
						env.R2_BUCKET,
						storagePath,
						uploadId!,
						currentPartNumber,
						partData
					);
					parts.push({ partNumber: currentPartNumber, etag });
					totalSize += partData.length;
					console.log(`Part ${currentPartNumber} uploaded (etag: ${etag})`);
				});

				partNumber++;
				outputBuffer = remaining.length > 0 ? [remaining] : [];
				bufferSize = remaining.length;
			}
		});

		// Stream each file into the ZIP sequentially
		for (const file of files) {
			console.log(`Processing file: ${file.storage_path}`);

			const r2Object = await env.R2_BUCKET.get(file.storage_path);
			if (!r2Object) {
				throw new Error(`File not found in R2: ${file.storage_path}`);
			}

			// Create a pass-through stream for this file
			const fileStream = new ZipPassThrough(file.filename);
			zip.add(fileStream);

			// Stream R2 object in chunks
			const reader = r2Object.body.getReader();
			let bytesRead = 0;

			while (true) {
				const { done, value } = await reader.read();

				if (done) {
					fileStream.push(new Uint8Array(0), true); // Signal end of file
					console.log(`Finished streaming ${file.filename} (${bytesRead} bytes)`);
					break;
				}

				bytesRead += value.length;
				fileStream.push(value, false);
			}
		}

		// Finalize ZIP (triggers final callback with remaining data)
		zip.end();
		console.log('ZIP stream finalized');

		// Wait for all upload promises to complete
		await uploadPromise;
		console.log(`All ${parts.length} parts uploaded`);

		// Complete multipart upload
		await completeMultipartUpload(env.R2_BUCKET, storagePath, uploadId, parts);
		console.log(`Multipart upload completed: ${storagePath} (${totalSize} bytes)`);

		// Notify Laravel of success
		await notifyLaravel(msg, env, storagePath, totalSize);

	} catch (error) {
		console.error('Error creating archive:', error);

		// Cleanup incomplete upload
		if (uploadId) {
			console.log('Aborting incomplete multipart upload...');
			try {
				await abortMultipartUpload(env.R2_BUCKET, storagePath, uploadId);
				console.log('Multipart upload aborted');
			} catch (abortError) {
				console.error('Failed to abort multipart upload:', abortError);
			}
		}

		// Notify Laravel of failure
		await notifyLaravelFailure(msg, env, error instanceof Error ? error.message : 'Unknown error');

		throw error;
	}
}

async function notifyLaravel(
	msg: QueueMessage,
	env: Env,
	storagePath: string,
	size: number
): Promise<void> {
	const callbackUrl = msg.callback_url || env.CALLBACK_URL;
	const callbackSecret = msg.callback_secret || env.CALLBACK_SECRET;

	const signature = await generateSignature(callbackSecret, msg.archive_id);

	console.log(`Notifying Laravel at: ${callbackUrl}`);

	const response = await fetch(callbackUrl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-Signature': signature,
		},
		body: JSON.stringify({
			archive_id: msg.archive_id,
			status: 'completed',
			storage_path: storagePath,
			size: size,
		}),
	});

	if (!response.ok) {
		throw new Error(`Callback failed: ${response.status} ${await response.text()}`);
	}

	console.log('Laravel notified successfully');
}

async function notifyLaravelFailure(
	msg: QueueMessage,
	env: Env,
	error: string
): Promise<void> {
	const callbackUrl = msg.callback_url || env.CALLBACK_URL;
	const callbackSecret = msg.callback_secret || env.CALLBACK_SECRET;

	const signature = await generateSignature(callbackSecret, msg.archive_id);

	console.log(`Notifying Laravel of failure at: ${callbackUrl}`);

	const response = await fetch(callbackUrl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-Signature': signature,
		},
		body: JSON.stringify({
			archive_id: msg.archive_id,
			status: 'failed',
			error: error,
		}),
	});

	if (!response.ok) {
		console.error(`Failure callback failed: ${response.status} ${await response.text()}`);
	} else {
		console.log('Laravel notified of failure successfully');
	}
}

async function generateSignature(secret: string, data: string): Promise<string> {
	const encoder = new TextEncoder();
	const key = await crypto.subtle.importKey(
		'raw',
		encoder.encode(secret),
		{ name: 'HMAC', hash: 'SHA-256' },
		false,
		['sign']
	);
	const signature = await crypto.subtle.sign('HMAC', key, encoder.encode(data));
	return btoa(String.fromCharCode(...new Uint8Array(signature)));
}
