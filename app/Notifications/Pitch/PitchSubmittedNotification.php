<?php

namespace App\Notifications\Pitch;

use App\Models\Pitch;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PitchSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $pitch;

    /**
     * Create a new notification instance.
     *
     * @param Pitch $pitch
     */
    public function __construct(Pitch $pitch)
    {
        $this->pitch = $pitch;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // Send via database and mail (or configure based on user preferences)
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable The User model (Project Owner)
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $project = $this->pitch->project;
        $producer = $this->pitch->user;
        $projectName = $project->name;
        $producerName = $producer->name ?? 'A producer';
        // TODO: Update with the correct route to view/manage the pitch
        $pitchUrl = route('projects.manage', $project); // Directing owner to manage project page

        return (new MailMessage)
                    ->subject("New Pitch Submitted for Project: {$projectName}")
                    ->greeting("Hello {$notifiable->name},")
                    ->line("{$producerName} has submitted a pitch for your project '{$projectName}'.")
                    ->line('You can review the pitch application and approve or deny it.')
                    ->action('Review Pitch', $pitchUrl)
                    ->line('Thank you for using MixPitch!');
    }

    /**
     * Get the array representation of the notification (for database storage).
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $project = $this->pitch->project;
        $producer = $this->pitch->user;

        return [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'project_slug' => $project->slug, // Added project slug
            'pitch_id' => $this->pitch->id,
            'pitch_slug' => $this->pitch->slug, // Added pitch slug
            'producer_id' => $producer?->id,
            'producer_name' => $producer?->name ?? 'A producer',
            'message' => ($producer?->name ?? 'A producer') . " submitted a pitch for your project '" . $project->name . "'",
            // Link for the notification center - likely the project manage page for the owner
            'link' => route('projects.manage', $project)
        ];
    }
} 