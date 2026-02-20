<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique()->comment('Kode voucher yang akan digunakan user');
            $table->string('name', 255)->comment('Nama voucher');
            $table->text('description')->nullable()->comment('Deskripsi voucher');
            $table->integer('text_quota')->default(0)->comment('Jumlah text quota yang diberikan');
            $table->integer('multimedia_quota')->default(0)->comment('Jumlah multimedia quota yang diberikan');
            $table->integer('max_uses')->nullable()->comment('Maksimal penggunaan (null = unlimited)');
            $table->integer('used_count')->default(0)->comment('Jumlah yang sudah digunakan');
            $table->timestamp('expires_at')->nullable()->comment('Tanggal kadaluarsa voucher');
            $table->boolean('is_active')->default(true)->comment('Status aktif/tidak aktif');
            $table->uuid('created_by')->nullable()->comment('Admin yang membuat voucher');
            $table->timestamps();
            
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index('code');
            $table->index('is_active');
            $table->index('expires_at');
        });

        Schema::create('voucher_redemptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('voucher_id');
            $table->uuid('user_id');
            $table->integer('text_quota_received')->default(0);
            $table->integer('multimedia_quota_received')->default(0);
            $table->timestamps();
            
            $table->foreign('voucher_id')->references('id')->on('vouchers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['voucher_id', 'user_id']); // User hanya bisa redeem sekali per voucher
            $table->index('user_id');
            $table->index('voucher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_redemptions');
        Schema::dropIfExists('vouchers');
    }
};
