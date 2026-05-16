<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('summarizable_type');
            $table->unsignedBigInteger('summarizable_id');
            $table->string('summary_type'); // weekly_student, weekly_class, risk_explanation
            $table->text('content');
            $table->date('week_start')->nullable();
            $table->date('week_end')->nullable();
            $table->timestamps();

            $table->index(
                ['summarizable_type', 'summarizable_id', 'summary_type', 'week_start'],
                'ai_summaries_poly_type_week_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_summaries');
    }
};
