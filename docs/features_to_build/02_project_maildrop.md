# Project Maildrop Implementation Plan

## Overview

Transform project communication by giving each project a unique email address that automatically processes client emails, stores attachments, parses timecodes, and threads conversations. This eliminates the need for clients to log into portals and creates a seamless email-based workflow.

## UX/UI Implementation

### Project Email Display

**Location**: Project header and client communication sections  
**Current**: Manual email forwarding and separate upload flows  
**New**: Prominent project email display with copy functionality

```blade
{{-- Project email address display --}}
<div class="bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 rounded-lg p-4 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <flux:icon name="mail" class="h-6 w-6 text-indigo-600" />
            <div>
                <h3 class="text-sm font-medium text-indigo-900">Project Email</h3>
                <p class="text-lg font-mono text-indigo-700" x-data="{ copied: false }">
                    {{ $project->maildrop_address }}
                    <flux:button 
                        size="sm" 
                        variant="ghost"
                        @click="navigator.clipboard.writeText('{{ $project->maildrop_address }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="ml-2"
                    >
                        <flux:icon name="clipboard" x-show="!copied" size="sm" />
                        <flux:icon name="check" x-show="copied" size="sm" class="text-green-600" />
                    </flux:button>
                </p>
            </div>
        </div>
        <div class="text-right">
            <flux:text size="sm" class="text-indigo-600">
                Send files & feedback here
            </flux:text>
            <div class="mt-1">
                <flux:badge variant="success" size="sm">
                    Active
                </flux:badge>
            </div>
        </div>
    </div>
    
    <div class="mt-3 pt-3 border-t border-indigo-200">
        <flux:text size="sm" class="text-indigo-600">
            ✉️ Emails automatically saved • 📎 Files extracted • 🕐 Timecodes parsed • 🔗 Threaded conversations
        </flux:text>
    </div>
</div>
```

### Email Thread Display

```blade
{{-- Email conversation display --}}
<div class="space-y-4">
    @foreach($project->emailThreads->groupBy('thread_id') as $threadId => $emails)
        <flux:card class="overflow-hidden">
            <div class="p-4 bg-slate-50 border-b">
                <div class="flex items-center justify-between">
                    <h4 class="font-medium">{{ $emails->first()->subject }}</h4>
                    <flux:text size="sm" class="text-slate-500">
                        {{ $emails->count() }} {{ Str::plural('message', $emails->count()) }}
                    </flux:text>
                </div>
                <flux:text size="sm" class="text-slate-600 mt-1">
                    Thread started {{ $emails->min('received_at')->diffForHumans() }}
                </flux:text>
            </div>
            
            <div class="divide-y divide-slate-100">
                @foreach($emails->sortBy('received_at') as $email)
                    <div class="p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                <div class="h-8 w-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <span class="text-xs font-medium text-indigo-700">
                                        {{ substr($email->sender_name ?: $email->sender_email, 0, 1) }}
                                    </span>
                                </div>
                                <div>
                                    <div class="font-medium text-sm">
                                        {{ $email->sender_name ?: $email->sender_email }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $email->received_at->format('M j, Y g:i A') }}
                                    </div>
                                </div>
                            </div>
                            
                            @if($email->attachments->count() > 0)
                                <flux:badge variant="outline" size="sm">
                                    {{ $email->attachments->count() }} 
                                    {{ Str::plural('file', $email->attachments->count()) }}
                                </flux:badge>
                            @endif
                        </div>
                        
                        <div class="prose prose-sm max-w-none">
                            {!! $email->parsed_content_html !!}
                        </div>
                        
                        {{-- Timecode highlights --}}
                        @if($email->parsed_timecodes->count() > 0)
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach($email->parsed_timecodes as $timecode)
                                    <flux:button 
                                        size="sm" 
                                        variant="outline"
                                        @click="$dispatch('seek-to-time', { time: {{ $timecode['seconds'] }} })"
                                        class="text-xs"
                                    >
                                        {{ $timecode['display'] }}
                                    </flux:button>
                                @endforeach
                            </div>
                        @endif
                        
                        {{-- Attachments --}}
                        @if($email->attachments->count() > 0)
                            <div class="mt-3 grid grid-cols-2 md:grid-cols-3 gap-3">
                                @foreach($email->attachments as $attachment)
                                    <div class="border border-slate-200 rounded-lg p-3">
                                        <div class="flex items-center space-x-2">
                                            <flux:icon name="document" size="sm" class="text-slate-400" />
                                            <div class="min-w-0 flex-1">
                                                <div class="text-sm font-medium truncate">
                                                    {{ $attachment->original_filename }}
                                                </div>
                                                <div class="text-xs text-slate-500">
                                                    {{ $attachment->formatted_size }}
                                                </div>
                                            </div>
                                        </div>
                                        <flux:button 
                                            size="sm" 
                                            variant="ghost" 
                                            class="w-full mt-2"
                                            wire:click="downloadAttachment({{ $attachment->id }})"
                                        >
                                            Download
                                        </flux:button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </flux:card>
    @endforeach
</div>
```

### Project Settings Integration

```blade
{{-- Project settings maildrop configuration --}}
<flux:field>
    <flux:label>Email Settings</flux:label>
    <div class="space-y-3">
        <div class="flex items-center justify-between p-3 border border-slate-200 rounded-lg">
            <div>
                <div class="font-medium">Project Email Address</div>
                <div class="text-sm text-slate-600 font-mono">{{ $project->maildrop_address }}</div>
            </div>
            <flux:button 
                size="sm" 
                variant="outline"
                wire:click="regenerateMaildropAddress"
                wire:confirm="This will change your project email address. Continue?"
            >
                Regenerate
            </flux:button>
        </div>
        
        <div>
            <flux:label for="authorized_senders">Authorized Senders</flux:label>
            <flux:textarea 
                wire:model.defer="authorizedSenders"
                placeholder="client@company.com&#10;another@domain.com"
                rows="3"
            />
            <flux:text size="sm" class="text-slate-500">
                One email address per line. Leave blank to accept from any sender.
            </flux:text>
        </div>
        
        <div class="flex items-center space-x-2">
            <flux:checkbox wire:model.defer="settings.auto_parse_timecodes" />
            <flux:label>Automatically parse timecodes (0:30, 1:45, etc.)</flux:label>
        </div>
        
        <div class="flex items-center space-x-2">
            <flux:checkbox wire:model.defer="settings.auto_extract_attachments" />
            <flux:label>Automatically save email attachments as project files</flux:label>
        </div>
    </div>
</flux:field>
```

## Database Schema

### New Table: `project_maildrops`

```php
Schema::create('project_maildrops', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->onDelete('cascade');
    $table->string('address')->unique(); // e.g., proj-abc123@mail.mixpitch.com
    $table->string('token', 32)->unique(); // Secure token for address generation
    $table->json('authorized_senders')->nullable(); // Array of allowed email addresses
    $table->json('settings')->nullable(); // Auto-parse options, etc.
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_email_at')->nullable();
    $table->integer('total_emails_received')->default(0);
    $table->timestamps();
    
    $table->index(['address', 'is_active']);
    $table->index('token');
});
```

### New Table: `project_emails`

```php
Schema::create('project_emails', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->onDelete('cascade');
    $table->string('thread_id', 100); // Message-ID based threading
    $table->string('message_id')->unique(); // Email Message-ID header
    $table->string('sender_email');
    $table->string('sender_name')->nullable();
    $table->string('subject');
    $table->longText('raw_content'); // Original email content
    $table->longText('parsed_content_text'); // Plain text version
    $table->longText('parsed_content_html')->nullable(); // HTML version
    $table->json('parsed_timecodes')->nullable(); // Extracted timecodes
    $table->json('headers')->nullable(); // Full email headers
    $table->timestamp('received_at');
    $table->boolean('is_verified')->default(false); // DKIM/SPF verification
    $table->string('verification_status')->nullable();
    $table->timestamps();
    
    $table->index(['project_id', 'thread_id', 'received_at']);
    $table->index(['sender_email', 'project_id']);
    $table->index('message_id');
});
```

### New Table: `email_attachments`

```php
Schema::create('email_attachments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_email_id')->constrained()->onDelete('cascade');
    $table->foreignId('project_file_id')->nullable()->constrained()->onDelete('set null');
    $table->string('original_filename');
    $table->string('content_type');
    $table->bigInteger('size_bytes');
    $table->string('storage_path');
    $table->string('checksum', 64); // SHA-256 for deduplication
    $table->boolean('is_processed')->default(false);
    $table->json('metadata')->nullable();
    $table->timestamps();
    
    $table->index(['project_email_id', 'is_processed']);
    $table->index('checksum');
});
```

## Service Layer Architecture

### New Service: `MaildropService`

```php
<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectMaildrop;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MaildropService
{
    public function createMaildropForProject(Project $project): ProjectMaildrop
    {
        $token = Str::random(32);
        $address = $this->generateAddress($project, $token);
        
        return ProjectMaildrop::create([
            'project_id' => $project->id,
            'address' => $address,
            'token' => $token,
            'authorized_senders' => [],
            'settings' => [
                'auto_parse_timecodes' => true,
                'auto_extract_attachments' => true,
                'thread_emails' => true,
            ],
            'is_active' => true,
        ]);
    }
    
    public function regenerateAddress(ProjectMaildrop $maildrop): string
    {
        $newToken = Str::random(32);
        $newAddress = $this->generateAddress($maildrop->project, $newToken);
        
        $maildrop->update([
            'address' => $newAddress,
            'token' => $newToken,
        ]);
        
        Log::info('Maildrop address regenerated', [
            'project_id' => $maildrop->project_id,
            'old_address' => $maildrop->getOriginal('address'),
            'new_address' => $newAddress,
        ]);
        
        return $newAddress;
    }
    
    protected function generateAddress(Project $project, string $token): string
    {
        // Generate secure, unique address: proj-{hash}@mail.mixpitch.com
        $hash = substr(hash('sha256', $project->id . $token . config('app.key')), 0, 12);
        return "proj-{$hash}@" . config('maildrop.domain', 'mail.mixpitch.com');
    }
    
    public function isAuthorizedSender(ProjectMaildrop $maildrop, string $senderEmail): bool
    {
        $authorizedSenders = $maildrop->authorized_senders ?? [];
        
        // If no specific senders configured, allow all
        if (empty($authorizedSenders)) {
            return true;
        }
        
        // Check exact matches and domain matches
        foreach ($authorizedSenders as $authorized) {
            if (strcasecmp($senderEmail, $authorized) === 0) {
                return true;
            }
            
            // Check domain wildcards (e.g., *@company.com)
            if (str_starts_with($authorized, '*@')) {
                $domain = substr($authorized, 2);
                if (str_ends_with(strtolower($senderEmail), strtolower('@' . $domain))) {
                    return true;
                }
            }
        }
        
        return false;
    }
}
```

### New Service: `EmailProcessingService`

```php
<?php

namespace App\Services;

use App\Models\ProjectEmail;
use App\Models\ProjectMaildrop;
use App\Models\EmailAttachment;
use App\Jobs\ProcessEmailAttachment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmailProcessingService
{
    protected FileManagementService $fileService;
    protected EmailVerificationService $verificationService;
    
    public function __construct(
        FileManagementService $fileService,
        EmailVerificationService $verificationService
    ) {
        $this->fileService = $fileService;
        $this->verificationService = $verificationService;
    }
    
    public function processInboundEmail(array $emailData): ?ProjectEmail
    {
        // Find target maildrop
        $maildrop = ProjectMaildrop::where('address', $emailData['to'])
            ->where('is_active', true)
            ->first();
            
        if (!$maildrop) {
            Log::warning('Email received for unknown maildrop', [
                'to' => $emailData['to'],
                'from' => $emailData['from']
            ]);
            return null;
        }
        
        // Verify sender authorization
        if (!app(MaildropService::class)->isAuthorizedSender($maildrop, $emailData['from'])) {
            Log::warning('Unauthorized sender blocked', [
                'maildrop' => $maildrop->address,
                'sender' => $emailData['from'],
                'project_id' => $maildrop->project_id
            ]);
            return null;
        }
        
        // Verify email authenticity
        $verification = $this->verificationService->verifyEmail($emailData);
        
        // Parse email content
        $parsedContent = $this->parseEmailContent($emailData['body']);
        $timecodes = $this->extractTimecodes($parsedContent['text']);
        
        // Determine thread ID
        $threadId = $this->determineThreadId($emailData, $maildrop);
        
        // Create email record
        $email = ProjectEmail::create([
            'project_id' => $maildrop->project_id,
            'thread_id' => $threadId,
            'message_id' => $emailData['message_id'],
            'sender_email' => $emailData['from'],
            'sender_name' => $emailData['from_name'] ?? null,
            'subject' => $emailData['subject'],
            'raw_content' => $emailData['raw'],
            'parsed_content_text' => $parsedContent['text'],
            'parsed_content_html' => $parsedContent['html'],
            'parsed_timecodes' => $timecodes,
            'headers' => $emailData['headers'],
            'received_at' => now(),
            'is_verified' => $verification['verified'],
            'verification_status' => $verification['status'],
        ]);
        
        // Process attachments
        if (!empty($emailData['attachments'])) {
            $this->processAttachments($email, $emailData['attachments']);
        }
        
        // Update maildrop stats
        $maildrop->increment('total_emails_received');
        $maildrop->update(['last_email_at' => now()]);
        
        // Dispatch notification events
        event(new \App\Events\ProjectEmailReceived($email));
        
        return $email;
    }
    
    protected function parseEmailContent(string $body): array
    {
        // Convert HTML to text and preserve HTML version
        $html = $this->extractHtmlContent($body);
        $text = $this->convertHtmlToText($html ?: $body);
        
        return [
            'text' => $text,
            'html' => $html ? $this->sanitizeHtml($html) : null,
        ];
    }
    
    protected function extractTimecodes(string $text): array
    {
        $timecodes = [];
        
        // Pattern matches: 0:30, 1:45, 12:34, 1:23:45, etc.
        $pattern = '/\b(\d{1,2}):(\d{2})(?::(\d{2}))?\b/';
        
        preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
        
        foreach ($matches[0] as $index => $match) {
            $timeString = $match[0];
            $position = $match[1];
            
            $minutes = (int) $matches[1][$index][0];
            $seconds = (int) $matches[2][$index][0];
            $hours = isset($matches[3][$index][0]) ? (int) $matches[3][$index][0] : 0;
            
            $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;
            
            // Only include reasonable timecodes (not dates like 12:34)
            if ($totalSeconds <= 7200) { // Max 2 hours
                $timecodes[] = [
                    'display' => $timeString,
                    'seconds' => $totalSeconds,
                    'position' => $position,
                ];
            }
        }
        
        return $timecodes;
    }
    
    protected function determineThreadId(array $emailData, ProjectMaildrop $maildrop): string
    {
        // Try to find existing thread based on References or In-Reply-To headers
        $references = $emailData['headers']['references'] ?? [];
        $inReplyTo = $emailData['headers']['in-reply-to'] ?? null;
        
        if ($inReplyTo) {
            $existingEmail = ProjectEmail::where('project_id', $maildrop->project_id)
                ->where('message_id', $inReplyTo)
                ->first();
                
            if ($existingEmail) {
                return $existingEmail->thread_id;
            }
        }
        
        foreach ($references as $ref) {
            $existingEmail = ProjectEmail::where('project_id', $maildrop->project_id)
                ->where('message_id', $ref)
                ->first();
                
            if ($existingEmail) {
                return $existingEmail->thread_id;
            }
        }
        
        // Create new thread ID based on subject and date
        $normalizedSubject = strtolower(preg_replace('/^(re:|fwd?:)\s*/i', '', $emailData['subject']));
        return 'thread_' . hash('sha256', $maildrop->project_id . $normalizedSubject . date('Y-m-d'));
    }
    
    protected function processAttachments(ProjectEmail $email, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $checksum = hash('sha256', $attachment['content']);
            
            // Check for duplicates
            $existing = EmailAttachment::where('checksum', $checksum)
                ->where('project_email_id', $email->id)
                ->first();
                
            if ($existing) {
                continue;
            }
            
            // Store attachment temporarily
            $tempPath = storage_path('app/temp/email_attachments/');
            if (!is_dir($tempPath)) {
                mkdir($tempPath, 0755, true);
            }
            
            $filename = Str::uuid() . '_' . $attachment['filename'];
            $fullPath = $tempPath . $filename;
            file_put_contents($fullPath, $attachment['content']);
            
            // Create attachment record
            $emailAttachment = EmailAttachment::create([
                'project_email_id' => $email->id,
                'original_filename' => $attachment['filename'],
                'content_type' => $attachment['content_type'],
                'size_bytes' => strlen($attachment['content']),
                'storage_path' => $fullPath,
                'checksum' => $checksum,
                'is_processed' => false,
            ]);
            
            // Queue for processing if it's a file type we handle
            if ($this->shouldProcessAttachment($attachment['content_type'])) {
                ProcessEmailAttachment::dispatch($emailAttachment);
            }
        }
    }
    
    protected function shouldProcessAttachment(string $contentType): bool
    {
        $processableTypes = [
            'audio/mpeg',
            'audio/wav',
            'audio/flac',
            'audio/aiff',
            'application/pdf',
            'image/jpeg',
            'image/png',
        ];
        
        return in_array($contentType, $processableTypes);
    }
}
```

## Email Infrastructure Integration

### Inbound Email Webhook Handler

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmailProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailWebhookController extends Controller
{
    public function handleInbound(Request $request, EmailProcessingService $processor)
    {
        // This endpoint receives webhooks from email service (Postmark, Mailgun, etc.)
        
        try {
            // Verify webhook authenticity
            $this->verifyWebhookSignature($request);
            
            // Parse webhook payload (format depends on email service)
            $emailData = $this->parseWebhookPayload($request->all());
            
            // Process the email
            $result = $processor->processInboundEmail($emailData);
            
            if ($result) {
                Log::info('Email processed successfully', [
                    'email_id' => $result->id,
                    'project_id' => $result->project_id,
                    'sender' => $result->sender_email,
                ]);
                
                return response()->json(['status' => 'processed', 'email_id' => $result->id]);
            } else {
                return response()->json(['status' => 'ignored']);
            }
            
        } catch (\Exception $e) {
            Log::error('Email webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);
            
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }
    
    protected function verifyWebhookSignature(Request $request): void
    {
        // Implementation depends on email service provider
        // Postmark, Mailgun, SendGrid all have different verification methods
        
        $signature = $request->header('X-Webhook-Signature');
        $expectedSignature = hash_hmac('sha256', $request->getContent(), config('mail.webhook_secret'));
        
        if (!hash_equals($expectedSignature, $signature)) {
            throw new \Exception('Invalid webhook signature');
        }
    }
    
    protected function parseWebhookPayload(array $payload): array
    {
        // Parse based on email service format
        // This example assumes Postmark format
        
        return [
            'to' => $payload['ToFull'][0]['Email'] ?? $payload['To'],
            'from' => $payload['FromFull']['Email'] ?? $payload['From'],
            'from_name' => $payload['FromFull']['Name'] ?? null,
            'subject' => $payload['Subject'],
            'message_id' => $payload['MessageID'],
            'body' => $payload['HtmlBody'] ?: $payload['TextBody'],
            'headers' => $payload['Headers'] ?? [],
            'attachments' => $this->parseAttachments($payload['Attachments'] ?? []),
            'raw' => $payload['RawEmail'] ?? '',
        ];
    }
    
    protected function parseAttachments(array $attachments): array
    {
        $processed = [];
        
        foreach ($attachments as $attachment) {
            $processed[] = [
                'filename' => $attachment['Name'],
                'content_type' => $attachment['ContentType'],
                'content' => base64_decode($attachment['Content']),
            ];
        }
        
        return $processed;
    }
}
```

### Queue Job for Attachment Processing

```php
<?php

namespace App\Jobs;

use App\Models\EmailAttachment;
use App\Services\FileManagementService;
use App\Services\FileSecurityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessEmailAttachment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $timeout = 300;
    
    public function __construct(
        protected EmailAttachment $attachment
    ) {}
    
    public function handle(
        FileManagementService $fileService,
        FileSecurityService $securityService
    ): void {
        try {
            // Security scan
            $content = file_get_contents($this->attachment->storage_path);
            $securityService->scanContent($content, $this->attachment->content_type);
            
            // Get maildrop settings
            $email = $this->attachment->projectEmail;
            $maildrop = $email->project->maildrop;
            
            if ($maildrop->settings['auto_extract_attachments'] ?? true) {
                // Create ProjectFile from attachment
                $projectFile = $fileService->storeProjectFile(
                    $email->project,
                    $this->attachment->storage_path,
                    $this->attachment->original_filename,
                    null, // No specific user - from email
                    [
                        'import_source' => 'email_attachment',
                        'source_checksum' => $this->attachment->checksum,
                        'email_id' => $email->id,
                    ]
                );
                
                // Link attachment to project file
                $this->attachment->update([
                    'project_file_id' => $projectFile->id,
                    'is_processed' => true,
                ]);
                
                // Dispatch event
                event(new \App\Events\EmailAttachmentProcessed($this->attachment, $projectFile));
            }
            
        } catch (\Exception $e) {
            Log::error('Email attachment processing failed', [
                'attachment_id' => $this->attachment->id,
                'error' => $e->getMessage(),
            ]);
            
            $this->attachment->update([
                'metadata->error' => $e->getMessage(),
                'is_processed' => true,
            ]);
        } finally {
            // Clean up temporary file
            if (file_exists($this->attachment->storage_path)) {
                @unlink($this->attachment->storage_path);
            }
        }
    }
}
```

## Livewire Components

### Email Thread Manager

```php
<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\ProjectEmail;
use App\Models\EmailAttachment;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Storage;

class EmailThreadManager extends Component
{
    public Project $project;
    public $groupedEmails = [];
    
    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadEmails();
    }
    
    #[On('echo:project.{project.id},ProjectEmailReceived')]
    public function handleNewEmail($data)
    {
        $this->loadEmails();
        $this->dispatch('email-received', ['email_id' => $data['email_id']]);
    }
    
    public function downloadAttachment(EmailAttachment $attachment)
    {
        $this->authorize('view', $this->project);
        
        if ($attachment->projectEmail->project_id !== $this->project->id) {
            abort(403);
        }
        
        if ($attachment->project_file_id) {
            // Redirect to file download
            return redirect()->route('project.file.download', [
                'project' => $this->project,
                'file' => $attachment->projectFile
            ]);
        } else {
            // Direct attachment download
            return response()->download(
                $attachment->storage_path,
                $attachment->original_filename
            );
        }
    }
    
    protected function loadEmails()
    {
        $emails = ProjectEmail::where('project_id', $this->project->id)
            ->with(['attachments.projectFile'])
            ->orderBy('received_at', 'desc')
            ->get();
            
        $this->groupedEmails = $emails->groupBy('thread_id')->toArray();
    }
    
    public function render()
    {
        return view('livewire.project.email-thread-manager');
    }
}
```

### Maildrop Settings Component

```php
<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Services\MaildropService;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class MaildropSettings extends Component
{
    public Project $project;
    public $authorizedSenders = '';
    public $settings = [
        'auto_parse_timecodes' => true,
        'auto_extract_attachments' => true,
        'thread_emails' => true,
    ];
    
    public function mount(Project $project)
    {
        $this->project = $project;
        
        if ($project->maildrop) {
            $this->authorizedSenders = implode("\n", $project->maildrop->authorized_senders ?? []);
            $this->settings = array_merge($this->settings, $project->maildrop->settings ?? []);
        }
    }
    
    public function regenerateMaildropAddress(MaildropService $service)
    {
        $this->authorize('update', $this->project);
        
        if (!$this->project->maildrop) {
            $this->project->maildrop = $service->createMaildropForProject($this->project);
        } else {
            $service->regenerateAddress($this->project->maildrop);
        }
        
        $this->project->refresh();
        
        Toaster::success('Project email address updated successfully.');
    }
    
    public function updateSettings()
    {
        $this->authorize('update', $this->project);
        
        if (!$this->project->maildrop) {
            $service = app(MaildropService::class);
            $this->project->maildrop = $service->createMaildropForProject($this->project);
        }
        
        $authorizedSenders = array_filter(
            array_map('trim', explode("\n", $this->authorizedSenders))
        );
        
        $this->project->maildrop->update([
            'authorized_senders' => $authorizedSenders,
            'settings' => $this->settings,
        ]);
        
        Toaster::success('Email settings updated successfully.');
    }
    
    public function render()
    {
        return view('livewire.project.maildrop-settings');
    }
}
```

## Security Implementation

### Email Verification Service

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class EmailVerificationService
{
    public function verifyEmail(array $emailData): array
    {
        $results = [
            'verified' => false,
            'status' => 'unverified',
            'checks' => [],
        ];
        
        // DKIM verification
        $dkimResult = $this->verifyDKIM($emailData);
        $results['checks']['dkim'] = $dkimResult;
        
        // SPF verification  
        $spfResult = $this->verifySPF($emailData);
        $results['checks']['spf'] = $spfResult;
        
        // DMARC verification
        $dmarcResult = $this->verifyDMARC($emailData);
        $results['checks']['dmarc'] = $dmarcResult;
        
        // Overall verification status
        if ($dkimResult['pass'] && $spfResult['pass']) {
            $results['verified'] = true;
            $results['status'] = 'verified';
        } elseif ($dkimResult['pass'] || $spfResult['pass']) {
            $results['verified'] = true;
            $results['status'] = 'partially_verified';
        } else {
            $results['status'] = 'failed';
        }
        
        return $results;
    }
    
    protected function verifyDKIM(array $emailData): array
    {
        // Implementation would check DKIM signatures
        // This requires parsing email headers and validating signatures
        
        return [
            'pass' => false,
            'reason' => 'DKIM verification not implemented',
        ];
    }
    
    protected function verifySPF(array $emailData): array
    {
        // Implementation would check SPF records
        // This requires DNS lookups and IP validation
        
        return [
            'pass' => false,
            'reason' => 'SPF verification not implemented',
        ];
    }
    
    protected function verifyDMARC(array $emailData): array
    {
        // Implementation would check DMARC policy
        
        return [
            'pass' => false,
            'reason' => 'DMARC verification not implemented',
        ];
    }
}
```

## Configuration

### Email Service Configuration

```php
// config/maildrop.php

return [
    'domain' => env('MAILDROP_DOMAIN', 'mail.mixpitch.com'),
    
    'webhook' => [
        'secret' => env('MAILDROP_WEBHOOK_SECRET'),
        'url' => env('APP_URL') . '/api/email/inbound',
    ],
    
    'providers' => [
        'postmark' => [
            'api_key' => env('POSTMARK_API_KEY'),
            'webhook_secret' => env('POSTMARK_WEBHOOK_SECRET'),
        ],
        'mailgun' => [
            'api_key' => env('MAILGUN_API_KEY'),
            'webhook_secret' => env('MAILGUN_WEBHOOK_SECRET'),
        ],
    ],
    
    'security' => [
        'require_verification' => env('MAILDROP_REQUIRE_VERIFICATION', false),
        'max_attachment_size' => 25 * 1024 * 1024, // 25MB
        'allowed_attachment_types' => [
            'audio/mpeg',
            'audio/wav',
            'audio/flac',
            'audio/aiff',
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/zip',
        ],
    ],
    
    'limits' => [
        'emails_per_project_per_day' => 100,
        'attachments_per_email' => 10,
        'max_thread_length' => 50,
    ],
];
```

## Testing Strategy

### Feature Tests

```php
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\ProjectMaildrop;
use App\Services\EmailProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaildropTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_creates_maildrop_for_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $service = app(\App\Services\MaildropService::class);
        $maildrop = $service->createMaildropForProject($project);
        
        $this->assertDatabaseHas('project_maildrops', [
            'project_id' => $project->id,
            'is_active' => true,
        ]);
        
        $this->assertStringContains('@mail.mixpitch.com', $maildrop->address);
        $this->assertEquals(32, strlen($maildrop->token));
    }
    
    public function test_processes_valid_email()
    {
        $project = Project::factory()->create();
        $maildrop = \App\Services\MaildropService::createMaildropForProject($project);
        
        $emailData = [
            'to' => $maildrop->address,
            'from' => 'client@example.com',
            'from_name' => 'Test Client',
            'subject' => 'Feedback on Track',
            'message_id' => 'test@example.com',
            'body' => 'Great track! Small feedback at 1:30 and 2:45.',
            'headers' => [],
            'attachments' => [],
            'raw' => 'raw email content',
        ];
        
        $service = app(EmailProcessingService::class);
        $result = $service->processInboundEmail($emailData);
        
        $this->assertNotNull($result);
        $this->assertDatabaseHas('project_emails', [
            'project_id' => $project->id,
            'sender_email' => 'client@example.com',
            'subject' => 'Feedback on Track',
        ]);
        
        // Check timecode parsing
        $this->assertCount(2, $result->parsed_timecodes);
        $this->assertEquals(90, $result->parsed_timecodes[0]['seconds']); // 1:30
        $this->assertEquals(165, $result->parsed_timecodes[1]['seconds']); // 2:45
    }
    
    public function test_blocks_unauthorized_senders()
    {
        $project = Project::factory()->create();
        $maildrop = \App\Services\MaildropService::createMaildropForProject($project);
        
        $maildrop->update([
            'authorized_senders' => ['allowed@example.com'],
        ]);
        
        $emailData = [
            'to' => $maildrop->address,
            'from' => 'unauthorized@example.com',
            'subject' => 'Test',
            'message_id' => 'test@example.com',
            'body' => 'Test message',
            'headers' => [],
            'attachments' => [],
            'raw' => 'raw email content',
        ];
        
        $service = app(EmailProcessingService::class);
        $result = $service->processInboundEmail($emailData);
        
        $this->assertNull($result);
        $this->assertDatabaseMissing('project_emails', [
            'project_id' => $project->id,
            'sender_email' => 'unauthorized@example.com',
        ]);
    }
}
```

## Implementation Steps

### Phase 1: Database & Core Services (Week 1)
1. Create database migrations for maildrop, emails, and attachments
2. Implement `MaildropService` for address generation and management
3. Set up basic `EmailProcessingService` structure
4. Configure email service provider integration (Postmark recommended)

### Phase 2: Email Processing (Week 2)
1. Implement webhook controller for inbound emails
2. Build email parsing and content extraction logic
3. Create timecode parsing functionality
4. Implement attachment processing queue job

### Phase 3: Security & Verification (Week 3)
1. Implement email verification service (DKIM/SPF/DMARC)
2. Add sender authorization and rate limiting
3. Integrate with existing file security scanning
4. Add comprehensive audit logging

### Phase 4: UI Components (Week 4)
1. Create email thread display Livewire component
2. Build maildrop settings management interface
3. Add project email address display to project header
4. Implement attachment download and file linking

### Phase 5: Advanced Features (Week 5)
1. Add email threading and conversation tracking
2. Implement clickable timecode integration with audio player
3. Add email search and filtering capabilities
4. Create email-to-Slack/Teams integration options

## Monitoring & Maintenance

### Email Processing Metrics
- Track email processing success rates
- Monitor attachment processing times
- Alert on webhook failures or high error rates
- Track timecode parsing accuracy

### Security Monitoring
- Monitor for spam or abuse attempts
- Track sender authorization effectiveness
- Alert on verification failures
- Monitor attachment scanning results

This implementation transforms project communication by making email a first-class citizen in the MixPitch workflow, eliminating friction for clients while maintaining security and organization.