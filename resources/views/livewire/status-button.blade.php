<button
    class="btn btn-{{ $status === 'open' ? 'success' : ($status === 'review' ? 'primary' : 'danger') }} {{ $type === 'top-right' ? 'status-top-right-rounded-corner position-absolute top-0 end-0 rounded-0' : '' }} btn-sm active text-uppercase pe-none">
    {{ $status }}
</button>