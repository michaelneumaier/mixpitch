@props(['user'])

@if($user && $user->hasLinkedReddit())
    <a href="{{ $user->getRedditProfileUrl() }}"
       target="_blank" rel="noopener noreferrer"
       {{ $attributes->merge(['class' => 'inline-flex max-w-full min-w-0 items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-orange-50 text-orange-700 border border-orange-200 hover:bg-orange-100 transition-colors dark:bg-orange-950 dark:text-orange-300 dark:border-orange-900 dark:hover:bg-orange-900']) }}
       title="Verified Reddit account @if($user->reddit_account_created_at) · Redditor since {{ $user->reddit_account_created_at->format('Y') }}@endif">
        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M20 10c0-1.1-.9-2-2-2-.5 0-1 .2-1.4.6-1.5-1-3.4-1.7-5.6-1.8l1-4.4 3.1.7c0 .8.6 1.4 1.4 1.4.8 0 1.4-.6 1.4-1.4S17.2 1.7 16.4 1.7c-.6 0-1.1.3-1.3.8L11.5 1.7c-.1 0-.2 0-.3.1-.1.1-.1.2-.1.3l-1.1 5C7.7 7.1 5.7 7.8 4.2 8.8 3.8 8.4 3.3 8.2 2.8 8.2 1.2 8.2 0 9.4 0 11c0 1.1.7 2 1.7 2.5-.1.3-.1.7-.1 1 0 3.4 4 6.2 8.9 6.2s8.9-2.8 8.9-6.2c0-.3 0-.7-.1-1 .9-.5 1.5-1.4 1.5-2.5H20zm-14.3 1.5c0-.8.7-1.5 1.5-1.5s1.5.7 1.5 1.5-.7 1.5-1.5 1.5-1.5-.7-1.5-1.5zm8.5 4.3c-1 1-2.5 1.4-4.1 1.4-1.5 0-3.1-.5-4.1-1.4-.2-.2-.2-.5 0-.7.2-.2.5-.2.7 0 .8.8 2.1 1.1 3.4 1.1 1.3 0 2.6-.4 3.4-1.1.2-.2.5-.2.7 0 .2.2.2.5 0 .7zm-.3-2.8c-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5 1.5.7 1.5 1.5-.7 1.5-1.5 1.5z"/>
        </svg>
        <span class="truncate">u/{{ $user->reddit_username }}</span>@if($user->reddit_account_created_at)<span class="hidden whitespace-nowrap opacity-70 sm:inline">&nbsp;·&nbsp;since {{ $user->reddit_account_created_at->format('Y') }}</span>@endif
    </a>
@endif
