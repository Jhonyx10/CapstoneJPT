<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RepairJob;
use App\Enums\RepairJobStatus;
use App\Enums\VehicleStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PayMongoController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'government_id' => 'required',
        ]);

        $governmentIdPath = '';
        if ($request->hasFile('government_id')) {
            $governmentIdPath = $request->file('government_id')->store('government_ids', 'public');
        } elseif (is_string($request->government_id)) {
            $governmentIdPath = $request->government_id;
        }

        $booking = Booking::create([
            'customer_id' => auth()->id() ?? 1,
            'vehicle_id' => $request->vehicle_id,
            'status' => 'pending_payment',
            'government_id_path' => $governmentIdPath,
            'reservation_fee' => 5000.00,
            'expires_at' => now()->addHours(48),
        ]);

        $invoice = Invoice::create([
            'booking_id' => $booking->id,
            'invoice_number' => 'INV-SEL-' . Str::upper(Str::random(8)),
            'total_amount' => 5000.00,
            'amount_due' => 5000.00,
            'status' => 'pending',
            'notes' => 'Holding fee deposit via PayMongo Mobile',
        ]);

        $amountInCents = 5000 * 100;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode(config('services.paymongo.secret') . ':'),
        ])->post('https://api.paymongo.com/v1/checkout_sessions', [
            'data' => [
                'attributes' => [
                    'cancel_url' => 'https://yourdomain.com/payment/cancel',
                    'success_url' => 'https://yourdomain.com/payment/success',
                    'line_items' => [
                        [
                            'amount' => $amountInCents,
                            'currency' => 'PHP',
                            'name' => 'Vehicle Reservation Holding Deposit',
                            'quantity' => 1,
                        ],
                    ],
                    'payment_method_types' => ['gcash', 'paymaya', 'card'],
                    'description' => 'Invoice Ref: ' . $invoice->invoice_number,
                    'reference_number' => $invoice->invoice_number,
                    'metadata' => [
                        'invoice_id' => (string) $invoice->id,
                        'booking_id' => (string) $booking->id,
                        'vehicle_id' => (string) $request->vehicle_id,
                    ],
                ],
            ],
        ]);

        Log::info('PayMongo checkout created', [
            'invoice_number' => $invoice->invoice_number,
            'booking_id' => $booking->id,
            'response_status' => $response->status(),
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to initialize PayMongo gateway session.'], 500);
        }

        $sessionData = $response->json()['data'];
        $checkoutUrl = $sessionData['attributes']['checkout_url'];
        $paymongoSessionId = $sessionData['id'];

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => 'paymongo_checkout',
            'type' => 'down_payment',
            'transaction_reference' => $paymongoSessionId,
            'amount_paid' => 5000.00,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'checkout_url' => $checkoutUrl,
            'checkout_session_id' => $paymongoSessionId,
            'invoice_number' => $invoice->invoice_number,
            'booking_id' => $booking->id,
        ]);
    }

    public function createRepairDownPaymentCheckout(Request $request)
    {
        $request->validate([
            'repair_job_id' => 'required|exists:repair_jobs,id',
        ]);

        $repairJob = RepairJob::with(['invoice', 'vehicle'])->findOrFail($request->repair_job_id);

        if ($repairJob->vehicle->user_id !== auth()->id()) {
            return response()->json(['error' => 'You are not authorized to pay for this repair job.'], 403);
        }

        $invoice = $repairJob->invoice;

        if (!$invoice) {
            return response()->json(['error' => 'No invoice found for this repair job.'], 404);
        }

        if ($invoice->status !== 'unpaid') {
            return response()->json(['error' => 'Down payment has already been made for this invoice.'], 422);
        }

        $existingPayment = Payment::where('invoice_id', $invoice->id)
            ->where('type', 'repair_down_payment')
            ->where('status', 'successful')
            ->exists();

        if ($existingPayment) {
            return response()->json(['error' => 'Down payment has already been completed.'], 422);
        }

        $downPaymentAmount = round((float) $invoice->total_amount * 0.30, 2);
        $amountInCents = (int) round($downPaymentAmount * 100);

        if ($amountInCents < 100) {
            return response()->json(['error' => 'Down payment amount is too small to process.'], 422);
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode(config('services.paymongo.secret') . ':'),
        ])->post('https://api.paymongo.com/v1/checkout_sessions', [
            'data' => [
                'attributes' => [
                    'cancel_url' => 'https://yourdomain.com/payment/cancel',
                    'success_url' => 'https://yourdomain.com/payment/success',
                    'line_items' => [
                        [
                            'amount' => $amountInCents,
                            'currency' => 'PHP',
                            'name' => 'Repair Job Down Payment (30%)',
                            'quantity' => 1,
                        ],
                    ],
                    'payment_method_types' => ['gcash', 'paymaya', 'card'],
                    'description' => 'Repair down payment for ' . $invoice->invoice_number,
                    'reference_number' => $invoice->invoice_number,
                    'metadata' => [
                        'invoice_id' => (string) $invoice->id,
                        'repair_job_id' => (string) $repairJob->id,
                        'vehicle_id' => (string) $repairJob->vehicle_id,
                        'payment_type' => 'repair_down_payment',
                    ],
                ],
            ],
        ]);

        Log::info('PayMongo repair down payment checkout created', [
            'repair_job_id' => $repairJob->id,
            'invoice_number' => $invoice->invoice_number,
            'down_payment_amount' => $downPaymentAmount,
            'response_status' => $response->status(),
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to initialize PayMongo gateway session.'], 500);
        }

        $sessionData = $response->json()['data'];
        $checkoutUrl = $sessionData['attributes']['checkout_url'];
        $paymongoSessionId = $sessionData['id'];

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => 'paymongo_checkout',
            'type' => 'repair_down_payment',
            'transaction_reference' => $paymongoSessionId,
            'amount_paid' => $downPaymentAmount,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'checkout_url' => $checkoutUrl,
            'checkout_session_id' => $paymongoSessionId,
            'invoice_number' => $invoice->invoice_number,
            'repair_job_id' => $repairJob->id,
            'down_payment_amount' => $downPaymentAmount,
            'total_amount' => (float) $invoice->total_amount,
        ]);
    }

    public function createRepairFinalPaymentCheckout(Request $request)
    {
        $request->validate([
            'repair_job_id' => 'required|exists:repair_jobs,id',
        ]);

        $repairJob = RepairJob::with(['invoice', 'vehicle'])->findOrFail($request->repair_job_id);

        if ($repairJob->vehicle->user_id !== auth()->id()) {
            return response()->json(['error' => 'You are not authorized to pay for this repair job.'], 403);
        }

        if ($repairJob->status !== RepairJobStatus::Completed->value) {
            return response()->json(['error' => 'This repair job must be completed before the final payment can be made.'], 422);
        }

        $invoice = $repairJob->invoice;

        if (!$invoice) {
            return response()->json(['error' => 'No invoice found for this repair job.'], 404);
        }

        if ($invoice->status === 'paid') {
            return response()->json(['error' => 'This invoice has already been paid in full.'], 422);
        }

        $amountDue = round((float) $invoice->amount_due, 2);
        $amountInCents = (int) round($amountDue * 100);

        if ($amountInCents < 100) {
            return response()->json(['error' => 'Amount due is too small to process.'], 422);
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode(config('services.paymongo.secret') . ':'),
        ])->post('https://api.paymongo.com/v1/checkout_sessions', [
            'data' => [
                'attributes' => [
                    'cancel_url' => 'https://yourdomain.com/payment/cancel',
                    'success_url' => 'https://yourdomain.com/payment/success',
                    'line_items' => [
                        [
                            'amount' => $amountInCents,
                            'currency' => 'PHP',
                            'name' => 'Repair Job Final Payment',
                            'quantity' => 1,
                        ],
                    ],
                    'payment_method_types' => ['gcash', 'paymaya', 'card'],
                    'description' => 'Repair final payment for ' . $invoice->invoice_number,
                    'reference_number' => $invoice->invoice_number,
                    'metadata' => [
                        'invoice_id' => (string) $invoice->id,
                        'repair_job_id' => (string) $repairJob->id,
                        'vehicle_id' => (string) $repairJob->vehicle_id,
                        'payment_type' => 'repair_final_payment',
                    ],
                ],
            ],
        ]);

        Log::info('PayMongo repair final payment checkout created', [
            'repair_job_id' => $repairJob->id,
            'invoice_number' => $invoice->invoice_number,
            'amount_due' => $amountDue,
            'response_status' => $response->status(),
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to initialize PayMongo gateway session.'], 500);
        }

        $sessionData = $response->json()['data'];
        $checkoutUrl = $sessionData['attributes']['checkout_url'];
        $paymongoSessionId = $sessionData['id'];

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => 'paymongo_checkout',
            'type' => 'repair_final_payment',
            'transaction_reference' => $paymongoSessionId,
            'amount_paid' => $amountDue,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'checkout_url' => $checkoutUrl,
            'checkout_session_id' => $paymongoSessionId,
            'invoice_number' => $invoice->invoice_number,
            'repair_job_id' => $repairJob->id,
            'amount_paid' => $amountDue,
            'total_amount' => (float) $invoice->total_amount,
        ]);
    }

    public function confirmCheckout(Request $request)
    {
        $request->validate([
            'checkout_session_id' => 'required|string',
        ]);

        $checkoutSessionId = $request->checkout_session_id;
        $payment = Payment::where('transaction_reference', $checkoutSessionId)->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment record not found for this checkout session.'], 404);
        }

        if ($payment->status !== 'successful' && !$this->isCheckoutSessionPaid($checkoutSessionId)) {
            return response()->json([
                'error' => 'Payment has not been completed on PayMongo yet.',
                'checkout_session_id' => $checkoutSessionId,
                'payment_status' => $payment->status,
            ], 422);
        }

        try {
            $result = $this->fulfillPayment($payment);

            return response()->json([
                'success' => true,
                'payment_id' => $result['payment']->id,
                'payment_status' => $result['payment']->status,
                'payment_type' => $result['payment']->type,
                'invoice_id' => $result['invoice']->id,
                'invoice_number' => $result['invoice']->invoice_number,
                'invoice_status' => $result['invoice']->status,
                'amount_due' => $result['invoice']->amount_due,
                'booking_id' => $result['booking']?->id,
                'booking_status' => $result['booking']?->status,
                'repair_job_id' => $result['repair_job']?->id,
                'repair_job_status' => $result['repair_job']?->status,
                'vehicle_id' => $result['vehicle']?->id,
                'vehicle_status' => $result['vehicle']?->status?->value ?? $result['vehicle']?->status,
            ]);
        } catch (\Throwable $e) {
            Log::error('PayMongo confirm checkout failed', [
                'checkout_session_id' => $checkoutSessionId,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to confirm reservation payment.'], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        $event = $this->resolveWebhookEvent($payload);

        Log::info('PayMongo webhook received', [
            'event_type' => $event['type'] ?? 'unknown',
            'checkout_session_id' => $event['checkout_session_id'] ?? null,
        ]);

        if (!$event || !$event['checkout_session_id']) {
            return response()->json(['status' => 'received'], 200);
        }

        $payment = Payment::where('transaction_reference', $event['checkout_session_id'])->first();

        if (!$payment) {
            Log::warning('PayMongo webhook: no payment found for session', [
                'checkout_session_id' => $event['checkout_session_id'],
            ]);

            return response()->json(['status' => 'received'], 200);
        }

        try {
            $result = $this->fulfillPayment($payment);

            Log::info('PayMongo webhook: payment fulfilled', [
                'payment_id' => $result['payment']->id,
                'payment_type' => $result['payment']->type,
                'invoice_number' => $result['invoice']->invoice_number,
                'booking_id' => $result['booking']?->id,
                'repair_job_id' => $result['repair_job']?->id,
                'vehicle_id' => $result['vehicle']?->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('PayMongo webhook failed to update records', [
                'checkout_session_id' => $event['checkout_session_id'],
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }

        return response()->json(['status' => 'received'], 200);
    }

    private function resolveWebhookEvent(array $payload): ?array
    {
        if (($payload['data']['type'] ?? null) === 'checkout_session.payment.paid') {
            return [
                'type' => $payload['data']['type'],
                'checkout_session_id' => $payload['data']['data']['id'] ?? null,
            ];
        }

        if (($payload['data']['attributes']['type'] ?? null) === 'checkout_session.payment.paid') {
            return [
                'type' => $payload['data']['attributes']['type'],
                'checkout_session_id' => $payload['data']['attributes']['data']['id'] ?? null,
            ];
        }

        return null;
    }

    private function isCheckoutSessionPaid(string $checkoutSessionId): bool
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(config('services.paymongo.secret') . ':'),
        ])->get("https://api.paymongo.com/v1/checkout_sessions/{$checkoutSessionId}");

        if ($response->failed()) {
            Log::warning('PayMongo checkout session lookup failed', [
                'checkout_session_id' => $checkoutSessionId,
                'status' => $response->status(),
            ]);

            return false;
        }

        $attributes = $response->json('data.attributes', []);

        foreach ($attributes['payments'] ?? [] as $payment) {
            $status = data_get($payment, 'attributes.status') ?? data_get($payment, 'status');

            if ($status === 'paid') {
                return true;
            }
        }

        $paymentIntentStatus = data_get($attributes, 'payment_intent.attributes.status');

        return in_array($paymentIntentStatus, ['succeeded', 'paid'], true);
    }

    private function fulfillPayment(Payment $payment): array
    {
        $invoice = $payment->invoice()->firstOrFail();

        if ($invoice->repair_job_id) {
            return $this->fulfillRepairDownPayment($payment);
        }

        return $this->fulfillReservationPayment($payment);
    }

   private function fulfillRepairDownPayment(Payment $payment): array
    {
        return DB::transaction(function () use ($payment) {
            $payment->refresh();
            $invoice = $payment->invoice()->firstOrFail();
            $repairJob = $invoice->repairJob;
            $vehicle = $repairJob?->vehicle;

            $payment->update([
                'status' => 'successful',
                'paid_at' => $payment->paid_at ?? now(),
            ]);

            $newAmountDue = max(0, round((float) $invoice->amount_due - (float) $payment->amount_paid, 2));
            $isFullyPaid = $newAmountDue <= 0;

            $invoice->update([
                'status' => $isFullyPaid ? 'paid' : 'partially_paid',
                'amount_due' => $newAmountDue,
            ]);

            if ($repairJob && $repairJob->status === RepairJobStatus::Pending->value) {
                $repairJob->update(['status' => RepairJobStatus::Confirmed->value]);
            }

            // Once the invoice is fully settled, the vehicle is ready for the
            // customer to pick up.
            if ($isFullyPaid && $vehicle) {
                $vehicle->update(['status' => VehicleStatus::ReadyForRelease->value]);
            }

            if ($repairJob) {
                $repairJob->load('vehicle.user');

                event(new \App\Events\RepairJobStatusUpdated($repairJob));

                $customer = $repairJob->vehicle?->user;
                if ($customer) {
                    $customer->notifyNow(new \App\Notifications\DownpaymentReceived($repairJob));

                    event(new \App\Events\RepairPaymentReceived(
                        userId: $customer->id,
                        repairJobId: $repairJob->id,
                        invoiceStatus: $invoice->fresh()->status,
                    ));
                }
            }

            return [
                'payment' => $payment->fresh(),
                'invoice' => $invoice->fresh(),
                'booking' => null,
                'repair_job' => $repairJob?->fresh(),
                'vehicle' => $vehicle?->fresh(),
            ];
        });
    }

    private function fulfillReservationPayment(Payment $payment): array
    {
        return DB::transaction(function () use ($payment) {
            $payment->refresh();
            $invoice = $payment->invoice()->firstOrFail();
            $booking = $invoice->booking;
            $vehicle = $booking?->vehicle;

            $payment->update([
                'status' => 'successful',
                'paid_at' => $payment->paid_at ?? now(),
            ]);

            $invoice->update([
                'status' => 'paid',
                'amount_due' => 0,
            ]);

            if ($booking) {
                $booking->update(['status' => 'confirmed']);
            }

            if ($vehicle) {
                $vehicle->update(['status' => VehicleStatus::OnHold->value]);
            }

            return [
                'payment' => $payment->fresh(),
                'invoice' => $invoice->fresh(),
                'booking' => $booking?->fresh(),
                'repair_job' => null,
                'vehicle' => $vehicle?->fresh(),
            ];
        });
    }
}
