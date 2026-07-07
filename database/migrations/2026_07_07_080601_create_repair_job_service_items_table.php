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
        Schema::create('repair_job_service_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('repair_job_service_id');
            $table->unsignedBigInteger('inventory_id');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->timestamps();
            
            $table->foreign('repair_job_service_id')->references('id')->on('repair_job_services')->onDelete('cascade');
            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_job_service_items');
    }
};
