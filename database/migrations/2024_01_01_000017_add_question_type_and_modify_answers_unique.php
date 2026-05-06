<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // questions テーブルに question_type カラム追加
        Schema::table('questions', function (Blueprint $table) {
            $table->string('question_type', 20)->default('single')->after('body');
        });

        // answers テーブルの unique 制約を変更（複数選択で複数行保存を許容）
        Schema::table('answers', function (Blueprint $table) {
            $table->dropUnique(['submission_id', 'question_id']);
            $table->unique(['submission_id', 'question_id', 'choice_id']);
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropUnique(['submission_id', 'question_id', 'choice_id']);
            $table->unique(['submission_id', 'question_id']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('question_type');
        });
    }
};
