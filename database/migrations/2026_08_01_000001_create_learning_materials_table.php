<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materi pembelajaran yang diupload guru/admin sekolah sebagai referensi
 * AI supaya soal yang digenerate lebih akurat/sesuai konteks.
 *
 * - user_id: SELALU diisi — siapa yang mengupload file ini.
 * - school_id: nullable. NULL = materi pribadi (cuma kelihatan oleh
 *   uploader-nya sendiri). Terisi = materi sekolah (dishare, kelihatan oleh
 *   semua guru + admin di sekolah itu). Hanya school_admin yang boleh
 *   mengisi school_id (lihat LearningMaterialService::store()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->string('subject')->nullable();
            $table->text('description')->nullable();

            $table->string('file_path');
            $table->string('original_filename');
            $table->unsignedBigInteger('file_size')->default(0); // bytes
            $table->string('mime_type')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id']);
            $table->index(['school_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_materials');
    }
};
