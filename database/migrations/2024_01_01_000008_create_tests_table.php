<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('title');
            $table->text('description')->nullable();
            $table->smallInteger('time_limit_minutes')->unsigned()->nullable();
            $table->dateTime('opens_at')->nullable();
            $table->dateTime('closes_at')->nullable();
            $table->timestamps();

            $table->index('cohort_id');
            $table->index(['cohort_id', 'opens_at', 'closes_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
