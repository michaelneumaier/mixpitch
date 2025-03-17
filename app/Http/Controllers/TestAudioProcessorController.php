<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\PitchFile;

class TestAudioProcessorController extends Controller
{
    /**
     * Show the test interface
     */
    public function index()
    {
        // Get some audio files to test with
        $files = PitchFile::whereRaw("LOWER(file_path) LIKE '%.mp3' OR LOWER(file_path) LIKE '%.wav'")->get();
        
        return view('test-audio-processor', [
            'files' => $files,
            'lambdaUrl' => config('services.aws.lambda_audio_processor_url')
        ]);
    }
    
    /**
     * Test the Lambda endpoint with a specific file
     */
    public function testEndpoint(Request $request)
    {
        $fileId = $request->input('file_id');
        $file = PitchFile::findOrFail($fileId);
        
        try {
            // Get the file URL
            $fileUrl = $file->fullFilePath;
            
            if (empty($fileUrl)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Could not generate S3 URL for file'
                ]);
            }
            
            // Properly encode the URL - ensure spaces are encoded as %20
            $encodedFileUrl = str_replace(' ', '%20', $fileUrl);
            
            // Log the attempt
            Log::info('Testing Lambda audio processor', [
                'file_id' => $file->id,
                'file_path' => $file->file_path,
                'file_url' => $fileUrl,
                'encoded_file_url' => $encodedFileUrl,
                'file_exists' => Storage::disk('s3')->exists($file->file_path) ? 'yes' : 'no',
                'file_extension' => pathinfo($file->file_name, PATHINFO_EXTENSION),
                'file_size' => $file->formatted_size
            ]);
            
            // Get the Lambda URL
            $lambdaUrl = config('services.aws.lambda_audio_processor_url');
            
            if (empty($lambdaUrl)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Lambda URL is not configured'
                ]);
            }
            
            // Append /waveform if it's not already there
            if (!str_ends_with($lambdaUrl, '/waveform')) {
                $lambdaUrl .= '/waveform';
            }
            
            Log::info('Calling Lambda function', [
                'lambda_url' => $lambdaUrl,
                'request_data' => [
                    'file_url' => $encodedFileUrl,
                    'peaks_count' => 200
                ]
            ]);
            
            // Send an initial test request to the Lambda endpoint without any data
            // to check if the endpoint is actually reachable
            try {
                $testResponse = Http::timeout(10)
                    ->withOptions([
                        'debug' => true,
                        'verify' => false
                    ])
                    ->get($lambdaUrl);
                
                Log::info('Lambda test connection response', [
                    'status' => $testResponse->status(),
                    'body' => $testResponse->body()
                ]);
            } catch (\Exception $e) {
                Log::error('Lambda test connection failed', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Make the request to Lambda with detailed error reporting
            $response = Http::timeout(60)
                ->withOptions([
                    'debug' => true,
                    'verify' => false // Only for testing
                ])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post($lambdaUrl, [
                    'file_url' => $encodedFileUrl,
                    'peaks_count' => 200
                ]);
            
            // Log the response
            Log::info('Lambda response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);
            
            // Check if the response was successful
            if ($response->successful()) {
                // Get the initial response data
                $responseData = $response->json();
                
                // Handle AWS Lambda response format which wraps results in a body property
                if (isset($responseData['statusCode']) && isset($responseData['body'])) {
                    // Handle string body that needs to be decoded
                    if (is_string($responseData['body'])) {
                        try {
                            // Try to decode the body
                            $data = json_decode($responseData['body'], true);
                            
                            // If json_decode returns null but the body isn't empty, it might be escaped
                            if ($data === null && !empty($responseData['body'])) {
                                // Try to remove escaped quotes and decode again
                                $cleanBody = trim($responseData['body'], '"');
                                $cleanBody = str_replace('\"', '"', $cleanBody);
                                $data = json_decode($cleanBody, true);
                                
                                // If still null, use the body directly
                                if ($data === null) {
                                    $data = ['message' => $responseData['body']];
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning('Error decoding Lambda response body', [
                                'error' => $e->getMessage(),
                                'body' => $responseData['body']
                            ]);
                            $data = ['message' => $responseData['body']];
                        }
                    } else {
                        // Body is already decoded
                        $data = $responseData['body'];
                    }
                } else {
                    // Response is already the data we need
                    $data = $responseData;
                }
                
                // Check for error messages in the data
                if (isset($data['error']) || (isset($data['message']) && $responseData['statusCode'] != 200)) {
                    $errorMessage = $data['error'] ?? $data['message'] ?? 'Unknown error';
                    
                    return response()->json([
                        'success' => false,
                        'error' => 'Lambda function returned an error: ' . $errorMessage,
                        'response' => $response->body(),
                        'parsed_data' => $data,
                        'original_response' => $responseData
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'data' => $data,
                    'original_response' => $responseData,
                    'visualize_data' => [
                        'duration' => $data['duration'] ?? 0,
                        'peaks' => $data['peaks'] ?? []
                    ]
                ]);
            }
            
            // Full error details for debugging
            return response()->json([
                'success' => false,
                'error' => 'Lambda function returned error: ' . $response->status(),
                'response' => $response->body(),
                'request_data' => [
                    'lambda_url' => $lambdaUrl,
                    'file_url' => $fileUrl,
                    'peaks_count' => 200
                ],
                'file_info' => [
                    'id' => $file->id,
                    'path' => $file->file_path,
                    'name' => $file->file_name,
                    'size' => $file->formatted_size,
                    'exists_in_s3' => Storage::disk('s3')->exists($file->file_path) ? 'yes' : 'no'
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error testing Lambda function', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Test uploading a file directly
     */
    public function uploadTest(Request $request)
    {
        try {
            $request->validate([
                'audio_file' => 'required|file|mimes:mp3,wav|max:102400',
            ]);
            
            // Store the file in S3
            $file = $request->file('audio_file');
            $path = $file->store('test-uploads', 's3');
            
            // Generate a public URL
            $url = Storage::disk('s3')->url($path);
            
            // Properly encode the URL - ensure spaces are encoded as %20
            $encodedUrl = str_replace(' ', '%20', $url);
            
            // Log the upload
            Log::info('File uploaded for testing', [
                'path' => $path,
                'url' => $url,
                'encoded_url' => $encodedUrl,
                'file_exists' => Storage::disk('s3')->exists($path) ? 'yes' : 'no',
                'file_size' => Storage::disk('s3')->size($path),
                'file_extension' => pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION),
                'file_mime' => $file->getMimeType()
            ]);
            
            // Get the Lambda URL
            $lambdaUrl = config('services.aws.lambda_audio_processor_url');
            
            if (empty($lambdaUrl)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Lambda URL is not configured'
                ]);
            }
            
            // Append /waveform if it's not already there
            if (!str_ends_with($lambdaUrl, '/waveform')) {
                $lambdaUrl .= '/waveform';
            }
            
            // Test the URL connectivity first
            try {
                $testResponse = Http::timeout(10)
                    ->withOptions([
                        'debug' => true,
                        'verify' => false
                    ])
                    ->get($lambdaUrl);
                
                Log::info('Lambda test connection response for upload', [
                    'status' => $testResponse->status(),
                    'body' => $testResponse->body()
                ]);
            } catch (\Exception $e) {
                Log::error('Lambda upload test connection failed', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Make the request to Lambda
            $response = Http::timeout(60)
                ->withOptions([
                    'debug' => true,
                    'verify' => false // Only for testing
                ])
                ->post($lambdaUrl, [
                    'file_url' => $encodedUrl,
                    'peaks_count' => 200
                ]);
            
            // Log the response
            Log::info('Lambda response for uploaded file', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);
            
            // Check if the response was successful
            if ($response->successful()) {
                $data = $response->json();
                
                return response()->json([
                    'success' => true,
                    'file_url' => $url,
                    'data' => $data,
                    'visualize_data' => [
                        'duration' => $data['duration'] ?? 0,
                        'peaks' => $data['peaks'] ?? []
                    ]
                ]);
            }
            
            // Detailed error for debugging
            return response()->json([
                'success' => false,
                'error' => 'Lambda function returned error: ' . $response->status(),
                'response' => $response->body(),
                'request_data' => [
                    'lambda_url' => $lambdaUrl,
                    'file_url' => $url,
                    'peaks_count' => 200
                ],
                'file_info' => [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'exists_in_s3' => Storage::disk('s3')->exists($path) ? 'yes' : 'no',
                    'mime' => $file->getMimeType()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in upload test', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ]);
        }
    }
} 