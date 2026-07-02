<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // 1. Classification (Defaulting to 'final_commercial' protects your existing rows)
            $table->string('type')->default('final_commercial')->after('invoice_number'); 
            
            // 2. Structural tracking (Nullable so old invoices don't require a parent link)
            $table->unsignedBigInteger('parent_id')->nullable()->after('type');
            $table->integer('version')->default(1)->after('parent_id');
            
            // 3. Authorization & Legal state fields
            $table->timestamp('authorized_at')->nullable()->after('status');
            $table->text('rejection_reason')->nullable()->after('authorized_at');
            
            // Setup foreign key index connection
            $table->foreign('parent_id')->references('id')->on('invoices')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop foreign key first, then columns
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['type', 'parent_id', 'version', 'authorized_at', 'rejection_reason']);
        });
    }
};