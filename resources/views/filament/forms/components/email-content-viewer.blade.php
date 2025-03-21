@php
    $state = $getState();
    $id = "email-content-viewer-" . uniqid();
@endphp

<div class="p-4 bg-white rounded-xl shadow-sm">
    <div class="flex justify-between items-center mb-2">
        <h3 class="text-lg font-medium">Email Content Preview</h3>
        <div>
            <button 
                type="button" 
                class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                onclick="document.getElementById('{{ $id }}').classList.toggle('h-96'); document.getElementById('{{ $id }}').classList.toggle('h-screen');"
            >
                Expand/Collapse
            </button>
        </div>
    </div>
    
    <div class="p-1 border border-gray-200 rounded-md">
        <iframe 
            id="{{ $id }}"
            srcdoc="{{ htmlspecialchars($state) }}"
            class="w-full h-96 transition-all duration-200 rounded-md"
            sandbox="allow-same-origin"
        ></iframe>
    </div>
    
    <div class="mt-2 text-xs text-gray-500">
        Note: Some email clients may display this content differently. Email styles have been sanitized for security.
    </div>
</div> 