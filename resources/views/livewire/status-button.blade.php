@php
    $colorClass = '';

    switch ($status) {
        case 'unpublished':
            $colorClass = 'bg-gray-400';
            break;
        case 'open':
            $colorClass = 'bg-green-400';
            break;
        case 'review':
            $colorClass = 'bg-yellow-400';
            break;
        case 'completed':
            $colorClass = 'bg-blue-400';
            break;
        case 'closed':
            $colorClass = 'bg-red-400';
            break;
        default:
            $colorClass = 'bg-gray-400';
            break;
    }
@endphp

<span
    class="py-1 px-3 {{ $colorClass }} text-black {{ $type === 'top-right' ? 'absolute top-0 right-0 rounded-none rounded-tr-md' : 'rounded-md' }} uppercase cursor-default">
    {{ $status }}
</span>


{{--<button--}}
{{--    class="btn btn-{{ $status === 'open' ? 'success' : ($status === 'review' ? 'primary' : 'danger') }} {{ $type === 'top-right' ? 'status-top-right-rounded-corner position-absolute top-0 end-0 rounded-0' : '' }} btn-sm active text-uppercase pe-none">--}}
{{--    {{ $status }}--}}
{{--</button>--}}
