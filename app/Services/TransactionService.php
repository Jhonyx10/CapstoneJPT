<?php

namespace App\Services;

use App\Models\Payment;

class TransactionService
{
    public function getAll()
    {
        return Payment::with('invoice')->orderByDesc('created_at')->get();
    }

    public function getById($id)
    {
        return Payment::with('invoice')->find($id);
    }
}