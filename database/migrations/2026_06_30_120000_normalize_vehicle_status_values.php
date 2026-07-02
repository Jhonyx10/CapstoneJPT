<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('vehicles')
            ->where('status', 'available')
            ->update(['status' => 'for_sale']);

        DB::table('vehicles')
            ->where('status', 'active')
            ->update(['status' => 'for_repair']);
    }

    public function down(): void
    {
        DB::table('vehicles')
            ->where('status', 'for_sale')
            ->update(['status' => 'available']);

        DB::table('vehicles')
            ->where('status', 'for_repair')
            ->update(['status' => 'active']);
    }
};
