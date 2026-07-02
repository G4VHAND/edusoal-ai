<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Template dokumen Word custom yang diupload sekolah/guru.
 * Sistem akan mengisi placeholder di template ini dengan data soal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();

            // Pemilik template — bisa sekolah (dipakai semua guru) atau user (personal)
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('name');                 // Nama template, misal "Format UTS SMPN 1"
            $table->string('file_path');             // Path file .docx template
            $table->string('original_filename');
            $table->enum('type', ['guru', 'siswa'])->default('guru'); // template untuk versi guru/siswa
            $table->boolean('is_default')->default(false); // dipakai otomatis jika true
            $table->timestamps();

            $table->index(['school_id', 'type']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
