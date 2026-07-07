<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RatingRequest extends FormRequest
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
            // 1. Validates that the repair job exists
            'repair_id' => 'required|exists:repair_jobs,id',
            
            // 2. Restricts rating to an integer between 1 and 5
            'rating' => 'required|integer|between:1,5',
            
            // 3. Changed 'text' to 'string' and limited length to prevent DB overflow
            'comment' => 'nullable|string|max:1000'
        ];
    }
}
