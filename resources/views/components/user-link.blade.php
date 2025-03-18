@props(['user'])

@if($user->username)
<a href="{{ route('profile.username', ['username' => $user->username]) }}"
    class="text-indigo-600 hover:text-indigo-900 hover:underline">
    {{ $user->name }}
</a>
@else
<span>{{ $user->name }}</span>
@endif