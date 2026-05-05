<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->index('submitted_at');
            $table->index(['test_id', 'submitted_at', 'score']);
        });

        Schema::table('risk_alerts', function (Blueprint $table) {
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex(['submitted_at']);
            $table->dropIndex(['test_id', 'submitted_at', 'score']);
        });

        Schema::table('risk_alerts', function (Blueprint $table) {
            $table->dropIndex(['resolved_at']);
        });
    }
};
