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
        if (!Schema::hasTable('webhook_logs')) {
            return;
        }

        // Change error_message from TEXT to LONGTEXT to handle longer error messages
        DB::statement('ALTER TABLE webhook_logs MODIFY error_message LONGTEXT NULL');
        
        // Also change response_body to LONGTEXT for consistency
        DB::statement('ALTER TABLE webhook_logs MODIFY response_body LONGTEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('webhook_logs')) {
            return;
        }

        // Revert back to TEXT (note: this might truncate existing data)
        DB::statement('ALTER TABLE webhook_logs MODIFY error_message TEXT NULL');
        DB::statement('ALTER TABLE webhook_logs MODIFY response_body TEXT NULL');
    }
};

