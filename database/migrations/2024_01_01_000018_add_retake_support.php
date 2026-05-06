<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->unsignedSmallInteger('max_attempts')->nullable()->after('closes_at');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->unsignedSmallInteger('attempt')->default(1)->after('user_id');
        });

        // 外部キー制約がユニークインデックスに依存しているため、
        // 先に外部キーを落とし、ユニーク制約を張り替えてから外部キーを再追加する
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropForeign(['test_id']);
            $table->dropUnique(['test_id', 'user_id']);
            $table->unique(['test_id', 'user_id', 'attempt']);
            $table->foreign('test_id')->references('id')->on('tests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropForeign(['test_id']);
            $table->dropUnique(['test_id', 'user_id', 'attempt']);
            $table->unique(['test_id', 'user_id']);
            $table->foreign('test_id')->references('id')->on('tests')->cascadeOnDelete();
            $table->dropColumn('attempt');
        });

        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn('max_attempts');
        });
    }
};
