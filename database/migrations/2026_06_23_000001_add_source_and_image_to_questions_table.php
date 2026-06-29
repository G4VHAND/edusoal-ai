<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom untuk:
 * 1. source_paragraph — kutipan dari materi yang menjadi dasar soal (anti-hallucination)
 * 2. image_path       — path gambar yang diupload guru untuk soal berbasis visual
 * 3. image_description — deskripsi gambar hasil Gemini Vision (untuk provider non-Vision)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->text('source_paragraph')->nullable()->after('explanation');
            $table->string('image_path')->nullable()->after('source_paragraph');
            $table->text('image_description')->nullable()->after('image_path');
        });

        Schema::table('question_sets', function (Blueprint $table) {
            $table->string('material_image')->nullable()->after('material_original_name');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['source_paragraph', 'image_path', 'image_description']);
        });

        Schema::table('question_sets', function (Blueprint $table) {
            $table->dropColumn('material_image');
        });
    }
};
