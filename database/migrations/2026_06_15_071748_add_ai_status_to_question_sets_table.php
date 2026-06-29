<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::table('question_sets', function (Blueprint $table) {
            $table->boolean('is_ai_generated')->default(false)->after('ai_result');
            $table->string('ai_provider')->nullable()->after('is_ai_generated');
            $table->string('ai_model')->nullable()->after('ai_provider');
            $table->longText('ai_prompt')->nullable()->after('ai_model');
            $table->text('ai_error')->nullable()->after('ai_prompt');
        });
    }

    public function down(): void {
        Schema::table('question_sets', function (Blueprint $table) {
            $table->dropColumn([
                'is_ai_generated',
                'ai_provider',
                'ai_model',
                'ai_prompt',
                'ai_error',
            ]);
        });
    }
};
