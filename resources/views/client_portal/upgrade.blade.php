@extends('layouts.app')

@section('title', 'Create Account - ' . $project->title)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-purple-50 dark:from-slate-900 dark:via-purple-900 dark:to-slate-900">
    <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Create Your Account</h1>
                <p class="text-gray-600 dark:text-gray-300">
                    Create an account to manage your projects and access your deliverables anytime.
                </p>
            </div>

            <form method="POST" action="{{ URL::temporarySignedRoute('client.portal.create_account', now()->addHours(1), ['project' => $project->id]) }}">
                @csrf

                <div class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input type="email" id="email" value="{{ $project->client_email }}" disabled
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-50 text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">This email is associated with your project.</p>
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $project->client_name) }}" required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                        <input type="password" id="password" name="password" required minlength="8"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 px-6 rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition-all duration-200">
                        Create Account
                    </button>
                </div>
            </form>

            <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                Already have an account?
                <a href="{{ route('login') }}" class="text-purple-600 hover:text-purple-800 dark:text-purple-400 font-medium">Log in</a>
            </p>
        </div>
    </div>
</div>
@endsection
