# Language-Aware Feedback Helpers Implementation Plan

## Overview

Break down language barriers in creative collaboration by providing intelligent translation services, language detection, and localized communication tools. This enables seamless collaboration between musicians and producers regardless of their native languages while maintaining privacy and context sensitivity.

## UX/UI Implementation

### Language Preference Settings

**Location**: User profile and project settings  
**Current**: Single language interface  
**New**: Multi-language support with intelligent translation

```blade
{{-- User language preferences --}}
<flux:card class="p-6">
    <h3 class="text-lg font-semibold mb-4">Language & Communication Preferences</h3>
    
    <div class="space-y-6">
        {{-- Primary language --}}
        <flux:field>
            <flux:label>Primary Language</flux:label>
            <flux:select wire:model.live="user.primary_language">
                <option value="">Select your primary language</option>
                @foreach($supportedLanguages as $code => $language)
                    <option value="{{ $code }}">{{ $language['name'] }} ({{ $language['native'] }})</option>
                @endforeach
            </flux:select>
            <flux:text size="sm" class="text-slate-500">
                This is the language you prefer to communicate in
            </flux:text>
        </flux:field>
        
        {{-- Secondary languages --}}
        <flux:field>
            <flux:label>Additional Languages</flux:label>
            <div class="space-y-2">
                @foreach($user->secondary_languages ?? [] as $index => $lang)
                    <div class="flex items-center space-x-2">
                        <flux:select wire:model="user.secondary_languages.{{ $index }}">
                            @foreach($supportedLanguages as $code => $language)
                                <option value="{{ $code }}">{{ $language['name'] }}</option>
                            @endforeach
                        </flux:select>
                        <flux:button 
                            wire:click="removeSecondaryLanguage({{ $index }})"
                            variant="ghost" 
                            size="sm"
                        >
                            <flux:icon name="x" size="sm" />
                        </flux:button>
                    </div>
                @endforeach
                
                <flux:button 
                    wire:click="addSecondaryLanguage"
                    variant="outline" 
                    size="sm"
                >
                    <flux:icon name="plus" size="sm" />
                    Add Language
                </flux:button>
            </div>
        </flux:field>
        
        {{-- Translation preferences --}}
        <div class="bg-slate-50 rounded-lg p-4">
            <h4 class="font-medium mb-3">Auto-Translation Settings</h4>
            <div class="space-y-3">
                <div class="flex items-center space-x-2">
                    <flux:checkbox wire:model.defer="user.auto_translate_comments" />
                    <flux:label>Automatically translate feedback and comments</flux:label>
                </div>
                
                <div class="flex items-center space-x-2">
                    <flux:checkbox wire:model.defer="user.auto_translate_project_descriptions" />
                    <flux:label>Translate project descriptions when browsing</flux:label>
                </div>
                
                <div class="flex items-center space-x-2">
                    <flux:checkbox wire:model.defer="user.show_original_with_translation" />
                    <flux:label>Always show original text alongside translations</flux:label>
                </div>
                
                <div class="flex items-center space-x-2">
                    <flux:checkbox wire:model.defer="user.privacy_mode_translation" />
                    <flux:label>Enable privacy mode (redact emails/URLs before translation)</flux:label>
                </div>
            </div>
        </div>
        
        {{-- Quality preferences --}}
        <flux:field>
            <flux:label>Translation Quality</flux:label>
            <flux:select wire:model.defer="user.translation_quality">
                <option value="fast">Fast (Basic quality, near-instant)</option>
                <option value="balanced">Balanced (Good quality, ~1-2 seconds)</option>
                <option value="high">High Quality (Best accuracy, ~3-5 seconds)</option>
            </flux:select>
            <flux:text size="sm" class="text-slate-500">
                Higher quality translations take slightly longer but provide better accuracy
            </flux:text>
        </flux:field>
    </div>
</flux:card>
```

### Comment Thread with Translation

```blade
{{-- Enhanced comment thread with translation capabilities --}}
<div class="space-y-4">
    @foreach($comments as $comment)
        <div class="flex space-x-3 {{ $comment->user_id === auth()->id() ? 'flex-row-reverse' : '' }}">
            <div class="flex-shrink-0">
                <div class="h-8 w-8 bg-indigo-100 rounded-full flex items-center justify-center">
                    <span class="text-xs font-medium text-indigo-700">
                        {{ substr($comment->user->name, 0, 1) }}
                    </span>
                </div>
            </div>
            
            <div class="flex-1 min-w-0">
                <div class="bg-white border border-slate-200 rounded-lg p-4 {{ $comment->user_id === auth()->id() ? 'bg-indigo-50 border-indigo-200' : '' }}">
                    {{-- Comment header --}}
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center space-x-2">
                            <span class="font-medium text-sm">{{ $comment->user->name }}</span>
                            @if($comment->detected_language && $comment->detected_language !== auth()->user()->primary_language)
                                <flux:badge variant="outline" size="sm">
                                    {{ strtoupper($comment->detected_language) }}
                                </flux:badge>
                            @endif
                            <span class="text-xs text-slate-500">
                                {{ $comment->created_at->format('M j, g:i A') }}
                            </span>
                        </div>
                        
                        {{-- Translation controls --}}
                        @if($comment->hasTranslation(auth()->user()->primary_language))
                            <div class="flex items-center space-x-1">
                                <flux:button 
                                    size="sm" 
                                    variant="ghost"
                                    wire:click="toggleTranslation({{ $comment->id }})"
                                >
                                    <flux:icon name="language" size="sm" />
                                </flux:button>
                                
                                @if($comment->showTranslation ?? false)
                                    <flux:button 
                                        size="sm" 
                                        variant="ghost"
                                        wire:click="showOriginal({{ $comment->id }})"
                                    >
                                        <flux:icon name="eye" size="sm" />
                                    </flux:button>
                                @endif
                            </div>
                        @elseif($comment->detected_language !== auth()->user()->primary_language)
                            <flux:button 
                                size="sm" 
                                variant="outline"
                                wire:click="translateComment({{ $comment->id }})"
                                wire:loading.attr="disabled"
                                wire:target="translateComment({{ $comment->id }})"
                            >
                                <span wire:loading.remove wire:target="translateComment({{ $comment->id }})">
                                    <flux:icon name="language" size="sm" />
                                    Translate
                                </span>
                                <span wire:loading wire:target="translateComment({{ $comment->id }})">
                                    <flux:icon name="spinner" size="sm" class="animate-spin" />
                                    Translating...
                                </span>
                            </flux:button>
                        @endif
                    </div>
                    
                    {{-- Comment content --}}
                    <div class="space-y-3">
                        @if($comment->showTranslation ?? false)
                            {{-- Translated content --}}
                            <div class="prose prose-sm max-w-none">
                                {!! $comment->getTranslation(auth()->user()->primary_language) !!}
                            </div>
                            
                            {{-- Translation indicator --}}
                            <div class="flex items-center justify-between p-2 bg-blue-50 rounded border border-blue-200">
                                <div class="flex items-center space-x-2 text-xs text-blue-700">
                                    <flux:icon name="language" size="sm" />
                                    <span>
                                        Translated from {{ $comment->detected_language_name }} • 
                                        <button 
                                            wire:click="showOriginal({{ $comment->id }})"
                                            class="underline hover:no-underline"
                                        >
                                            Show original
                                        </button>
                                    </span>
                                </div>
                                
                                <div class="flex items-center space-x-1">
                                    <flux:button 
                                        size="sm" 
                                        variant="ghost"
                                        wire:click="reportTranslation({{ $comment->id }})"
                                        title="Report translation issue"
                                    >
                                        <flux:icon name="flag" size="sm" />
                                    </flux:button>
                                    
                                    <flux:button 
                                        size="sm" 
                                        variant="ghost"
                                        wire:click="improveTranslation({{ $comment->id }})"
                                        title="Suggest improvement"
                                    >
                                        <flux:icon name="pencil" size="sm" />
                                    </flux:button>
                                </div>
                            </div>
                            
                            @if(auth()->user()->show_original_with_translation)
                                {{-- Original content (smaller) --}}
                                <details class="text-sm">
                                    <summary class="cursor-pointer text-slate-600 hover:text-slate-900">
                                        Original text
                                    </summary>
                                    <div class="mt-2 p-2 bg-slate-50 rounded text-slate-700">
                                        {!! $comment->content !!}
                                    </div>
                                </details>
                            @endif
                        @else
                            {{-- Original content --}}
                            <div class="prose prose-sm max-w-none">
                                {!! $comment->content !!}
                            </div>
                        @endif
                        
                        {{-- Timecode highlights --}}
                        @if($comment->parsed_timecodes)
                            <div class="flex flex-wrap gap-2">
                                @foreach($comment->parsed_timecodes as $timecode)
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
                        @if($comment->attachments->count() > 0)
                            <div class="border-t pt-3 mt-3">
                                @foreach($comment->attachments as $attachment)
                                    <div class="flex items-center space-x-2 text-sm">
                                        <flux:icon name="paperclip" size="sm" class="text-slate-400" />
                                        <a href="{{ route('attachment.download', $attachment) }}" 
                                           class="text-indigo-600 hover:text-indigo-800">
                                            {{ $attachment->filename }}
                                        </a>
                                        <span class="text-slate-500">({{ $attachment->formatted_size }})</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
```

### Smart Compose Assistant

```blade
{{-- Intelligent comment composition with language assistance --}}
<div class="bg-white border border-slate-200 rounded-lg p-4">
    <div class="space-y-4">
        {{-- Language detection indicator --}}
        <div class="flex items-center justify-between">
            <h4 class="font-medium">Add Comment</h4>
            <div class="flex items-center space-x-2">
                @if($detectedWritingLanguage && $detectedWritingLanguage !== auth()->user()->primary_language)
                    <div class="flex items-center space-x-1 text-sm text-amber-600">
                        <flux:icon name="exclamation-triangle" size="sm" />
                        <span>Writing in {{ $detectedWritingLanguage }}</span>
                        <flux:button 
                            size="sm" 
                            variant="ghost"
                            wire:click="offerTranslationAssistance"
                        >
                            <flux:icon name="language" size="sm" />
                        </flux:button>
                    </div>
                @endif
                
                <flux:badge 
                    variant="outline" 
                    size="sm"
                    class="text-xs"
                >
                    {{ strtoupper(auth()->user()->primary_language) }}
                </flux:badge>
            </div>
        </div>
        
        {{-- Comment textarea with smart features --}}
        <div class="relative">
            <flux:textarea 
                wire:model.live.debounce.500ms="newComment"
                rows="4"
                placeholder="Share your feedback..."
                class="resize-none"
                x-data="{ 
                    focused: false,
                    showSuggestions: @entangle('showFeedbackSuggestions')
                }"
                @focus="focused = true"
                @blur="focused = false"
            />
            
            {{-- Smart suggestions --}}
            <div x-show="showSuggestions && focused" 
                 x-transition
                 class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg z-10">
                <div class="p-3">
                    <div class="text-xs font-medium text-slate-600 mb-2">Suggested phrases:</div>
                    <div class="space-y-1">
                        @foreach($feedbackSuggestions as $suggestion)
                            <button 
                                wire:click="insertSuggestion('{{ $suggestion['text'] }}')"
                                class="block w-full text-left text-sm p-2 hover:bg-slate-50 rounded"
                            >
                                <div class="font-medium">{{ $suggestion['text'] }}</div>
                                @if($suggestion['translation'])
                                    <div class="text-xs text-slate-500">{{ $suggestion['translation'] }}</div>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Translation assistance panel --}}
        @if($showTranslationAssistance)
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start space-x-3">
                    <flux:icon name="language" class="h-5 w-5 text-blue-600 mt-0.5" />
                    <div class="flex-1">
                        <h5 class="font-medium text-blue-900">Translation Assistance</h5>
                        <p class="text-sm text-blue-700 mt-1">
                            We detected you're writing in {{ $detectedWritingLanguage }}. 
                            Would you like help composing in {{ auth()->user()->primary_language_name }}?
                        </p>
                        
                        <div class="flex items-center space-x-2 mt-3">
                            <flux:button 
                                size="sm" 
                                variant="primary"
                                wire:click="enableCompositionAssistance"
                            >
                                Help me write in {{ auth()->user()->primary_language_name }}
                            </flux:button>
                            
                            <flux:button 
                                size="sm" 
                                variant="outline"
                                wire:click="continueInDetectedLanguage"
                            >
                                Continue in {{ $detectedWritingLanguage }}
                            </flux:button>
                            
                            <flux:button 
                                size="sm" 
                                variant="ghost"
                                wire:click="dismissTranslationAssistance"
                            >
                                Dismiss
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        {{-- Attachment and submission controls --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <flux:button 
                    size="sm" 
                    variant="ghost"
                    wire:click="attachFile"
                >
                    <flux:icon name="paperclip" size="sm" />
                    Attach
                </flux:button>
                
                <flux:button 
                    size="sm" 
                    variant="ghost"
                    wire:click="addTimecode"
                >
                    <flux:icon name="clock" size="sm" />
                    Timecode
                </flux:button>
                
                @if($newComment && $canTranslateForRecipient)
                    <div class="flex items-center space-x-1 text-xs text-slate-600">
                        <flux:checkbox wire:model.defer="autoTranslateComment" />
                        <span>Auto-translate for recipient</span>
                    </div>
                @endif
            </div>
            
            <div class="flex items-center space-x-2">
                @if($compositionMode === 'assisted')
                    <flux:button 
                        size="sm" 
                        variant="outline"
                        wire:click="translateDraft"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove>Preview Translation</span>
                        <span wire:loading>Translating...</span>
                    </flux:button>
                @endif
                
                <flux:button 
                    size="sm" 
                    variant="primary"
                    wire:click="submitComment"
                    :disabled="!newComment.trim()"
                >
                    Post Comment
                </flux:button>
            </div>
        </div>
    </div>
</div>
```

### Project Description Translation

```blade
{{-- Auto-translating project descriptions in project listings --}}
<div class="space-y-4">
    @foreach($projects as $project)
        <flux:card class="p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-semibold">
                        <a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-600">
                            {{ $project->title }}
                        </a>
                    </h3>
                    
                    {{-- Project description with translation --}}
                    <div class="mt-2">
                        @if($project->hasTranslatedDescription(auth()->user()->primary_language))
                            <div class="prose prose-sm text-slate-600">
                                {{ $project->getTranslatedDescription(auth()->user()->primary_language) }}
                            </div>
                            
                            <div class="mt-2 flex items-center space-x-2">
                                <flux:badge variant="outline" size="sm">
                                    Translated from {{ $project->original_language_name }}
                                </flux:badge>
                                
                                <button 
                                    wire:click="toggleOriginalDescription({{ $project->id }})"
                                    class="text-xs text-slate-500 hover:text-slate-700 underline"
                                >
                                    {{ $project->showOriginal ? 'Show translation' : 'Show original' }}
                                </button>
                            </div>
                            
                            @if($project->showOriginal)
                                <div class="mt-2 p-3 bg-slate-50 rounded text-sm text-slate-600">
                                    {{ $project->description }}
                                </div>
                            @endif
                        @else
                            <div class="prose prose-sm text-slate-600">
                                {{ Str::limit($project->description, 200) }}
                            </div>
                            
                            @if($project->detected_language !== auth()->user()->primary_language)
                                <button 
                                    wire:click="translateProjectDescription({{ $project->id }})"
                                    class="mt-2 text-xs text-indigo-600 hover:text-indigo-800 underline"
                                    wire:loading.attr="disabled"
                                    wire:target="translateProjectDescription({{ $project->id }})"
                                >
                                    <span wire:loading.remove wire:target="translateProjectDescription({{ $project->id }})">
                                        Translate to {{ auth()->user()->primary_language_name }}
                                    </span>
                                    <span wire:loading wire:target="translateProjectDescription({{ $project->id }})">
                                        Translating...
                                    </span>
                                </button>
                            @endif
                        @endif
                    </div>
                    
                    {{-- Project metadata --}}
                    <div class="mt-4 flex items-center space-x-4 text-sm text-slate-500">
                        <span>{{ $project->budget_range_formatted }}</span>
                        <span>{{ $project->deadline->format('M j, Y') }}</span>
                        <span>{{ $project->pitches_count }} pitches</span>
                        @if($project->detected_language)
                            <flux:badge variant="outline" size="sm">
                                {{ strtoupper($project->detected_language) }}
                            </flux:badge>
                        @endif
                    </div>
                </div>
                
                <div class="ml-4">
                    <flux:button variant="primary" size="sm">
                        Submit Pitch
                    </flux:button>
                </div>
            </div>
        </flux:card>
    @endforeach
</div>
```

## Database Schema

### New Table: `user_language_preferences`

```php
Schema::create('user_language_preferences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('primary_language', 5); // ISO 639-1 codes
    $table->json('secondary_languages')->nullable(); // Array of language codes
    $table->boolean('auto_translate_comments')->default(true);
    $table->boolean('auto_translate_project_descriptions')->default(true);
    $table->boolean('show_original_with_translation')->default(false);
    $table->boolean('privacy_mode_translation')->default(true);
    $table->enum('translation_quality', ['fast', 'balanced', 'high'])->default('balanced');
    $table->json('custom_phrases')->nullable(); // User-defined translations
    $table->timestamps();
    
    $table->unique('user_id');
    $table->index('primary_language');
});
```

### New Table: `translations`

```php
Schema::create('translations', function (Blueprint $table) {
    $table->id();
    $table->string('translatable_type'); // Model type (Comment, Project, etc.)
    $table->unsignedBigInteger('translatable_id'); // Model ID
    $table->string('source_language', 5); // Original language
    $table->string('target_language', 5); // Translation target
    $table->longText('source_text'); // Original text
    $table->longText('translated_text'); // Translated text
    $table->string('translation_provider'); // deepl, google, aws, etc.
    $table->float('confidence_score')->nullable(); // 0-1 translation confidence
    $table->json('metadata')->nullable(); // Provider-specific metadata
    $table->boolean('is_auto_generated')->default(true);
    $table->boolean('is_verified')->default(false); // Human verified
    $table->foreignId('verified_by')->nullable()->constrained('users');
    $table->timestamp('verified_at')->nullable();
    $table->timestamps();
    
    $table->index(['translatable_type', 'translatable_id']);
    $table->index(['source_language', 'target_language']);
    $table->index(['translation_provider', 'created_at']);
    $table->unique(['translatable_type', 'translatable_id', 'source_language', 'target_language'], 'unique_translation');
});
```

### New Table: `language_detection_cache`

```php
Schema::create('language_detection_cache', function (Blueprint $table) {
    $table->id();
    $table->string('content_hash', 64); // SHA-256 of content
    $table->string('detected_language', 5);
    $table->float('confidence_score'); // 0-1 detection confidence
    $table->string('detection_provider'); // google, aws, azure, etc.
    $table->integer('content_length');
    $table->timestamp('detected_at');
    $table->timestamps();
    
    $table->unique('content_hash');
    $table->index(['detected_language', 'confidence_score']);
    $table->index('detected_at');
});
```

### New Table: `translation_feedback`

```php
Schema::create('translation_feedback', function (Blueprint $table) {
    $table->id();
    $table->foreignId('translation_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->enum('feedback_type', ['quality_issue', 'accuracy_issue', 'context_issue', 'improvement']);
    $table->text('feedback_text')->nullable();
    $table->text('suggested_translation')->nullable();
    $table->json('metadata')->nullable(); // Additional feedback context
    $table->boolean('is_resolved')->default(false);
    $table->timestamp('resolved_at')->nullable();
    $table->timestamps();
    
    $table->index(['translation_id', 'feedback_type']);
    $table->index(['user_id', 'created_at']);
});
```

### Extend existing tables

```php
// Add to comments table
Schema::table('comments', function (Blueprint $table) {
    $table->string('detected_language', 5)->nullable()->after('content');
    $table->float('language_confidence')->nullable()->after('detected_language');
    $table->boolean('is_translated')->default(false)->after('language_confidence');
    $table->json('translation_status')->nullable()->after('is_translated'); // Track translation progress
    
    $table->index(['detected_language', 'is_translated']);
});

// Add to projects table
Schema::table('projects', function (Blueprint $table) {
    $table->string('detected_language', 5)->nullable()->after('description');
    $table->float('language_confidence')->nullable()->after('detected_language');
    $table->boolean('is_translated')->default(false)->after('language_confidence');
    
    $table->index(['detected_language', 'is_translated']);
});
```

## Service Layer Architecture

### New Service: `LanguageDetectionService`

```php
<?php

namespace App\Services;

use App\Models\LanguageDetectionCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LanguageDetectionService
{
    protected array $providers = ['google', 'aws', 'azure'];
    protected string $defaultProvider;
    
    public function __construct()
    {
        $this->defaultProvider = config('translation.detection_provider', 'google');
    }
    
    public function detectLanguage(string $text, bool $useCache = true): array
    {
        // Skip detection for very short text
        if (strlen(trim($text)) < 10) {
            return [
                'language' => 'unknown',
                'confidence' => 0.0,
                'provider' => 'rule_based',
            ];
        }
        
        $contentHash = hash('sha256', $text);
        
        // Check cache first
        if ($useCache) {
            $cached = LanguageDetectionCache::where('content_hash', $contentHash)->first();
            if ($cached) {
                return [
                    'language' => $cached->detected_language,
                    'confidence' => $cached->confidence_score,
                    'provider' => $cached->detection_provider,
                    'cached' => true,
                ];
            }
        }
        
        try {
            $result = $this->detectWithProvider($text, $this->defaultProvider);
            
            // Cache the result
            if ($useCache && $result['confidence'] > 0.5) {
                LanguageDetectionCache::create([
                    'content_hash' => $contentHash,
                    'detected_language' => $result['language'],
                    'confidence_score' => $result['confidence'],
                    'detection_provider' => $result['provider'],
                    'content_length' => strlen($text),
                    'detected_at' => now(),
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Language detection failed', [
                'provider' => $this->defaultProvider,
                'text_length' => strlen($text),
                'error' => $e->getMessage(),
            ]);
            
            return [
                'language' => 'unknown',
                'confidence' => 0.0,
                'provider' => $this->defaultProvider,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    protected function detectWithProvider(string $text, string $provider): array
    {
        return match($provider) {
            'google' => $this->detectWithGoogle($text),
            'aws' => $this->detectWithAWS($text),
            'azure' => $this->detectWithAzure($text),
            default => throw new \InvalidArgumentException("Unknown provider: {$provider}")
        };
    }
    
    protected function detectWithGoogle(string $text): array
    {
        $apiKey = config('services.google.translate_api_key');
        
        if (!$apiKey) {
            throw new \Exception('Google Translate API key not configured');
        }
        
        $response = Http::post('https://translation.googleapis.com/language/translate/v2/detect', [
            'key' => $apiKey,
            'q' => $text,
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('Google detection API failed: ' . $response->body());
        }
        
        $data = $response->json();
        $detection = $data['data']['detections'][0][0];
        
        return [
            'language' => $detection['language'],
            'confidence' => $detection['confidence'],
            'provider' => 'google',
        ];
    }
    
    protected function detectWithAWS(string $text): array
    {
        // AWS Comprehend implementation
        $client = new \Aws\Comprehend\ComprehendClient([
            'region' => config('services.aws.region'),
            'version' => 'latest',
            'credentials' => [
                'key' => config('services.aws.key'),
                'secret' => config('services.aws.secret'),
            ],
        ]);
        
        $result = $client->detectDominantLanguage([
            'Text' => $text,
        ]);
        
        $languages = $result['Languages'];
        $topLanguage = collect($languages)->sortByDesc('Score')->first();
        
        return [
            'language' => $topLanguage['LanguageCode'],
            'confidence' => $topLanguage['Score'],
            'provider' => 'aws',
        ];
    }
    
    protected function detectWithAzure(string $text): array
    {
        $endpoint = config('services.azure.translator_endpoint');
        $key = config('services.azure.translator_key');
        
        if (!$endpoint || !$key) {
            throw new \Exception('Azure Translator credentials not configured');
        }
        
        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $key,
            'Content-Type' => 'application/json',
        ])->post($endpoint . '/detect?api-version=3.0', [
            ['Text' => $text]
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('Azure detection API failed: ' . $response->body());
        }
        
        $detection = $response->json()[0];
        
        return [
            'language' => $detection['language'],
            'confidence' => $detection['score'],
            'provider' => 'azure',
        ];
    }
    
    public function isReliableDetection(array $detection): bool
    {
        return $detection['confidence'] >= 0.7 && $detection['language'] !== 'unknown';
    }
    
    public function getSupportedLanguages(): array
    {
        return Cache::remember('supported_languages', 3600, function () {
            return [
                'en' => ['name' => 'English', 'native' => 'English'],
                'es' => ['name' => 'Spanish', 'native' => 'Español'],
                'fr' => ['name' => 'French', 'native' => 'Français'],
                'de' => ['name' => 'German', 'native' => 'Deutsch'],
                'it' => ['name' => 'Italian', 'native' => 'Italiano'],
                'pt' => ['name' => 'Portuguese', 'native' => 'Português'],
                'ru' => ['name' => 'Russian', 'native' => 'Русский'],
                'ja' => ['name' => 'Japanese', 'native' => '日本語'],
                'ko' => ['name' => 'Korean', 'native' => '한국어'],
                'zh' => ['name' => 'Chinese', 'native' => '中文'],
                'ar' => ['name' => 'Arabic', 'native' => 'العربية'],
                'hi' => ['name' => 'Hindi', 'native' => 'हिन्दी'],
                'th' => ['name' => 'Thai', 'native' => 'ไทย'],
                'vi' => ['name' => 'Vietnamese', 'native' => 'Tiếng Việt'],
                'nl' => ['name' => 'Dutch', 'native' => 'Nederlands'],
                'sv' => ['name' => 'Swedish', 'native' => 'Svenska'],
                'da' => ['name' => 'Danish', 'native' => 'Dansk'],
                'no' => ['name' => 'Norwegian', 'native' => 'Norsk'],
                'fi' => ['name' => 'Finnish', 'native' => 'Suomi'],
                'pl' => ['name' => 'Polish', 'native' => 'Polski'],
                'cs' => ['name' => 'Czech', 'native' => 'Čeština'],
                'hu' => ['name' => 'Hungarian', 'native' => 'Magyar'],
                'tr' => ['name' => 'Turkish', 'native' => 'Türkçe'],
                'he' => ['name' => 'Hebrew', 'native' => 'עברית'],
            ];
        });
    }
}
```

### New Service: `TranslationService`

```php
<?php

namespace App\Services;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TranslationService
{
    protected LanguageDetectionService $languageService;
    protected string $defaultProvider;
    
    public function __construct(LanguageDetectionService $languageService)
    {
        $this->languageService = $languageService;
        $this->defaultProvider = config('translation.provider', 'deepl');
    }
    
    public function translateText(
        string $text,
        string $targetLanguage,
        ?string $sourceLanguage = null,
        string $quality = 'balanced'
    ): array {
        
        // Auto-detect source language if not provided
        if (!$sourceLanguage) {
            $detection = $this->languageService->detectLanguage($text);
            $sourceLanguage = $detection['language'];
            
            if (!$this->languageService->isReliableDetection($detection)) {
                throw new \Exception('Could not reliably detect source language');
            }
        }
        
        // Skip translation if source and target are the same
        if ($sourceLanguage === $targetLanguage) {
            return [
                'translated_text' => $text,
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'confidence' => 1.0,
                'provider' => 'none',
                'skipped' => true,
            ];
        }
        
        // Apply privacy filtering
        $filteredText = $this->applyPrivacyFilters($text);
        
        try {
            $result = $this->translateWithProvider(
                $filteredText,
                $sourceLanguage,
                $targetLanguage,
                $quality
            );
            
            // Restore filtered content
            $result['translated_text'] = $this->restorePrivacyFilters(
                $result['translated_text'],
                $text,
                $filteredText
            );
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Translation failed', [
                'provider' => $this->defaultProvider,
                'source_lang' => $sourceLanguage,
                'target_lang' => $targetLanguage,
                'text_length' => strlen($text),
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    public function translateModel(
        Model $model,
        string $field,
        string $targetLanguage,
        ?User $requestedBy = null
    ): Translation {
        
        $sourceText = $model->{$field};
        
        if (empty($sourceText)) {
            throw new \Exception('Source text is empty');
        }
        
        // Check for existing translation
        $existingTranslation = Translation::where([
            'translatable_type' => get_class($model),
            'translatable_id' => $model->id,
            'target_language' => $targetLanguage,
        ])->first();
        
        if ($existingTranslation) {
            return $existingTranslation;
        }
        
        // Detect source language from model
        $sourceLanguage = $model->detected_language;
        if (!$sourceLanguage) {
            $detection = $this->languageService->detectLanguage($sourceText);
            $sourceLanguage = $detection['language'];
            
            // Update model with detected language
            $model->update([
                'detected_language' => $sourceLanguage,
                'language_confidence' => $detection['confidence'],
            ]);
        }
        
        // Translate the text
        $translationResult = $this->translateText($sourceText, $targetLanguage, $sourceLanguage);
        
        // Create translation record
        $translation = Translation::create([
            'translatable_type' => get_class($model),
            'translatable_id' => $model->id,
            'source_language' => $sourceLanguage,
            'target_language' => $targetLanguage,
            'source_text' => $sourceText,
            'translated_text' => $translationResult['translated_text'],
            'translation_provider' => $translationResult['provider'],
            'confidence_score' => $translationResult['confidence'] ?? null,
            'is_auto_generated' => true,
            'metadata' => [
                'requested_by' => $requestedBy?->id,
                'quality_setting' => $translationResult['quality'] ?? 'balanced',
                'translation_time' => $translationResult['duration'] ?? null,
            ],
        ]);
        
        // Mark model as translated
        $model->update(['is_translated' => true]);
        
        Log::info('Model translated', [
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'source_lang' => $sourceLanguage,
            'target_lang' => $targetLanguage,
            'provider' => $translationResult['provider'],
        ]);
        
        return $translation;
    }
    
    protected function translateWithProvider(
        string $text,
        string $sourceLanguage,
        string $targetLanguage,
        string $quality
    ): array {
        
        return match($this->defaultProvider) {
            'deepl' => $this->translateWithDeepL($text, $sourceLanguage, $targetLanguage, $quality),
            'google' => $this->translateWithGoogle($text, $sourceLanguage, $targetLanguage, $quality),
            'aws' => $this->translateWithAWS($text, $sourceLanguage, $targetLanguage, $quality),
            'azure' => $this->translateWithAzure($text, $sourceLanguage, $targetLanguage, $quality),
            default => throw new \InvalidArgumentException("Unknown provider: {$this->defaultProvider}")
        };
    }
    
    protected function translateWithDeepL(
        string $text,
        string $sourceLanguage,
        string $targetLanguage,
        string $quality
    ): array {
        
        $apiKey = config('services.deepl.api_key');
        $endpoint = config('services.deepl.endpoint', 'https://api-free.deepl.com');
        
        if (!$apiKey) {
            throw new \Exception('DeepL API key not configured');
        }
        
        $formality = $quality === 'high' ? 'prefer_more' : 'default';
        
        $response = Http::asForm()->post($endpoint . '/v2/translate', [
            'auth_key' => $apiKey,
            'text' => $text,
            'source_lang' => strtoupper($sourceLanguage),
            'target_lang' => strtoupper($targetLanguage),
            'formality' => $formality,
            'preserve_formatting' => '1',
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('DeepL translation failed: ' . $response->body());
        }
        
        $data = $response->json();
        $translation = $data['translations'][0];
        
        return [
            'translated_text' => $translation['text'],
            'source_language' => strtolower($translation['detected_source_language']),
            'target_language' => $targetLanguage,
            'provider' => 'deepl',
            'quality' => $quality,
        ];
    }
    
    protected function translateWithGoogle(
        string $text,
        string $sourceLanguage,
        string $targetLanguage,
        string $quality
    ): array {
        
        $apiKey = config('services.google.translate_api_key');
        
        if (!$apiKey) {
            throw new \Exception('Google Translate API key not configured');
        }
        
        $response = Http::post('https://translation.googleapis.com/language/translate/v2', [
            'key' => $apiKey,
            'q' => $text,
            'source' => $sourceLanguage,
            'target' => $targetLanguage,
            'format' => 'text',
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('Google translation failed: ' . $response->body());
        }
        
        $data = $response->json();
        $translation = $data['data']['translations'][0];
        
        return [
            'translated_text' => $translation['translatedText'],
            'source_language' => $sourceLanguage,
            'target_language' => $targetLanguage,
            'provider' => 'google',
            'quality' => $quality,
        ];
    }
    
    protected function applyPrivacyFilters(string $text): string
    {
        // Replace emails with placeholders
        $text = preg_replace(
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/',
            '[EMAIL_PLACEHOLDER]',
            $text
        );
        
        // Replace URLs with placeholders
        $text = preg_replace(
            '/https?:\/\/[^\s<>"{}|\\^`\[\]]+/',
            '[URL_PLACEHOLDER]',
            $text
        );
        
        // Replace phone numbers
        $text = preg_replace(
            '/(\+?1[-.\s]?)?\(?[0-9]{3}\)?[-.\s]?[0-9]{3}[-.\s]?[0-9]{4}/',
            '[PHONE_PLACEHOLDER]',
            $text
        );
        
        return $text;
    }
    
    protected function restorePrivacyFilters(string $translatedText, string $originalText, string $filteredText): string
    {
        // This would need more sophisticated logic to properly restore
        // filtered content in the translated text while preserving context
        
        return $translatedText; // Simplified for now
    }
    
    public function getSupportedLanguagePairs(): array
    {
        return Cache::remember("translation_pairs_{$this->defaultProvider}", 3600, function () {
            // Return supported language pairs for the configured provider
            // This would be fetched from the provider's API or a static configuration
            
            return [
                'deepl' => [
                    'source' => ['en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'ja', 'zh'],
                    'target' => ['en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'ja', 'zh'],
                ],
                'google' => [
                    'source' => ['en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'ja', 'zh', 'ar', 'hi', 'ko'],
                    'target' => ['en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'ja', 'zh', 'ar', 'hi', 'ko'],
                ],
            ];
        });
    }
}
```

### New Service: `SmartFeedbackService`

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Facades\Cache;

class SmartFeedbackService
{
    protected TranslationService $translationService;
    
    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }
    
    public function getFeedbackSuggestions(
        User $user,
        Project $project,
        string $context = 'general'
    ): array {
        
        $cacheKey = "feedback_suggestions_{$user->primary_language}_{$context}";
        
        return Cache::remember($cacheKey, 1800, function () use ($user, $project, $context) {
            $suggestions = $this->getContextualSuggestions($context, $user->primary_language);
            
            // Add project-specific suggestions
            if ($project) {
                $projectSuggestions = $this->getProjectSpecificSuggestions($project, $user->primary_language);
                $suggestions = array_merge($suggestions, $projectSuggestions);
            }
            
            return $suggestions;
        });
    }
    
    protected function getContextualSuggestions(string $context, string $language): array
    {
        $baseSuggestions = match($context) {
            'positive' => [
                'en' => [
                    'This sounds great!',
                    'Love the direction this is going',
                    'Perfect vibe for this track',
                    'Really nice work on the arrangement',
                    'The mix is sounding solid',
                ],
                'es' => [
                    '¡Esto suena genial!',
                    'Me encanta la dirección que está tomando',
                    'Vibra perfecta para esta pista',
                    'Muy buen trabajo en el arreglo',
                    'La mezcla suena sólida',
                ],
                'fr' => [
                    'Ça sonne très bien !',
                    'J\'adore la direction que ça prend',
                    'Parfaite ambiance pour ce morceau',
                    'Très bon travail sur l\'arrangement',
                    'Le mix sonne bien',
                ],
            ],
            'constructive' => [
                'en' => [
                    'Could we try adjusting the levels on',
                    'I think this section might benefit from',
                    'The energy feels a bit low around',
                    'Maybe we could add some movement to',
                    'This part could use more clarity',
                ],
                'es' => [
                    'Podríamos intentar ajustar los niveles en',
                    'Creo que esta sección podría beneficiarse de',
                    'La energía se siente un poco baja alrededor de',
                    'Tal vez podríamos agregar algo de movimiento a',
                    'Esta parte podría usar más claridad',
                ],
                'fr' => [
                    'On pourrait essayer d\'ajuster les niveaux sur',
                    'Je pense que cette section pourrait bénéficier de',
                    'L\'énergie semble un peu faible vers',
                    'On pourrait peut-être ajouter du mouvement à',
                    'Cette partie pourrait avoir plus de clarté',
                ],
            ],
            'technical' => [
                'en' => [
                    'The vocals could use a bit more compression',
                    'There\'s some muddiness in the low mids',
                    'The stereo width feels narrow',
                    'Could we brighten up the top end?',
                    'The drums need more punch',
                ],
                'es' => [
                    'Las voces podrían usar un poco más de compresión',
                    'Hay algo de turbiedad en los medios bajos',
                    'El ancho estéreo se siente estrecho',
                    'Podríamos iluminar el extremo superior?',
                    'Los tambores necesitan más contundencia',
                ],
                'fr' => [
                    'Les voix pourraient utiliser un peu plus de compression',
                    'Il y a de la boue dans les médiums bas',
                    'La largeur stéréo semble étroite',
                    'On pourrait éclaircir le haut du spectre ?',
                    'La batterie a besoin de plus de punch',
                ],
            ],
            default => [
                'en' => [
                    'Thanks for the update',
                    'Looking forward to hearing the next version',
                    'Great progress so far',
                    'Let me know if you have any questions',
                ],
                'es' => [
                    'Gracias por la actualización',
                    'Esperando escuchar la próxima versión',
                    'Gran progreso hasta ahora',
                    'Déjame saber si tienes alguna pregunta',
                ],
                'fr' => [
                    'Merci pour la mise à jour',
                    'J\'ai hâte d\'entendre la prochaine version',
                    'Bon progrès jusqu\'à présent',
                    'Fais-moi savoir si tu as des questions',
                ],
            ],
        };
        
        $suggestions = $baseSuggestions[$language] ?? $baseSuggestions['en'];
        
        return collect($suggestions)->map(function ($text) use ($language) {
            return [
                'text' => $text,
                'language' => $language,
                'category' => 'suggestion',
            ];
        })->toArray();
    }
    
    protected function getProjectSpecificSuggestions(Project $project, string $language): array
    {
        $suggestions = [];
        
        // Genre-specific suggestions
        if ($project->primary_genre) {
            $genreSuggestions = $this->getGenreSpecificSuggestions($project->primary_genre, $language);
            $suggestions = array_merge($suggestions, $genreSuggestions);
        }
        
        // Project type suggestions
        if ($project->projectType) {
            $typeSuggestions = $this->getProjectTypeSpecificSuggestions($project->projectType->name, $language);
            $suggestions = array_merge($suggestions, $typeSuggestions);
        }
        
        return $suggestions;
    }
    
    protected function getGenreSpecificSuggestions(string $genre, string $language): array
    {
        $genreMap = [
            'hip-hop' => [
                'en' => ['The beat hits hard', 'Love the groove', 'Nice flow on this'],
                'es' => ['El beat pega fuerte', 'Me encanta el groove', 'Buen flow en esto'],
            ],
            'electronic' => [
                'en' => ['The drop is massive', 'Great sound design', 'Perfect for the dancefloor'],
                'es' => ['El drop es masivo', 'Gran diseño de sonido', 'Perfecto para la pista'],
            ],
            'rock' => [
                'en' => ['The guitar tone is perfect', 'Great energy', 'Love the power in this'],
                'es' => ['El tono de guitarra es perfecto', 'Gran energía', 'Me encanta la potencia'],
            ],
        ];
        
        $suggestions = $genreMap[strtolower($genre)][$language] ?? [];
        
        return collect($suggestions)->map(function ($text) use ($language) {
            return [
                'text' => $text,
                'language' => $language,
                'category' => 'genre_specific',
            ];
        })->toArray();
    }
    
    protected function getProjectTypeSpecificSuggestions(string $projectType, string $language): array
    {
        $typeMap = [
            'mixing' => [
                'en' => ['The balance is great', 'Nice spatial placement', 'The mix translates well'],
                'es' => ['El balance está genial', 'Buena colocación espacial', 'La mezcla se traduce bien'],
            ],
            'mastering' => [
                'en' => ['Great loudness consistency', 'Nice frequency balance', 'Ready for streaming'],
                'es' => ['Gran consistencia de volumen', 'Buen balance de frecuencias', 'Listo para streaming'],
            ],
        ];
        
        $suggestions = $typeMap[strtolower($projectType)][$language] ?? [];
        
        return collect($suggestions)->map(function ($text) use ($language) {
            return [
                'text' => $text,
                'language' => $language,
                'category' => 'project_type',
            ];
        })->toArray();
    }
    
    public function analyzeCommentSentiment(string $text, string $language): array
    {
        // Simple sentiment analysis
        // In a real implementation, this could use ML services
        
        $positiveWords = $this->getPositiveWords($language);
        $negativeWords = $this->getNegativeWords($language);
        
        $words = str_word_count(strtolower($text), 1);
        $positiveCount = count(array_intersect($words, $positiveWords));
        $negativeCount = count(array_intersect($words, $negativeWords));
        
        $totalWords = count($words);
        $sentiment = 'neutral';
        
        if ($positiveCount > $negativeCount && $positiveCount > 0) {
            $sentiment = 'positive';
        } elseif ($negativeCount > $positiveCount && $negativeCount > 0) {
            $sentiment = 'negative';
        }
        
        return [
            'sentiment' => $sentiment,
            'confidence' => $totalWords > 0 ? max($positiveCount, $negativeCount) / $totalWords : 0,
            'positive_words' => $positiveCount,
            'negative_words' => $negativeCount,
        ];
    }
    
    protected function getPositiveWords(string $language): array
    {
        $words = [
            'en' => ['great', 'good', 'excellent', 'amazing', 'perfect', 'love', 'awesome', 'fantastic'],
            'es' => ['genial', 'bueno', 'excelente', 'increíble', 'perfecto', 'amor', 'impresionante'],
            'fr' => ['génial', 'bon', 'excellent', 'incroyable', 'parfait', 'amour', 'impressionnant'],
        ];
        
        return $words[$language] ?? $words['en'];
    }
    
    protected function getNegativeWords(string $language): array
    {
        $words = [
            'en' => ['bad', 'terrible', 'awful', 'hate', 'wrong', 'problem', 'issue', 'muddy'],
            'es' => ['malo', 'terrible', 'horrible', 'odio', 'mal', 'problema', 'tema', 'turbio'],
            'fr' => ['mauvais', 'terrible', 'affreux', 'déteste', 'mal', 'problème', 'question'],
        ];
        
        return $words[$language] ?? $words['en'];
    }
}
```

## Livewire Components

### Main Language-Aware Comment System

```php
<?php

namespace App\Livewire\Project;

use App\Models\Project;
use App\Models\Comment;
use App\Models\Translation;
use App\Services\TranslationService;
use App\Services\LanguageDetectionService;
use App\Services\SmartFeedbackService;
use Livewire\Component;
use Livewire\Attributes\On;
use Masmerise\Toaster\Toaster;

class LanguageAwareComments extends Component
{
    public Project $project;
    public $comments = [];
    public $newComment = '';
    public $showTranslationAssistance = false;
    public $detectedWritingLanguage = null;
    public $compositionMode = 'normal'; // normal, assisted
    public $autoTranslateComment = false;
    public $showFeedbackSuggestions = false;
    public $feedbackSuggestions = [];
    
    protected $rules = [
        'newComment' => 'required|string|max:2000',
    ];
    
    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadComments();
        $this->loadFeedbackSuggestions();
    }
    
    public function updatedNewComment()
    {
        if (strlen($this->newComment) > 20) {
            $this->detectWritingLanguage();
            $this->updateFeedbackSuggestions();
        }
        
        $this->showFeedbackSuggestions = !empty($this->newComment);
    }
    
    protected function detectWritingLanguage()
    {
        $detectionService = app(LanguageDetectionService::class);
        $result = $detectionService->detectLanguage($this->newComment);
        
        if ($result['confidence'] > 0.6) {
            $this->detectedWritingLanguage = $result['language'];
            
            // Show translation assistance if writing in different language
            if ($this->detectedWritingLanguage !== auth()->user()->primary_language) {
                $this->showTranslationAssistance = true;
            }
        }
    }
    
    protected function updateFeedbackSuggestions()
    {
        $suggestionService = app(SmartFeedbackService::class);
        
        // Analyze sentiment to provide contextual suggestions
        $sentiment = $suggestionService->analyzeCommentSentiment(
            $this->newComment,
            $this->detectedWritingLanguage ?: auth()->user()->primary_language
        );
        
        $context = $sentiment['sentiment'] === 'positive' ? 'positive' : 'constructive';
        
        $this->feedbackSuggestions = $suggestionService->getFeedbackSuggestions(
            auth()->user(),
            $this->project,
            $context
        );
    }
    
    public function translateComment(Comment $comment)
    {
        try {
            $translationService = app(TranslationService::class);
            
            $translation = $translationService->translateModel(
                $comment,
                'content',
                auth()->user()->primary_language,
                auth()->user()
            );
            
            // Mark comment to show translation
            $comment->showTranslation = true;
            
            $this->loadComments();
            
            Toaster::success('Comment translated successfully!');
            
        } catch (\Exception $e) {
            Toaster::error('Translation failed: ' . $e->getMessage());
        }
    }
    
    public function toggleTranslation(Comment $comment)
    {
        $comment->showTranslation = !($comment->showTranslation ?? false);
        $this->loadComments();
    }
    
    public function showOriginal(Comment $comment)
    {
        $comment->showTranslation = false;
        $this->loadComments();
    }
    
    public function submitComment()
    {
        $this->validate();
        
        try {
            // Detect language if not already detected
            if (!$this->detectedWritingLanguage) {
                $this->detectWritingLanguage();
            }
            
            $comment = Comment::create([
                'project_id' => $this->project->id,
                'user_id' => auth()->id(),
                'content' => $this->newComment,
                'detected_language' => $this->detectedWritingLanguage,
                'language_confidence' => 0.8, // Would be from actual detection
            ]);
            
            // Auto-translate for other participants if enabled
            if ($this->autoTranslateComment) {
                $this->autoTranslateForParticipants($comment);
            }
            
            $this->reset(['newComment', 'showTranslationAssistance', 'detectedWritingLanguage']);
            $this->loadComments();
            
            Toaster::success('Comment posted successfully!');
            
        } catch (\Exception $e) {
            Toaster::error('Failed to post comment: ' . $e->getMessage());
        }
    }
    
    protected function autoTranslateForParticipants(Comment $comment)
    {
        $translationService = app(TranslationService::class);
        
        // Get unique languages of project participants
        $participantLanguages = $this->project->participants()
            ->whereNotNull('primary_language')
            ->where('primary_language', '!=', $comment->detected_language)
            ->pluck('primary_language')
            ->unique();
            
        foreach ($participantLanguages as $language) {
            try {
                $translationService->translateModel(
                    $comment,
                    'content',
                    $language
                );
            } catch (\Exception $e) {
                Log::warning('Auto-translation failed', [
                    'comment_id' => $comment->id,
                    'target_language' => $language,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
    
    public function insertSuggestion(string $suggestionText)
    {
        $this->newComment .= ($this->newComment ? ' ' : '') . $suggestionText;
        $this->showFeedbackSuggestions = false;
    }
    
    public function enableCompositionAssistance()
    {
        $this->compositionMode = 'assisted';
        $this->showTranslationAssistance = false;
        
        Toaster::info('Composition assistance enabled. We\'ll help you write in ' . auth()->user()->primary_language_name);
    }
    
    public function continueInDetectedLanguage()
    {
        $this->showTranslationAssistance = false;
        $this->autoTranslateComment = true;
        
        Toaster::info('Auto-translation enabled for other participants.');
    }
    
    public function dismissTranslationAssistance()
    {
        $this->showTranslationAssistance = false;
    }
    
    public function translateDraft()
    {
        if (empty($this->newComment)) {
            return;
        }
        
        try {
            $translationService = app(TranslationService::class);
            
            $result = $translationService->translateText(
                $this->newComment,
                auth()->user()->primary_language,
                $this->detectedWritingLanguage
            );
            
            // Show preview modal or replace text
            $this->dispatch('show-translation-preview', [
                'original' => $this->newComment,
                'translated' => $result['translated_text'],
            ]);
            
        } catch (\Exception $e) {
            Toaster::error('Translation preview failed: ' . $e->getMessage());
        }
    }
    
    public function reportTranslation(Comment $comment)
    {
        // Open modal for reporting translation issues
        $this->dispatch('open-translation-feedback-modal', [
            'comment_id' => $comment->id,
        ]);
    }
    
    protected function loadComments()
    {
        $this->comments = $this->project->comments()
            ->with(['user', 'translations', 'attachments'])
            ->latest()
            ->get()
            ->each(function ($comment) {
                // Check if user has translation preference
                if (auth()->user()->auto_translate_comments && 
                    $comment->detected_language !== auth()->user()->primary_language) {
                    
                    $translation = $comment->translations()
                        ->where('target_language', auth()->user()->primary_language)
                        ->first();
                        
                    if ($translation) {
                        $comment->showTranslation = true;
                    }
                }
            });
    }
    
    protected function loadFeedbackSuggestions()
    {
        $suggestionService = app(SmartFeedbackService::class);
        $this->feedbackSuggestions = $suggestionService->getFeedbackSuggestions(
            auth()->user(),
            $this->project
        );
    }
    
    public function getCanTranslateForRecipientProperty()
    {
        // Check if any project participants have different primary language
        return $this->project->participants()
            ->where('primary_language', '!=', auth()->user()->primary_language)
            ->exists();
    }
    
    public function render()
    {
        return view('livewire.project.language-aware-comments');
    }
}
```

### User Language Preferences Component

```php
<?php

namespace App\Livewire\User;

use App\Models\User;
use App\Services\LanguageDetectionService;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class LanguagePreferences extends Component
{
    public User $user;
    public $secondaryLanguages = [];
    
    protected $rules = [
        'user.primary_language' => 'required|string|size:2',
        'user.auto_translate_comments' => 'boolean',
        'user.auto_translate_project_descriptions' => 'boolean',
        'user.show_original_with_translation' => 'boolean',
        'user.privacy_mode_translation' => 'boolean',
        'user.translation_quality' => 'required|in:fast,balanced,high',
        'secondaryLanguages.*' => 'string|size:2',
    ];
    
    public function mount(User $user)
    {
        $this->user = $user;
        $this->secondaryLanguages = $user->languagePreferences->secondary_languages ?? [];
    }
    
    public function addSecondaryLanguage()
    {
        $this->secondaryLanguages[] = '';
    }
    
    public function removeSecondaryLanguage(int $index)
    {
        unset($this->secondaryLanguages[$index]);
        $this->secondaryLanguages = array_values($this->secondaryLanguages);
    }
    
    public function savePreferences()
    {
        $this->validate();
        
        try {
            // Update user language preferences
            $this->user->languagePreferences()->updateOrCreate(
                ['user_id' => $this->user->id],
                [
                    'primary_language' => $this->user->primary_language,
                    'secondary_languages' => array_filter($this->secondaryLanguages),
                    'auto_translate_comments' => $this->user->auto_translate_comments,
                    'auto_translate_project_descriptions' => $this->user->auto_translate_project_descriptions,
                    'show_original_with_translation' => $this->user->show_original_with_translation,
                    'privacy_mode_translation' => $this->user->privacy_mode_translation,
                    'translation_quality' => $this->user->translation_quality,
                ]
            );
            
            Toaster::success('Language preferences saved successfully!');
            
        } catch (\Exception $e) {
            Toaster::error('Failed to save preferences: ' . $e->getMessage());
        }
    }
    
    public function getSupportedLanguagesProperty()
    {
        $languageService = app(LanguageDetectionService::class);
        return $languageService->getSupportedLanguages();
    }
    
    public function render()
    {
        return view('livewire.user.language-preferences');
    }
}
```

## Testing Strategy

### Feature Tests

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Comment;
use App\Models\Project;
use App\Services\TranslationService;
use App\Services\LanguageDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageAwareFeaturesTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_detects_comment_language()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $detectionService = app(LanguageDetectionService::class);
        
        $result = $detectionService->detectLanguage('This is a test comment in English');
        
        $this->assertEquals('en', $result['language']);
        $this->assertGreaterThan(0.5, $result['confidence']);
    }
    
    public function test_translates_comment()
    {
        $user = User::factory()->create(['primary_language' => 'en']);
        $comment = Comment::factory()->create([
            'content' => 'Esto suena muy bien',
            'detected_language' => 'es',
        ]);
        
        $translationService = app(TranslationService::class);
        
        $translation = $translationService->translateModel(
            $comment,
            'content',
            'en',
            $user
        );
        
        $this->assertDatabaseHas('translations', [
            'translatable_type' => Comment::class,
            'translatable_id' => $comment->id,
            'source_language' => 'es',
            'target_language' => 'en',
        ]);
        
        $this->assertNotEmpty($translation->translated_text);
    }
    
    public function test_applies_privacy_filters()
    {
        $translationService = app(TranslationService::class);
        
        $textWithEmail = 'Contact me at john@example.com for more details';
        $filtered = $this->invokeMethod($translationService, 'applyPrivacyFilters', [$textWithEmail]);
        
        $this->assertStringContains('[EMAIL_PLACEHOLDER]', $filtered);
        $this->assertStringNotContainsString('john@example.com', $filtered);
    }
    
    public function test_caches_language_detection()
    {
        $detectionService = app(LanguageDetectionService::class);
        
        $text = 'This is a test for caching';
        
        // First detection
        $result1 = $detectionService->detectLanguage($text);
        
        // Second detection should use cache
        $result2 = $detectionService->detectLanguage($text);
        
        $this->assertEquals($result1['language'], $result2['language']);
        $this->assertTrue($result2['cached'] ?? false);
    }
    
    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }
}
```

### Livewire Component Tests

```php
<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Project\LanguageAwareComments;
use App\Models\User;
use App\Models\Project;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LanguageAwareCommentsTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_renders_comments_with_translation_buttons()
    {
        $user = User::factory()->create(['primary_language' => 'en']);
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $comment = Comment::factory()->create([
            'project_id' => $project->id,
            'detected_language' => 'es',
            'content' => 'Esto suena muy bien',
        ]);
        
        $this->actingAs($user);
        
        Livewire::test(LanguageAwareComments::class, ['project' => $project])
            ->assertStatus(200)
            ->assertSee('Translate')
            ->assertSee('ES'); // Language badge
    }
    
    public function test_can_translate_comment()
    {
        $user = User::factory()->create(['primary_language' => 'en']);
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $comment = Comment::factory()->create([
            'project_id' => $project->id,
            'detected_language' => 'es',
            'content' => 'Esto suena muy bien',
        ]);
        
        $this->actingAs($user);
        
        Livewire::test(LanguageAwareComments::class, ['project' => $project])
            ->call('translateComment', $comment)
            ->assertNotified();
            
        $this->assertDatabaseHas('translations', [
            'translatable_type' => Comment::class,
            'translatable_id' => $comment->id,
        ]);
    }
    
    public function test_shows_feedback_suggestions()
    {
        $user = User::factory()->create(['primary_language' => 'en']);
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $this->actingAs($user);
        
        Livewire::test(LanguageAwareComments::class, ['project' => $project])
            ->set('newComment', 'This sounds great but could use some work')
            ->assertSet('showFeedbackSuggestions', true);
    }
    
    public function test_detects_writing_language_change()
    {
        $user = User::factory()->create(['primary_language' => 'en']);
        $project = Project::factory()->create(['user_id' => $user->id]);
        
        $this->actingAs($user);
        
        Livewire::test(LanguageAwareComments::class, ['project' => $project])
            ->set('newComment', 'Esto suena muy bien, me gusta mucho')
            ->assertSet('showTranslationAssistance', true);
    }
}
```

## Implementation Steps

### Phase 1: Core Language Services (Week 1)
1. Create database migrations for language preferences and translations
2. Implement `LanguageDetectionService` with multiple provider support
3. Set up basic `TranslationService` with privacy filtering
4. Create language preference management interface

### Phase 2: Translation System (Week 2)
1. Build comprehensive translation caching and optimization
2. Implement comment and project description translation
3. Add translation quality feedback and improvement system
4. Create translation analytics and monitoring

### Phase 3: Smart Feedback System (Week 3)
1. Implement `SmartFeedbackService` with contextual suggestions
2. Add sentiment analysis and language-aware suggestions
3. Create composition assistance and writing helpers
4. Build genre and project-specific feedback templates

### Phase 4: UI Integration (Week 4)
1. Create `LanguageAwareComments` Livewire component
2. Implement auto-translation and preference management
3. Add smart compose assistant with translation preview
4. Style with Flux UI following UX guidelines

### Phase 5: Advanced Features (Week 5)
1. Add real-time language detection during typing
2. Implement collaborative translation improvement
3. Create language analytics and usage reporting
4. Add multi-language project discovery features

## Business Benefits

### For Users
- Seamless collaboration regardless of language barriers
- Improved communication quality through smart suggestions
- Privacy protection during translation
- Faster feedback composition with intelligent assistance

### For Platform
- Expanded global user base through language accessibility
- Higher engagement through better communication
- Reduced support burden from language-related issues
- Valuable language and communication analytics

This implementation creates a truly global collaboration platform that empowers users to communicate effectively regardless of their native language while maintaining privacy and context sensitivity.