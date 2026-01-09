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

        // Check if webhook_id is already UUID
        $column = DB::select("SHOW COLUMNS FROM webhook_logs WHERE Field = 'webhook_id'");
        if (!empty($column) && str_contains($column[0]->Type, 'char(36)')) {
            // Already UUID, skip
            return;
        }

        // Drop foreign key first
        try {
            Schema::table('webhook_logs', function (Blueprint $table) {
                $table->dropForeign(['webhook_id']);
            });
        } catch (\Exception $e) {
            // Foreign key might not exist
            \Log::info('Foreign key drop skipped: ' . $e->getMessage());
        }

        // Since webhooks.id is already UUID and webhook_logs.webhook_id is integer,
        // all existing webhook_id values are invalid. We need to set them to NULL.
        // We'll do this by first converting the column to VARCHAR temporarily,
        // then to CHAR(36), setting invalid values to NULL in the process.
        
        // Step 1: Convert to VARCHAR to allow string values
        DB::statement('ALTER TABLE webhook_logs MODIFY webhook_id VARCHAR(36) NULL');
        
        // Step 2: Set all existing integer values to NULL (they can't match UUIDs)
        // Since the column is now VARCHAR, we can check if values are numeric (old integers)
        DB::statement("UPDATE webhook_logs SET webhook_id = NULL WHERE webhook_id REGEXP '^[0-9]+$'");
        
        // Step 3: Get all valid UUID webhook IDs from webhooks table
        $validWebhookIds = DB::table('webhooks')->pluck('id')->toArray();
        
        // Step 4: Set webhook_id to NULL for any records that don't match valid UUIDs
        if (!empty($validWebhookIds)) {
            DB::table('webhook_logs')
                ->whereNotNull('webhook_id')
                ->whereNotIn('webhook_id', $validWebhookIds)
                ->update(['webhook_id' => null]);
        } else {
            // If no webhooks exist, set all to NULL
            DB::table('webhook_logs')->update(['webhook_id' => null]);
        }

        // Step 5: Change webhook_id column type to CHAR(36) UUID
        DB::statement('ALTER TABLE webhook_logs MODIFY webhook_id CHAR(36) NULL');

        // Recreate foreign key
        try {
            Schema::table('webhook_logs', function (Blueprint $table) {
                $table->foreign('webhook_id')->references('id')->on('webhooks')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            \Log::warning('Foreign key creation skipped: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be safely reversed
        // as we don't have the original integer IDs
    }
};

