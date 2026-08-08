<div>
    @php
        $publicUrl = $this->publicUrl;
        $shareText = "Check out my music project: " . $project->name;
        $twitterUrl = 'https://twitter.com/intent/tweet?text=' . urlencode($shareText) . '&url=' . urlencode($publicUrl);
        $facebookUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($publicUrl);
        $linkedinUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($publicUrl);
        $manualRedditUrl = 'https://reddit.com/submit?title=' . urlencode($shareText) . '&url=' . urlencode($publicUrl);
    @endphp

    <flux:modal name="shareProject" class="md:w-2xl" x-data="{
        copyPublicUrl() {
            navigator.clipboard.writeText(this.$refs.publicUrl.value).then(() => {
                this.$dispatch('toast', { type: 'success', message: 'Project URL copied to clipboard' });
            });
        }
    }">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Share Your Project</flux:heading>
                <flux:subheading>Get your project in front of more producers</flux:subheading>
            </div>

            {{-- ============================================================ --}}
            {{-- SINK 1: Copy Public Link                                     --}}
            {{-- ============================================================ --}}
            <div class="space-y-2" data-testid="share-sink-link">
                <flux:field>
                    <flux:label>Public link</flux:label>
                    <div class="flex gap-2">
                        <flux:input
                            value="{{ $publicUrl }}"
                            readonly
                            x-ref="publicUrl"
                            class="flex-1" />
                        <flux:button
                            variant="outline"
                            x-on:click="copyPublicUrl()"
                            icon="clipboard">
                            Copy
                        </flux:button>
                    </div>
                    <flux:description>Anyone with this link can view the project and submit a pitch.</flux:description>
                </flux:field>
            </div>

            {{-- ============================================================ --}}
            {{-- SINK 2: r/MixPitch                                            --}}
            {{-- ============================================================ --}}
            @if($this->showsRedditSection)
                <div class="rounded-lg border border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-950 p-4 space-y-4"
                     data-testid="share-sink-reddit">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="shrink-0 mt-0.5 w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <flux:heading size="sm" class="text-orange-900 dark:text-orange-100">Post to r/MixPitch</flux:heading>
                                <p class="text-sm text-orange-700 dark:text-orange-300">
                                    Share with the MixPitch subreddit community.
                                </p>
                            </div>
                        </div>
                    </div>

                    @if($project->hasBeenPostedToReddit())
                        {{-- STATE: already posted --}}
                        <div class="rounded-md bg-white/60 dark:bg-black/20 border border-green-200 dark:border-green-800 p-3"
                             data-testid="reddit-state-posted">
                            <div class="flex items-center gap-2 mb-2">
                                <flux:icon.check-circle class="w-4 h-4 text-green-600 dark:text-green-400" />
                                <span class="text-sm font-medium text-green-800 dark:text-green-200">
                                    Posted to r/MixPitch
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <flux:button
                                    href="{{ $project->getRedditUrl() }}"
                                    target="_blank"
                                    variant="outline"
                                    size="sm"
                                    icon="arrow-top-right-on-square">
                                    View on Reddit
                                </flux:button>
                                <flux:button
                                    wire:click="unpostFromReddit"
                                    wire:confirm="Remove this project's post from r/MixPitch? This cannot be undone."
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    class="text-red-600 hover:text-red-700">
                                    Remove from Reddit
                                </flux:button>
                            </div>
                        </div>

                    @elseif($isPostingToReddit)
                        {{-- STATE: posting in flight --}}
                        <div class="rounded-md bg-white/60 dark:bg-black/20 border border-orange-200 dark:border-orange-800 p-3"
                             data-testid="reddit-state-posting">
                            <div class="flex items-center gap-2">
                                <flux:icon.arrow-path class="w-4 h-4 text-orange-600 dark:text-orange-400 animate-spin" />
                                <span class="text-sm font-medium text-orange-800 dark:text-orange-200">
                                    Posting to r/MixPitch&hellip;
                                </span>
                            </div>
                            <p class="text-xs text-orange-700 dark:text-orange-300 mt-1">
                                This usually takes a few seconds. You can close this modal &mdash; we'll notify you when it's live.
                            </p>
                        </div>

                    @elseif($project->is_published)
                        {{-- STATE: ready to post --}}
                        <div class="space-y-3" data-testid="reddit-state-ready">
                            {{-- Preview accordion --}}
                            <div x-data="{ open: false }" class="rounded-md bg-white/60 dark:bg-black/20 border border-orange-200 dark:border-orange-800">
                                <button
                                    type="button"
                                    x-on:click="open = !open"
                                    class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-orange-900 dark:text-orange-100">
                                    <span class="flex items-center gap-2">
                                        <flux:icon.eye class="w-4 h-4" />
                                        Preview post
                                    </span>
                                    <flux:icon.chevron-down class="w-4 h-4 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                                </button>
                                <div x-show="open" x-collapse class="border-t border-orange-200 dark:border-orange-800 px-3 py-3 space-y-3">
                                    <div>
                                        <div class="text-xs font-semibold text-orange-700 dark:text-orange-300 uppercase tracking-wide mb-1">
                                            Title
                                        </div>
                                        <div class="text-sm text-slate-900 dark:text-slate-100 font-medium break-words"
                                             data-testid="reddit-preview-title">
                                            {{ $project->name }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-orange-700 dark:text-orange-300 uppercase tracking-wide mb-1">
                                            Body
                                        </div>
                                        <pre class="text-xs whitespace-pre-wrap break-words max-h-64 overflow-y-auto bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded p-2 text-slate-800 dark:text-slate-200"
                                             data-testid="reddit-preview-body">{{ $this->redditPreviewBody }}</pre>
                                    </div>
                                </div>
                            </div>

                            {{-- Post button --}}
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <div class="text-xs text-orange-700 dark:text-orange-300">
                                    @if($this->ownerHasLinkedReddit)
                                        Posted as <span class="font-medium">u/MixPitch</span>, credited to <span class="font-medium">u/{{ $project->user->reddit_username }}</span>.
                                    @else
                                        Posted as <span class="font-medium">u/MixPitch</span> bot.
                                    @endif
                                </div>
                                <flux:button
                                    wire:click="postToReddit"
                                    variant="primary"
                                    size="sm"
                                    icon="globe-alt">
                                    Post to r/MixPitch
                                </flux:button>
                            </div>

                            {{-- OAuth conversion nudge --}}
                            @if(! $this->ownerHasLinkedReddit)
                                <div class="rounded-md bg-white dark:bg-slate-900 border border-dashed border-orange-300 dark:border-orange-700 p-3"
                                     data-testid="reddit-connect-nudge">
                                    <div class="flex items-start gap-2">
                                        <flux:icon.sparkles class="w-4 h-4 text-orange-600 dark:text-orange-400 mt-0.5 shrink-0" />
                                        <div class="text-xs text-slate-700 dark:text-slate-300">
                                            <strong>Get credited on Reddit.</strong>
                                            <a href="{{ route('account.reddit.connect') }}"
                                               class="text-orange-700 dark:text-orange-300 underline hover:no-underline">Connect your Reddit account</a>
                                            so posts show your username instead of just the bot.
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                    @else
                        {{-- STATE: not yet published --}}
                        <div class="rounded-md bg-white/60 dark:bg-black/20 border border-orange-200 dark:border-orange-800 p-3"
                             data-testid="reddit-state-unpublished">
                            <div class="flex items-center gap-2">
                                <flux:icon.exclamation-triangle class="w-4 h-4 text-orange-600 dark:text-orange-400" />
                                <span class="text-sm text-orange-800 dark:text-orange-200">
                                    Publish your project before posting it to r/MixPitch.
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ============================================================ --}}
            {{-- SINK 3: Social share targets                                  --}}
            {{-- ============================================================ --}}
            <div class="space-y-3" data-testid="share-sink-social">
                <flux:heading size="base">Share elsewhere</flux:heading>
                <div class="grid grid-cols-2 gap-3">
                    <flux:button
                        href="{{ $twitterUrl }}"
                        target="_blank"
                        variant="outline"
                        class="justify-start">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                        Twitter / X
                    </flux:button>

                    <flux:button
                        href="{{ $facebookUrl }}"
                        target="_blank"
                        variant="outline"
                        class="justify-start">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Facebook
                    </flux:button>

                    <flux:button
                        href="{{ $linkedinUrl }}"
                        target="_blank"
                        variant="outline"
                        class="justify-start">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                        LinkedIn
                    </flux:button>

                    <flux:button
                        href="{{ $manualRedditUrl }}"
                        target="_blank"
                        variant="outline"
                        class="justify-start">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/>
                        </svg>
                        Reddit (manual)
                    </flux:button>
                </div>
            </div>

            <div class="flex justify-end">
                <flux:button x-on:click="$flux.modal('shareProject').close()" variant="primary">
                    Done
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
