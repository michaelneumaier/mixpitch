<?php

namespace App\Events;

use App\Models\BulkDownload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BulkDownloadCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public BulkDownload $bulkDownload)
    {
        //
    }
}
