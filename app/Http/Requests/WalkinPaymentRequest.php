<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WalkinPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'repair_job_id' => 'required|exists:repair_jobs,id',
            'invoice_id' => 'required|exists:invoices,id',
            'type' => 'required|in:repair_down_payment,repair_partial_payment,repair_final_payment',
            'amount' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'repair_job_id.required' => 'Repair job ID is required.',
            'repair_job_id.exists' => 'Repair job ID does not exist.',
            'invoice_id.required' => 'Invoice ID is required.',
            'invoice_id.exists' => 'Invoice ID does not exist.',
            'type.required' => 'Payment type is required.',
            'type.in' => 'Payment type must be one of: repair_down_payment, repair_partial_payment, repair_final_payment.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.min' => 'Amount must be at least 0.',
        ];
    }
}
