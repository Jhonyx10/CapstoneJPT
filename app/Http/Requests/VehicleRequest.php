<?php

namespace App\Http\Requests;

use App\Enums\VehicleStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
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
            'brand' => 'required|string',
            'model' => 'required|string',
            'body_type' => 'required|string',
            'engine_type' => 'required|string',
            'transmission' => 'required|string',
            'chassis_number' => 'required|string',
            'plate_number' => 'required|string|unique:vehicles,plate_number',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'status' => [
                'required',
                Rule::enum(VehicleStatus::class)->only(VehicleStatus::registrationOptions()),
            ],
            'service_ids' => [
                'nullable',
                'array',
                Rule::prohibitedIf(fn () => $this->input('status') !== VehicleStatus::ForRepair->value),
            ],
            'service_ids.*' => 'exists:services,id',
            'service_items' => [
                'nullable',
                'array',
                Rule::prohibitedIf(fn () => $this->input('status') !== VehicleStatus::ForRepair->value),
            ],
            'service_items.*' => 'array',
            'service_items.*.*' => 'exists:inventories,id',
            'customer_information_id' => 'nullable|exists:customer_information,id',

            // Optional — public bookings from customers without the mobile app
            // may not include any of these.
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone_number' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'user_id.required' => 'User ID is required',
            'brand.required' => 'Brand is required',
            'model.required' => 'Model is required',
            'body_type.required' => 'Body type is required',
            'engine_type.required' => 'Engine type is required',
            'transmission.required' => 'Transmission is required',
            'chassis_number.required' => 'Chassis number is required',
            'plate_number.required' => 'Plate number is required',
            'status.required' => 'Status is required',
            'status.Illuminate\Validation\Rules\Enum' => 'Status must be either for_repair or for_sale when registering a vehicle',
        ];
    }
}
