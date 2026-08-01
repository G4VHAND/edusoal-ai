<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('school_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('event');          // generate_question
            $table->string('module');         // Bank Soal
            $table->text('description');

            $table->ipAddress('ip_address')->nullable();
            $table->string('browser')->nullable();
            $table->string('device')->nullable();

            $table->json('properties')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
