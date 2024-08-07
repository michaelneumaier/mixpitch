@php
$iconClass = '';
$colorClass = '';

switch ($status) {
case 'unpublished':
$colorClass = 'bg-gray-400';
$iconClass = 'fa-eye-slash'; // Font Awesome class for eye with slash
break;
case 'open':
$colorClass = 'bg-statusOpen shadow-lightGlow shadow-statusOpen';
$iconClass = 'fa-check-circle'; // Font Awesome class for check circle
break;
case 'review':
$colorClass = 'bg-statusReview shadow-lightGlow shadow-statusReview';
$iconClass = 'fa-eye'; // Font Awesome class for eye
break;
case 'completed':
$colorClass = 'bg-statusComplete text-white shadow-lightGlow shadow-statusComplete';
$iconClass = 'fa-check'; // Font Awesome class for check
break;
case 'closed':
$colorClass = 'bg-statusClosed text-white shadow-lightGlow shadow-statusClosed';
$iconClass = 'fa-times-circle'; // Font Awesome class for times circle
break;
default:
$colorClass = 'bg-gray-400';
$iconClass = 'fa-question-circle'; // Font Awesome class for question circle
break;
}
@endphp

<span
    class="py-1 px-3 {{ $colorClass }} text-black text-sm {{ $type === 'top-right' ? 'absolute top-0 right-0 rounded-none rounded-tr-md' : 'rounded-md' }} uppercase cursor-default">
    <i class="fa-solid {{ $iconClass }}"></i> {{ $status }}
</span>