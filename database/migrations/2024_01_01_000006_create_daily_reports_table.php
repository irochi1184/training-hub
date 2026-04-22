<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_id')->constrained('curricula')->cascadeOnDelete();
            $table->date('reported_on');
            $table->tinyInteger('understanding_level')->unsigned();
            $table->text('content');
            $table->text('impression')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'curriculum_id', 'reported_on']);
            $table->index(['curriculum_id', 'reported_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
