<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Choice;
use App\Models\Question;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Answer>
 */
class AnswerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'question_id' => Question::factory(),
            'choice_id' => null,
            'is_correct' => null,
        ];
    }
}
