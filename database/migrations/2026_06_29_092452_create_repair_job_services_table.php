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
        Schema::create('repair_job_services', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('repair_job_id')->constrained('repair_jobs')->onDelete('cascade');
                    $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
                    $table->string('status')->default('pending'); // 'pending', 'in_progress', 'completed'
                    $table->decimal('actual_price', 10, 2); // Allows manual adjustment from base price
                    $table->timestamps();
                });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_job_services');
    }
};
