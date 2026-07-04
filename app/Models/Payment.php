<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'invoice_id',
        'payment_method',
        'type',
        'transaction_reference',
        'amount_paid',
        'status',
        'paid_at'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
