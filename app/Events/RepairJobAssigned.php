<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RepairJobAssigned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $repairJobId,
        public string $vehicleLabel,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'repair.job.assigned';
    }

    public function broadcastWith(): array
    {
        return [
            'repair_job_id' => $this->repairJobId,
            'vehicle_label' => $this->vehicleLabel,
            'title' => 'Repair Job Assigned',
            'message' => 'Technicians have been assigned to your '
                . ($this->vehicleLabel ?: 'vehicle')
                . ' repair (Ticket #' . $this->repairJobId . ').',
        ];
    }
}
