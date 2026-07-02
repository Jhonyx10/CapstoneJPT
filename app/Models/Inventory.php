<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Inventory extends Model
{
    protected $table = 'inventories';

    protected $fillable = [
        'item_name',
        'sku',
        'category_id',
        'quantity_in_stock',
        'unit',
        'unit_price',
        'min_stock_alert',
        'category_id',
    ];

    protected static function booted()
    {
        static::creating(function ($item) {
            if (empty($item->sku)) {
                $prefix = strtoupper(Str::substr($item->category?->name ?? 'ITM', 0, 3));
                $count = static::where('category_id', $item->category_id)->count() + 1;
                $item->sku = "{$prefix}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function stockLogs()
    {
        return $this->hasMany(InventoryStockLog::class);
    }

    public function category()
    {
        return $this->belongsTo(ItemCategory::class);
    }
}
