<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahkan index pada kolom-kolom yang sering digunakan untuk filter dan query.
 *
 * Sebelum: Full table scan untuk setiap query filter di BankSoal.
 * Setelah: Index lookup — jauh lebih cepat saat data ratusan ribu baris.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_sets', function (Blueprint $table) {
            // Index untuk filter + ordering yang paling sering dipakai
            $table->index(['user_id', 'created_at'], 'idx_qsets_user_created');

            // Index untuk filter per kolom
            $table->index('question_type', 'idx_qsets_question_type');
            $table->index('difficulty', 'idx_qsets_difficulty');
            $table->index('subject', 'idx_qsets_subject');
            $table->index('ai_provider', 'idx_qsets_ai_provider');
            $table->index('is_ai_generated', 'idx_qsets_is_ai_generated');
        });

        Schema::table('questions', function (Blueprint $table) {
            // Index untuk query questions by question_set_id (paling sering)
            $table->index('question_set_id', 'idx_questions_qset_id');
        });
    }

    public function down(): void
    {
        Schema::table('question_sets', function (Blueprint $table) {
            $table->dropIndex('idx_qsets_user_created');
            $table->dropIndex('idx_qsets_question_type');
            $table->dropIndex('idx_qsets_difficulty');
            $table->dropIndex('idx_qsets_subject');
            $table->dropIndex('idx_qsets_ai_provider');
            $table->dropIndex('idx_qsets_is_ai_generated');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('idx_questions_qset_id');
        });
    }
};
