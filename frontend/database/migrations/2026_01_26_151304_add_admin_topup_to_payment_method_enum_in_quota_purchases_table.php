<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify ENUM to include 'admin_topup'
        DB::statement("ALTER TABLE quota_purchases MODIFY COLUMN payment_method ENUM('manual', 'bank_transfer', 'credit_card', 'e_wallet', 'xendit', 'admin_topup', 'other') DEFAULT 'manual'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to previous ENUM values (without admin_topup)
        DB::statement("ALTER TABLE quota_purchases MODIFY COLUMN payment_method ENUM('manual', 'bank_transfer', 'credit_card', 'e_wallet', 'xendit', 'other') DEFAULT 'manual'");
    }
};
