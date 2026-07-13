<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sumber referensi (buku/kurikulum/jurnal/dst.) yang jadi dasar generate
     * soal SATU SET soal secara keseluruhan — beda dengan
     * `questions.source_paragraph` yang isinya kutipan/referensi spesifik
     * per soal.
     */
    public function up(): void
    {
        Schema::table('question_sets', function (Blueprint $table) {
            $table->text('source_reference')->nullable()->after('ai_result');
        });
    }

    public function down(): void
    {
        Schema::table('question_sets', function (Blueprint $table) {
            $table->dropColumn('source_reference');
        });
    }
};
