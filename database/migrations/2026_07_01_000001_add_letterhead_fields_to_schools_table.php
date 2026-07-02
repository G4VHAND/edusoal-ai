<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah field untuk kop surat otomatis sekolah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('headmaster_name')->nullable()->after('logo');
            $table->string('headmaster_nip')->nullable()->after('headmaster_name');
            $table->text('letterhead_address')->nullable()->after('headmaster_nip');
            $table->boolean('show_letterhead_on_export')->default(true)->after('letterhead_address');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'headmaster_name', 'headmaster_nip',
                'letterhead_address', 'show_letterhead_on_export',
            ]);
        });
    }
};
