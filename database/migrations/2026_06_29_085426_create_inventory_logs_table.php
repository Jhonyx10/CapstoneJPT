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
        Schema::create('inventory_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_id')->constrained('inventories')->onDelete('cascade');
                $table->foreignId('repair_job_id')->nullable()->constrained()->onDelete('set null'); // Tied if used on a job
                $table->enum('type', ['in', 'out']); 
                $table->enum('action', ['restock', 'repair_usage', 'waste', 'adjustment']);
                $table->decimal('quantity', 10, 2);
                $table->foreignId('user_id')->constrained('users')->onDelete('restrict'); // Worker who recorded the change
                $table->text('notes')->nullable();
                $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};
