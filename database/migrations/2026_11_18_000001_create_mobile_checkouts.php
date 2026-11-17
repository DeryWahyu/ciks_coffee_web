<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_checkouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->uuid('request_key');
            $table->string('fingerprint', 64);
            $table->json('cart');
            $table->decimal('total', 12, 2);
            $table->string('status', 20)->default('reserved');
            $table->timestamp('expires_at');
            $table->foreignId('order_id')->nullable()->index()->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'request_key']);
            $table->index(['user_id', 'status', 'expires_at']);
        });
        Schema::create('mobile_checkout_products', function (Blueprint $table) {
            $table->foreignUuid('mobile_checkout_id')->constrained('mobile_checkouts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->primary(['mobile_checkout_id', 'product_id']);
        });
        Schema::create('checkout_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('mobile_checkout_id')->constrained('mobile_checkouts')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->unique(['mobile_checkout_id', 'ingredient_id']);
            $table->index(['ingredient_id', 'released_at', 'expires_at']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->text('payment_review_reason')->nullable();
            $table->text('payment_resolution_note')->nullable();
            $table->timestamp('payment_resolved_at')->nullable();
            $table->foreignId('payment_resolved_by')->nullable()->constrained('users')->restrictOnDelete();
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('menunggu_verifikasi','antrian_baru','sedang_dibuat','selesai','diambil','dibatalkan','dikembalikan') DEFAULT 'antrian_baru'");
        }
    }

    public function down(): void
    {
        // Refuse to discard recorded financial resolutions during rollback.
        if (DB::table('orders')->whereIn('status', ['dibatalkan', 'dikembalikan'])->exists()) {
            throw new RuntimeException('Ada penyelesaian pembayaran; pertahankan migrasi dan lakukan rollback kode saja.');
        }
        Schema::dropIfExists('checkout_reservations');
        Schema::dropIfExists('mobile_checkout_products');
        Schema::dropIfExists('mobile_checkouts');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_resolved_by');
            $table->dropColumn(['payment_review_reason', 'payment_resolution_note', 'payment_resolved_at']);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('menunggu_verifikasi','antrian_baru','sedang_dibuat','selesai','diambil') DEFAULT 'antrian_baru'");
        }
    }
};
