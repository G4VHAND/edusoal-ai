<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Provider AI ditentukan di level sekolah (bukan per-guru) — admin
     * sekolah yang memilih, semua guru di sekolah tersebut otomatis ikut
     * pakai provider yang sama. Null berarti "belum diset", sistem akan
     * pakai default global (config('ai.default')).
     */
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('ai_provider')->nullable()->after('level');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('ai_provider');
        });
    }
};
