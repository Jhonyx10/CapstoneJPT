<?php

namespace App\Notifications;

use App\Models\RepairJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class DownpaymentReceived extends Notification
{
    use Queueable;

    public $repairJob;
    /**
     * Create a new notification instance.
     */
    public function __construct(RepairJob $repairJob)
    {
        $this->repairJob = $repairJob;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'repair_job_id' => $this->repairJob->id,
            'title' => 'Downpayment Verified!',
            'message' => 'We received your payment for Ticket #' . $this->repairJob->id . '. Your slot is now confirmed.',
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'repair_job_id' => $this->repairJob->id,
            'title' => 'Downpayment Verified!',
            'message' => 'We received your payment for Ticket #' . $this->repairJob->id . '.',
        ]);
    }
}
