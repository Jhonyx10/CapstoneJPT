<?php

namespace App\Services;

use App\Enums\RepairJobStatus;
use App\Enums\VehicleStatus;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RepairJob;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;

class DashboardService
{
    public function getOverview(): array
    {
        return [
            'stats' => $this->getStats(),
            'repair_breakdown' => $this->getRepairBreakdown(),
            'recent_activities' => $this->getRecentActivities(),
        ];
    }

    private function getStats(): array
    {
        return [
            'total_collected' => (float) Payment::where('status', 'successful')->sum('amount_paid'),
            'active_repairs' => RepairJob::whereIn('status', [
                RepairJobStatus::Confirmed->value,
                RepairJobStatus::Assigned->value,
                RepairJobStatus::InProgress->value,
            ])->count(),
            'outstanding_due' => (float) Invoice::sum('amount_due'),
            'vehicles_for_sale' => Vehicle::where('status', VehicleStatus::ForSale->value)->count(),
            'low_stock_items' => Inventory::whereColumn('quantity_in_stock', '<=', 'min_stock_alert')->count(),
            'pending_requests' => RepairJob::where('status', RepairJobStatus::Pending->value)->count(),
            'total_invoices' => Invoice::count(),
            'total_workers' => User::where('role', 'worker')->count(),
        ];
    }

    private function getRepairBreakdown(): array
    {
        $breakdown = [];

        foreach (RepairJobStatus::cases() as $status) {
            $breakdown[$status->value] = RepairJob::where('status', $status->value)->count();
        }

        return $breakdown;
    }

    private function getRecentActivities(): array
    {
        $activities = collect();

        Payment::with('invoice:id,invoice_number')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->each(function (Payment $payment) use ($activities) {
                $activities->push([
                    'id' => 'payment-' . $payment->id,
                    'type' => 'payment',
                    'title' => 'Payment for ' . ($payment->invoice?->invoice_number ?? 'Invoice #' . $payment->invoice_id),
                    'description' => number_format((float) $payment->amount_paid, 2) . ' via ' . str_replace('_', ' ', $payment->payment_method ?? ''),
                    'status' => $payment->status,
                    'timestamp' => $payment->paid_at ?? $payment->created_at,
                ]);
            });

        RepairJob::with('vehicle:id,brand,model')
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get()
            ->each(function (RepairJob $job) use ($activities) {
                $vehicleLabel = $job->vehicle
                    ? trim("{$job->vehicle->brand} {$job->vehicle->model}")
                    : 'Unknown vehicle';

                $activities->push([
                    'id' => 'repair-' . $job->id,
                    'type' => 'repair',
                    'title' => "Repair job #{$job->id} — {$vehicleLabel}",
                    'description' => 'Job status updated',
                    'status' => $job->status,
                    'timestamp' => $job->updated_at,
                ]);
            });

        InventoryLog::with(['inventory:id,item_name', 'loggedBy:id,name'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->each(function (InventoryLog $log) use ($activities) {
                $action = $log->type === 'in' ? 'Stock in' : 'Stock out';

                $activities->push([
                    'id' => 'inventory-' . $log->id,
                    'type' => 'inventory',
                    'title' => "{$action}: " . ($log->inventory?->item_name ?? 'Inventory item'),
                    'description' => $log->loggedBy?->name
                        ? "Logged by {$log->loggedBy->name}"
                        : ($log->notes ?? ''),
                    'status' => $log->type,
                    'timestamp' => $log->created_at,
                ]);
            });

        return $activities
            ->sortByDesc(fn (array $item) => Carbon::parse($item['timestamp']))
            ->take(8)
            ->values()
            ->map(fn (array $item) => [
                ...$item,
                'timestamp' => Carbon::parse($item['timestamp'])->toIso8601String(),
            ])
            ->all();
    }
}
