<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\RepairJob;
use App\Models\Payment;
use App\Models\Invoice;
use App\Enums\RepairJobStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Requests\WalkinPaymentRequest;

class PaymentService
{
    public function processWalkInPayment($data)
    {
        $repairJob = RepairJob::with(['invoice', 'vehicle'])->findOrFail($data['repair_job_id']);

        $invoice = $repairJob->invoice;

        if (!$invoice) {
            return response()->json(['error' => 'No invoice found for this repair job.'], 404);
        }

        if ($invoice->status === 'paid') {
            return response()->json(['error' => 'This invoice has already been fully paid.'], 422);
        }

        $type = $data['type'];
        $amount = round((float) $data['amount'], 2);

        if ($type === 'repair_down_payment') {
            $existingDownPayment = Payment::where('invoice_id', $invoice->id)
                ->where('type', 'repair_down_payment')
                ->where('status', 'successful')
                ->exists();

            if ($existingDownPayment) {
                return response()->json(['error' => 'Down payment has already been completed.'], 422);
            }
        }

        $totalPaid = Payment::where('invoice_id', $invoice->id)
            ->where('status', 'successful')
            ->sum('amount_paid');

        $remainingBalance = round((float) $invoice->total_amount - $totalPaid, 2);

        if ($amount > $remainingBalance) {
            return response()->json(['error' => 'Payment amount exceeds the remaining balance.'], 422);
        }

        return DB::transaction(function () use ($repairJob, $invoice, $type, $amount, $totalPaid) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'payment_method' => 'walk_in',
                'type' => $type,
                'transaction_reference' => 'WALKIN-' . strtoupper(Str::random(10)),
                'amount_paid' => $amount,
                'status' => 'successful',
                'paid_at' => now(),
            ]);

            $newTotalPaid = $totalPaid + $amount;
            $invoice->update([
                'status' => $newTotalPaid >= (float) $invoice->total_amount ? 'paid' : 'partially_paid',
            ]);

            $this->deductAmountDue($invoice, $amount);

            // A successful down payment confirms the job — move it out of "pending"
            if ($type === 'repair_down_payment' && $repairJob->status === 'pending') {
                $repairJob->update(['status' => 'confirmed']);
            }

            Log::info('Walk-in payment recorded', [
                'repair_job_id' => $repairJob->id,
                'invoice_number' => $invoice->invoice_number,
                'type' => $type,
                'amount' => $amount,
                'payment_id' => $payment->id,
                'repair_job_status' => $repairJob->status,
            ]);

            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'invoice_number' => $invoice->invoice_number,
                'repair_job_id' => $repairJob->id,
                'repair_job_status' => $repairJob->status,
                'type' => $type,
                'amount_paid' => $amount,
                'total_amount' => (float) $invoice->total_amount,
                'remaining_balance' => round((float) $invoice->total_amount - $newTotalPaid, 2),
                'invoice_status' => $invoice->status,
            ]);
        });
    }

    /**
     * Deduct the paid amount from the invoice's amount_due.
     * Refreshes the invoice instance afterward so callers see the updated value.
     */
    private function deductAmountDue(Invoice $invoice, float $amountPaid): void
    {
        $newAmountDue = round((float) $invoice->amount_due - $amountPaid, 2);

        $invoice->update([
            'amount_due' => max($newAmountDue, 0),
        ]);
    }
}