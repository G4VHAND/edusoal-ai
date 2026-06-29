<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat subscription per sekolah.
 * Sekolah bisa upgrade/downgrade plan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained();
            $table->enum('status', ['active', 'expired', 'cancelled', 'trial'])
                ->default('trial');
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->integer('amount_paid')->default(0);     // dalam Rupiah
            $table->string('payment_method')->nullable();   // midtrans, transfer, dll
            $table->string('payment_ref')->nullable();      // referensi transaksi
            $table->integer('quota_used')->default(0);      // total generate bulan ini
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('quota_reset_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status'], 'idx_school_subs_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_subscriptions');
    }
};
