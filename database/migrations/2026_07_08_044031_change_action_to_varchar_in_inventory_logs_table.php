<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE inventory_logs MODIFY action VARCHAR(255) NOT NULL");
    }

    public function down(): void
    {
        // Reverts back to the enum — adjust this list to match your original exactly
        DB::statement("ALTER TABLE inventory_logs MODIFY action ENUM('restock','usage','adjustment','damaged') NOT NULL");
    }
};