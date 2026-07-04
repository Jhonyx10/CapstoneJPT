<?php

namespace App\Events;

use App\Models\RepairJob;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RepairJobStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $repairJob;

    public function __construct(RepairJob $repairJob)
    {
        // Fully load relations needed by the React Native details tracker
        $this->repairJob = $repairJob->load(['invoice', 'vehicle']);
    }

    public function broadcastOn(): array
    {
        // Broadcast on a public channel specific to this ticket id
        return [
            new Channel('repairs.' . $this->repairJob->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'status.changed';
    }
}