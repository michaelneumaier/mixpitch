<div class="flex w-full">
    <div class="block tracking-tight text-xl text-center font-bold grow py-2 px-4 whitespace-nowrap 
       @apply {{ $bgColor }} {{ $textColor }}">
        {{ ucwords(str_replace('_', ' ', $status)) }}
    </div>
</div>