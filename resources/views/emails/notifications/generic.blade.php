@component('mail::message')
# {{ $greeting }}

{{ $description }}

@if ($actionUrl)
@component('mail::button', ['url' => $actionUrl, 'color' => ($level ?? 'primary')])
{{ $actionText }}
@endcomponent
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
