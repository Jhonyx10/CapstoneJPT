<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ItemCategory extends Model
{
    protected $table = 'item_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'category_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'item_category_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            $category->slug = Str::slug($category->name);
        });

        static::updating(function ($category) {
            $category->slug = Str::slug($category->name);
        });
    }
}
