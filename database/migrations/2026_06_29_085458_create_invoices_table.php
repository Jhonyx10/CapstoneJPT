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
        Schema::create('invoices', function (Blueprint $table) {
           $table->id();
            $table->foreignId('repair_job_id')->constrained('repair_jobs')->onDelete('cascade');
            $table->string('invoice_number')->unique(); // e.g., "INV-2026-XXXX"
            $table->decimal('labor_cost', 10, 2)->default(0.00); // Sum of actual_prices from services pivot
            $table->decimal('material_cost', 10, 2)->default(0.00); // Calculated from materials used
            $table->decimal('tax', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->string('status')->default('unpaid'); // 'unpaid', 'partially_paid', 'paid'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
