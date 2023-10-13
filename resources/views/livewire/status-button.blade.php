@php
$iconClass = '';
$colorClass = '';

switch ($status) {
case 'unpublished':
$colorClass = 'bg-gray-400';
$iconClass = 'fa-eye-slash'; // Font Awesome class for eye with slash
break;
case 'open':
$colorClass = 'bg-green-400';
$iconClass = 'fa-check-circle'; // Font Awesome class for check circle
break;
case 'review':
$colorClass = 'bg-yellow-400';
$iconClass = 'fa-eye'; // Font Awesome class for eye
break;
case 'completed':
$colorClass = 'bg-blue-400';
$iconClass = 'fa-check'; // Font Awesome class for check
break;
case 'closed':
$colorClass = 'bg-red-400';
$iconClass = 'fa-times-circle'; // Font Awesome class for times circle
break;
default:
$colorClass = 'bg-gray-400';
$iconClass = 'fa-question-circle'; // Font Awesome class for question circle
break;
}
@endphp

<span
    class="py-1 px-3 {{ $colorClass }} text-black {{ $type === 'top-right' ? 'absolute top-0 right-0 rounded-none rounded-tr-md' : 'rounded-md' }} uppercase cursor-default">
    <i class="{{ $iconClass }}"></i> {{ $status }}
</span>