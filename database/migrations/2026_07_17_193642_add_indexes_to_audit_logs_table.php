<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * user_id & school_id sudah punya index otomatis dari foreignId(), tapi
     * halaman audit log akan sering difilter kombinasi school+tanggal
     * (school_admin) atau module/event sendirian (super_admin), jadi
     * ditambahkan index yang sesuai pola query itu.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['school_id', 'created_at']);
            $table->index('module');
            $table->index('event');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'created_at']);
            $table->dropIndex(['module']);
            $table->dropIndex(['event']);
            $table->dropIndex(['created_at']);
        });
    }
};
