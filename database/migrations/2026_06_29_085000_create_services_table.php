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
       Schema::create('services', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // e.g., "Full Body Paint", "Dent Removal"
                $table->decimal('base_price', 10, 2);
                $table->foreignId('worker_type')->constrained('worker_types')->onDelete('restrict'); // Linked directly to role id
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
