<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paket langganan yang tersedia.
 * Diisi via seeder, bukan dari input user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // Free, Basic, Pro, Enterprise
            $table->string('slug')->unique();               // free, basic, pro, enterprise
            $table->integer('price_monthly')->default(0);  // dalam Rupiah
            $table->integer('price_yearly')->default(0);
            $table->integer('max_teachers')->default(1);   // -1 = unlimited
            $table->integer('quota_per_month')->default(10); // jumlah generate per bulan
            $table->integer('max_questions_per_generate')->default(10);
            $table->boolean('allow_image_upload')->default(false);
            $table->boolean('allow_export_word')->default(false);
            $table->boolean('allow_export_pdf')->default(true);
            $table->boolean('allow_all_providers')->default(false); // free hanya 1 provider
            $table->json('features')->nullable();           // fitur tambahan dalam JSON
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
