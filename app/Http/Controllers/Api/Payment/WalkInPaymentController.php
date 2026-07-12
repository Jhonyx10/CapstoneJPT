<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Http\Requests\WalkinPaymentRequest;

class WalkInPaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function __invoke(WalkinPaymentRequest $request)
    {
        return $this->paymentService->processWalkInPayment($request->validated());
    }
}
