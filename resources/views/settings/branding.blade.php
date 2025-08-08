@extends('components.layouts.app')

@section('content')
<div class="container mx-auto max-w-6xl py-8">
    <h1 class="text-2xl font-bold mb-6">Branding Settings</h1>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid">
        <!-- Form -->
        <form method="post" action="{{ route('settings.branding.update') }}" enctype="multipart/form-data" class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            @csrf
            @method('PUT')

            <!-- Logo -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Logo</h2>
                <p class="text-sm text-gray-600 mb-4">Upload an image or provide a URL. PNG, JPG, SVG, or WEBP up to 4MB.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload Image</label>
                        <input type="file" name="brand_logo_file" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="block w-full text-sm border border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        @if($user->brand_logo_url)
                        <div class="mt-2">
                            <img src="{{ $user->brand_logo_url }}" alt="Current logo" class="h-16 object-contain rounded border border-gray-200 p-2 bg-white" />
                        </div>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Or use a URL</label>
                        <input type="url" name="brand_logo_url" value="{{ old('brand_logo_url', $user->brand_logo_url) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="https://...">
                        <div class="flex items-center gap-3 mt-3">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300">
                                Remove existing logo
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Colors removed per request -->

            <!-- Invite Email -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Client Invite Email</h2>
                <p class="text-sm text-gray-600 mb-4">Customize the subject and message sent when inviting clients to their portal.</p>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input type="text" name="invite_email_subject" value="{{ old('invite_email_subject', $user->invite_email_subject) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="You're invited to your MixPitch client portal">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea name="invite_email_body" rows="6" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="Hi {{ $user->name }},\n\nHere's your secure link to view and approve your project.\n\nBest,\n{{ $user->name }}">{{ old('invite_email_body', $user->invite_email_body) }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm">Save Branding</button>
                <a href="{{ route('producer.client-management') }}" class="text-gray-600 hover:text-gray-800">Back to Client Management</a>
            </div>
        </form>

        <!-- Live Preview removed per request -->
    </div>
    
</div>
@endsection


