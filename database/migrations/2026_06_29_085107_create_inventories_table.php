<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('item_name'); // e.g., "Glossy Clear Coat", "Bondo Filler"
            $table->string('sku')->unique();
            $table->decimal('quantity_in_stock', 10, 2)->default(0.00); // Decimals allow partial liters/kg
            $table->string('unit'); // 'liters', 'kg', 'pcs'
            $table->decimal('unit_price', 10, 2);
            $table->integer('min_stock_alert')->default(5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
