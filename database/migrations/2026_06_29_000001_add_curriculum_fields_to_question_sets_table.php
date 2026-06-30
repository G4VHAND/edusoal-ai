<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah parameter kurikulum dan jenis asesmen.
 *
 * curriculum:
 *   - merdeka : Kurikulum Merdeka (Capaian Pembelajaran)
 *   - k13     : Kurikulum 2013 (Kompetensi Dasar)
 *
 * assessment_type:
 *   - reguler : Soal ujian standar
 *   - hots    : Higher Order Thinking Skills
 *   - akm     : Asesmen Kompetensi Minimum (numerasi/literasi)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_sets', function (Blueprint $table) {
            $table->string('curriculum')->default('merdeka')->after('difficulty');
            $table->string('assessment_type')->default('reguler')->after('curriculum');
        });
    }

    public function down(): void
    {
        Schema::table('question_sets', function (Blueprint $table) {
            $table->dropColumn(['curriculum', 'assessment_type']);
        });
    }
};
