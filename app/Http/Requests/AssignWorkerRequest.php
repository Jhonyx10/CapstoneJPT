<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssignWorkerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
   protected function prepareForValidation()
{
    $input = $this->all();

    if (isset($input['repair_job_service_id'])) {
        $input = [$input];
    }

    $normalizedPayload = [];
    if (is_array($input)) {
        foreach ($input as $item) {
            if (is_array($item) && isset($item['repair_job_service_id'])) {
                $repairJobServiceId = (int) $item['repair_job_service_id'];
                $repairJobId = isset($item['repair_job_id']) ? (int) $item['repair_job_id'] : null;
                $workerIdsRaw = $item['worker_ids'] ?? [];

                $workerIds = [];
                if (is_array($workerIdsRaw)) {
                    foreach ($workerIdsRaw as $key => $val) {
                        if (is_array($val) && isset($val['worker_id'])) {
                            $workerIds[] = (int) $val['worker_id'];
                        } else {
                            $workerIds[] = (int) $val;
                        }
                    }
                } elseif (is_numeric($workerIdsRaw)) {
                    $workerIds[] = (int) $workerIdsRaw;
                }

                $normalizedPayload[] = [
                    'repair_job_id' => $repairJobId,
                    'repair_job_service_id' => $repairJobServiceId,
                    'worker_ids' => array_values(array_unique(array_filter($workerIds))),
                ];
            }
        }
    }

    $this->replace($normalizedPayload);
}

public function rules(): array
{
    return [
        '*' => 'required|array',
        '*.repair_job_id' => 'required|exists:repair_jobs,id',
        '*.repair_job_service_id' => 'required|exists:repair_job_services,id',
        '*.worker_ids' => 'required|array',
        '*.worker_ids.*' => 'required|exists:users,id',
    ];
}

    public function messages(): array
    {
        return [
            '*.repair_job_service_id.required' => 'Repair job service ID is required.',
            '*.repair_job_service_id.exists' => 'Repair job service ID does not exist.',
            '*.worker_ids.required' => 'Worker ID is required.',
            '*.worker_ids.exists' => 'Worker ID does not exist.',
        ];
    }
}
