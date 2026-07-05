<?php

namespace App\Services;

use App\Enums\RepairJobStatus;
use App\Enums\VehicleStatus;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RepairJob;
use App\Models\Vehicle;
use Carbon\Carbon;

class ReportService
{
    private const MONTHS = 6;

    public function revenue(): array
    {
        $months = $this->monthRange();

        $monthlyTrend = Payment::query()
            ->where('status', 'successful')
            ->where('paid_at', '>=', $months[0]['start'])
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(amount_paid) as amount, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $byMethod = Payment::query()
            ->where('status', 'successful')
            ->selectRaw('payment_method, SUM(amount_paid) as amount, COUNT(*) as count')
            ->groupBy('payment_method')
            ->orderByDesc('amount')
            ->get()
            ->map(fn ($row) => [
                'method' => str_replace('_', ' ', $row->payment_method),
                'amount' => (float) $row->amount,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

        $byType = Payment::query()
            ->where('status', 'successful')
            ->selectRaw('type, SUM(amount_paid) as amount, COUNT(*) as count')
            ->groupBy('type')
            ->orderByDesc('amount')
            ->get()
            ->map(fn ($row) => [
                'type' => str_replace('_', ' ', $row->type),
                'amount' => (float) $row->amount,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

        $totalCollected = (float) Payment::where('status', 'successful')->sum('amount_paid');
        $totalTransactions = Payment::where('status', 'successful')->count();
        $thisMonthStart = Carbon::now()->startOfMonth();

        return [
            'summary' => [
                'total_collected' => $totalCollected,
                'total_transactions' => $totalTransactions,
                'average_payment' => $totalTransactions > 0 ? round($totalCollected / $totalTransactions, 2) : 0,
                'this_month' => (float) Payment::where('status', 'successful')
                    ->where('paid_at', '>=', $thisMonthStart)
                    ->sum('amount_paid'),
            ],
            'monthly_trend' => $this->fillMonthlyGaps($months, $monthlyTrend, 'amount', 'count'),
            'by_method' => $byMethod,
            'by_type' => $byType,
        ];
    }

    public function repairs(): array
    {
        $months = $this->monthRange();

        $byStatus = [];
        foreach (RepairJobStatus::cases() as $status) {
            $byStatus[] = [
                'status' => $status->value,
                'label' => str_replace('_', ' ', ucfirst($status->value)),
                'count' => RepairJob::where('status', $status->value)->count(),
            ];
        }

        $monthlyCreated = RepairJob::query()
            ->where('created_at', '>=', $months[0]['start'])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $monthlyCompleted = RepairJob::query()
            ->where('status', RepairJobStatus::Completed->value)
            ->where('updated_at', '>=', $months[0]['start'])
            ->selectRaw("DATE_FORMAT(updated_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $total = RepairJob::count();
        $completed = RepairJob::where('status', RepairJobStatus::Completed->value)->count();

        return [
            'summary' => [
                'total_jobs' => $total,
                'active_jobs' => RepairJob::whereIn('status', [
                    RepairJobStatus::Confirmed->value,
                    RepairJobStatus::Assigned->value,
                    RepairJobStatus::InProgress->value,
                ])->count(),
                'completed_jobs' => $completed,
                'pending_jobs' => RepairJob::where('status', RepairJobStatus::Pending->value)->count(),
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
                'avg_estimated_cost' => round((float) RepairJob::avg('total_estimated_cost'), 2),
            ],
            'by_status' => $byStatus,
            'monthly_created' => $this->fillMonthlyGaps($months, $monthlyCreated, 'count'),
            'monthly_completed' => $this->fillMonthlyGaps($months, $monthlyCompleted, 'count'),
        ];
    }

    public function inventory(): array
    {
        $months = $this->monthRange();
        $items = Inventory::with('category:id,name')->get();

        $byCategory = $items
            ->groupBy(fn ($item) => $item->category?->name ?? 'Uncategorized')
            ->map(fn ($group, $category) => [
                'category' => $category,
                'quantity' => (float) $group->sum('quantity_in_stock'),
                'value' => round($group->sum(fn ($i) => (float) $i->quantity_in_stock * (float) $i->unit_price), 2),
                'items' => $group->count(),
            ])
            ->values()
            ->sortByDesc('value')
            ->values()
            ->all();

        $movementTrend = InventoryLog::query()
            ->where('created_at', '>=', $months[0]['start'])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, type, SUM(quantity) as quantity")
            ->groupBy('month', 'type')
            ->orderBy('month')
            ->get();

        $movementByMonth = [];
        foreach ($months as $m) {
            $movementByMonth[$m['key']] = ['month' => $m['label'], 'in' => 0, 'out' => 0];
        }
        foreach ($movementTrend as $row) {
            if (isset($movementByMonth[$row->month])) {
                $movementByMonth[$row->month][$row->type] = (float) $row->quantity;
            }
        }

        $lowStock = $items
            ->filter(fn ($item) => (float) $item->quantity_in_stock <= (float) $item->min_stock_alert)
            ->map(fn ($item) => [
                'name' => $item->item_name,
                'sku' => $item->sku,
                'quantity' => (float) $item->quantity_in_stock,
                'min_alert' => (float) $item->min_stock_alert,
                'category' => $item->category?->name ?? 'Uncategorized',
            ])
            ->values()
            ->all();

        $totalValue = $items->sum(fn ($i) => (float) $i->quantity_in_stock * (float) $i->unit_price);

        return [
            'summary' => [
                'total_items' => $items->count(),
                'total_value' => round($totalValue, 2),
                'low_stock_count' => count($lowStock),
                'total_movements' => InventoryLog::count(),
            ],
            'by_category' => $byCategory,
            'movement_trend' => array_values($movementByMonth),
            'low_stock_items' => $lowStock,
        ];
    }

    public function vehicles(): array
    {
        $byStatus = [];
        foreach (VehicleStatus::cases() as $status) {
            $byStatus[] = [
                'status' => $status->value,
                'label' => str_replace('_', ' ', ucwords(str_replace('_', ' ', $status->value))),
                'count' => Vehicle::where('status', $status->value)->count(),
            ];
        }

        $byBodyType = Vehicle::query()
            ->selectRaw('body_type, COUNT(*) as count')
            ->whereNotNull('body_type')
            ->groupBy('body_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'body_type' => ucfirst($row->body_type),
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

        $monthlyAdded = Vehicle::query()
            ->where('created_at', '>=', $this->monthRange()[0]['start'])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $forSaleValue = (float) Vehicle::where('status', VehicleStatus::ForSale->value)->sum('price');

        return [
            'summary' => [
                'total_vehicles' => Vehicle::count(),
                'for_sale' => Vehicle::where('status', VehicleStatus::ForSale->value)->count(),
                'sold' => Vehicle::where('status', VehicleStatus::Sold->value)->count(),
                'for_repair' => Vehicle::where('status', VehicleStatus::ForRepair->value)->count(),
                'for_sale_value' => round($forSaleValue, 2),
            ],
            'by_status' => $byStatus,
            'by_body_type' => $byBodyType,
            'monthly_added' => $this->fillMonthlyGaps($this->monthRange(), $monthlyAdded, 'count'),
        ];
    }

    public function financial(): array
    {
        $months = $this->monthRange();

        $byStatus = Invoice::query()
            ->selectRaw('status, COUNT(*) as count, SUM(total_amount) as total, SUM(amount_due) as due')
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => str_replace('_', ' ', $row->status),
                'count' => (int) $row->count,
                'total' => (float) $row->total,
                'due' => (float) $row->due,
            ])
            ->values()
            ->all();

        $monthlyInvoiced = Invoice::query()
            ->where('created_at', '>=', $months[0]['start'])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as invoiced, SUM(amount_due) as outstanding")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $monthlyCollected = Payment::query()
            ->where('status', 'successful')
            ->where('paid_at', '>=', $months[0]['start'])
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(amount_paid) as collected")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $monthlyFinancial = [];
        foreach ($months as $m) {
            $inv = $monthlyInvoiced->get($m['key']);
            $col = $monthlyCollected->get($m['key']);
            $monthlyFinancial[] = [
                'month' => $m['label'],
                'invoiced' => (float) ($inv->invoiced ?? 0),
                'collected' => (float) ($col->collected ?? 0),
                'outstanding' => (float) ($inv->outstanding ?? 0),
            ];
        }

        $byType = Invoice::query()
            ->selectRaw('type, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('type')
            ->get()
            ->map(fn ($row) => [
                'type' => str_replace('_', ' ', ucwords(str_replace('_', ' ', $row->type ?? 'standard'))),
                'count' => (int) $row->count,
                'total' => (float) $row->total,
            ])
            ->values()
            ->all();

        $totalInvoiced = (float) Invoice::sum('total_amount');
        $totalCollected = (float) Payment::where('status', 'successful')->sum('amount_paid');
        $totalOutstanding = (float) Invoice::sum('amount_due');

        return [
            'summary' => [
                'total_invoiced' => $totalInvoiced,
                'total_collected' => $totalCollected,
                'total_outstanding' => $totalOutstanding,
                'collection_rate' => $totalInvoiced > 0
                    ? round(($totalCollected / $totalInvoiced) * 100, 1)
                    : 0,
                'invoice_count' => Invoice::count(),
            ],
            'by_status' => $byStatus,
            'monthly_financial' => $monthlyFinancial,
            'by_type' => $byType,
        ];
    }

    private function monthRange(): array
    {
        $months = [];
        for ($i = self::MONTHS - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = [
                'key' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
                'start' => $date->copy()->startOfMonth(),
            ];
        }

        return $months;
    }

    private function fillMonthlyGaps(array $months, $data, string ...$fields): array
    {
        return collect($months)->map(function (array $m) use ($data, $fields) {
            $row = $data->get($m['key']);
            $entry = ['month' => $m['label']];
            foreach ($fields as $field) {
                $entry[$field] = $field === 'amount' || $field === 'count'
                    ? (float) ($row?->{$field} ?? 0)
                    : ($row?->{$field} ?? 0);
            }
            if (count($fields) === 1 && $fields[0] === 'count') {
                $entry['count'] = (int) ($row?->count ?? 0);
            }

            return $entry;
        })->all();
    }
}
