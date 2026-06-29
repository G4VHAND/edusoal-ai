<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom status untuk tracking progress queue job.
 *
 * Status:
 *   - pending    : QuestionSet baru dibuat, job belum dijalankan
 *   - processing : Job sedang berjalan (AI sedang di-query)
 *   - completed  : Soal berhasil digenerate
 *   - failed     : AI gagal, lihat kolom ai_error untuk detail
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_sets', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('ai_error');
            $table->index('status', 'idx_qsets_status');
        });
    }

    public function down(): void
    {
        Schema::table('question_sets', function (Blueprint $table) {
            $table->dropIndex('idx_qsets_status');
            $table->dropColumn('status');
        });
    }
};
