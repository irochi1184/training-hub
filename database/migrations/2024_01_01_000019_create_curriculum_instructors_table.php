<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained('curricula')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 10)->default('main'); // main or sub
            $table->timestamps();

            $table->unique(['curriculum_id', 'user_id']);
            $table->index(['user_id', 'role']);
        });

        // 既存の instructor_id データを中間テーブルへ移行
        DB::statement('
            INSERT INTO curriculum_instructors (curriculum_id, user_id, role, created_at, updated_at)
            SELECT id, instructor_id, \'main\', NOW(), NOW()
            FROM curricula
            WHERE instructor_id IS NOT NULL AND deleted_at IS NULL
        ');

        Schema::table('curricula', function (Blueprint $table) {
            $table->dropForeign(['instructor_id']);
            $table->dropIndex(['instructor_id']);
            $table->dropColumn('instructor_id');
        });
    }

    public function down(): void
    {
        Schema::table('curricula', function (Blueprint $table) {
            $table->foreignId('instructor_id')->nullable()->after('organization_id')->constrained('users');
            $table->index('instructor_id');
        });

        // 中間テーブルからメイン講師を復元
        DB::statement('
            UPDATE curricula c
            JOIN curriculum_instructors ci ON ci.curriculum_id = c.id AND ci.role = \'main\'
            SET c.instructor_id = ci.user_id
        ');

        Schema::dropIfExists('curriculum_instructors');
    }
};
