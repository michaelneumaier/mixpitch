<?php

namespace App\Mail;

use App\Models\Project;
use App\Models\Pitch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientProducerComment extends Mailable
{
    use Queueable, SerializesModels;

    public Project $project;
    public Pitch $pitch;
    public string $comment;
    public string $signedUrl;
    public ?string $clientName;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Project $project,
        Pitch $pitch,
        string $comment,
        string $signedUrl,
        ?string $clientName = null
    ) {
        $this->project = $project;
        $this->pitch = $pitch;
        $this->comment = $comment;
        $this->signedUrl = $signedUrl;
        $this->clientName = $clientName;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject("New Message from {$this->pitch->user->name} - {$this->project->title}")
                    ->markdown('emails.client.producer_comment')
                    ->with([
                        'project' => $this->project,
                        'pitch' => $this->pitch,
                        'comment' => $this->comment,
                        'signedUrl' => $this->signedUrl,
                        'clientName' => $this->clientName,
                    ]);
    }
} 