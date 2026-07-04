<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RepairPaymentReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $repairJobId,
        public string $invoiceStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'repair.payment.received';
    }

    public function broadcastWith(): array
    {
        return [
            'repair_job_id' => $this->repairJobId,
            'invoice_status' => $this->invoiceStatus,
            'title' => 'Downpayment Verified!',
            'message' => 'We received your payment for Ticket #' . $this->repairJobId . '.',
        ];
    }
}
