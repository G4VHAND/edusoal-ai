<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom untuk fitur soal bergambar.
 *
 * needs_image           : AI merekomendasikan soal ini butuh gambar
 * image_recommendation  : Deskripsi gambar yang direkomendasikan AI
 * image_path            : Path gambar yang diupload guru (sudah ada sebelumnya)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (! Schema::hasColumn('questions', 'needs_image')) {
                $table->boolean('needs_image')->default(false)->after('source_paragraph');
            }
            if (! Schema::hasColumn('questions', 'image_recommendation')) {
                $table->text('image_recommendation')->nullable()->after('needs_image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['needs_image', 'image_recommendation']);
        });
    }
};
