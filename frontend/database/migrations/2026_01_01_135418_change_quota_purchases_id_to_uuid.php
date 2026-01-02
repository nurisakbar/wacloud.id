<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if id column is already UUID (CHAR(36))
        $columnInfo = DB::select("SHOW COLUMNS FROM quota_purchases WHERE Field = 'id'");
        if (!empty($columnInfo) && strpos(strtolower($columnInfo[0]->Type), 'char') !== false) {
            // Already UUID, skip migration
            return;
        }

        // Step 1: Remove AUTO_INCREMENT from id column (must be done before dropping primary key)
        DB::statement('ALTER TABLE quota_purchases MODIFY id BIGINT UNSIGNED NOT NULL');

        // Step 2: Drop primary key constraint
        DB::statement('ALTER TABLE quota_purchases DROP PRIMARY KEY');

        // Step 3: Add new UUID column
        DB::statement('ALTER TABLE quota_purchases ADD COLUMN new_id CHAR(36) NOT NULL AFTER id');

        // Step 4: Generate UUIDs for existing records
        $purchases = DB::table('quota_purchases')->get();
        foreach ($purchases as $purchase) {
            DB::table('quota_purchases')
                ->where('id', $purchase->id)
                ->update(['new_id' => (string) Str::uuid()]);
        }

        // Step 5: Drop old id column
        DB::statement('ALTER TABLE quota_purchases DROP COLUMN id');

        // Step 6: Rename new_id to id
        DB::statement('ALTER TABLE quota_purchases CHANGE COLUMN new_id id CHAR(36) NOT NULL');

        // Step 7: Add primary key back
        DB::statement('ALTER TABLE quota_purchases ADD PRIMARY KEY (id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: Converting UUID back to auto-increment integer will lose the original IDs
        // This is a destructive operation
        
        // Step 1: Drop primary key
        DB::statement('ALTER TABLE quota_purchases DROP PRIMARY KEY');

        // Step 2: Add new bigInteger id column
        DB::statement('ALTER TABLE quota_purchases ADD COLUMN new_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT AFTER id');

        // Step 3: Drop old UUID id column
        DB::statement('ALTER TABLE quota_purchases DROP COLUMN id');

        // Step 4: Rename new_id to id
        DB::statement('ALTER TABLE quota_purchases CHANGE COLUMN new_id id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        // Step 5: Add primary key back
        DB::statement('ALTER TABLE quota_purchases ADD PRIMARY KEY (id)');
    }
};
