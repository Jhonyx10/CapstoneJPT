<?php

namespace App\Services;

use App\Models\Rating;
use App\Models\RepairJob;
use App\Enums\RepairJobStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class RatingService
{
    public function storeRating(array $data, $user)
    {
        $repairJob = RepairJob::with('vehicle')->findOrFail($data['repair_id']);

        if ($repairJob->status !== RepairJobStatus::Completed->value) {
            throw ValidationException::withMessages([
                'repair_id' => 'You can only rate completed repair jobs.',
            ]);
        }

        if ((int) $repairJob->vehicle->user_id !== (int) $user->id) {
            throw new AuthorizationException('You can only rate your own repair jobs.');
        }

        if (Rating::where('repair_id', $repairJob->id)->exists()) {
            throw ValidationException::withMessages([
                'repair_id' => 'This repair job has already been rated.',
            ]);
        }

        return Rating::create($data);
    }
}
