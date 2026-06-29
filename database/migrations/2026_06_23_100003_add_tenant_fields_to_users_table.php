<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom multi-tenancy dan role ke tabel users.
 *
 * Role:
 *   - super_admin  : Anthropic/owner — bisa lihat semua data
 *   - school_admin : Admin sekolah — kelola guru di sekolahnya
 *   - teacher      : Guru — hanya kelola soal miliknya sendiri
 *   - individual   : User individual (bukan bagian sekolah)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('school_id')
                ->nullable()
                ->constrained('schools')
                ->nullOnDelete()
                ->after('id');

            $table->foreignId('subscription_plan_id')
                ->nullable()
                ->constrained('subscription_plans')
                ->nullOnDelete()
                ->after('school_id');

            $table->enum('role', ['super_admin', 'school_admin', 'teacher', 'individual'])
                ->default('individual')
                ->after('subscription_plan_id');

            // Quota tracking
            $table->integer('quota_used_this_month')->default(0)->after('role');
            $table->timestamp('quota_reset_at')->nullable()->after('quota_used_this_month');

            // Subscription untuk individual user
            $table->timestamp('subscription_ends_at')->nullable()->after('quota_reset_at');
            $table->boolean('is_active')->default(true)->after('subscription_ends_at');

            $table->index(['school_id', 'role'], 'idx_users_school_role');
            $table->index('role', 'idx_users_role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_school_role');
            $table->dropIndex('idx_users_role');
            $table->dropForeign(['school_id']);
            $table->dropForeign(['subscription_plan_id']);
            $table->dropColumn([
                'school_id', 'subscription_plan_id', 'role',
                'quota_used_this_month', 'quota_reset_at',
                'subscription_ends_at', 'is_active',
            ]);
        });
    }
};
