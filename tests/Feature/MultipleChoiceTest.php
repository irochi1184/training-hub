<?php

namespace Tests\Feature;

use App\Actions\ScoreSubmissionAction;
use App\Models\Choice;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Submission;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MultipleChoiceTest extends TestCase
{
    use RefreshDatabase;

    private function createTestWithMultipleChoice(): array
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create(['instructor_id' => $instructor->id]);
        $student = User::factory()->student()->create(['organization_id' => $instructor->organization_id]);
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        $test = Test::factory()->create([
            'curriculum_id' => $curriculum->id,
            'created_by' => $instructor->id,
        ]);

        // 単一選択問題
        $q1 = Question::create([
            'test_id' => $test->id,
            'body' => '単一選択問題',
            'question_type' => 'single',
            'position' => 1,
            'score' => 1,
        ]);
        $q1c1 = Choice::create(['question_id' => $q1->id, 'body' => '正解', 'is_correct' => true, 'position' => 1]);
        $q1c2 = Choice::create(['question_id' => $q1->id, 'body' => '不正解', 'is_correct' => false, 'position' => 2]);

        // 複数選択問題
        $q2 = Question::create([
            'test_id' => $test->id,
            'body' => '複数選択問題',
            'question_type' => 'multiple',
            'position' => 2,
            'score' => 2,
        ]);
        $q2c1 = Choice::create(['question_id' => $q2->id, 'body' => '正解A', 'is_correct' => true, 'position' => 1]);
        $q2c2 = Choice::create(['question_id' => $q2->id, 'body' => '不正解B', 'is_correct' => false, 'position' => 2]);
        $q2c3 = Choice::create(['question_id' => $q2->id, 'body' => '正解C', 'is_correct' => true, 'position' => 3]);
        $q2c4 = Choice::create(['question_id' => $q2->id, 'body' => '不正解D', 'is_correct' => false, 'position' => 4]);

        return compact('instructor', 'student', 'test', 'q1', 'q1c1', 'q1c2', 'q2', 'q2c1', 'q2c2', 'q2c3', 'q2c4');
    }

    public function test_複数選択問題を含むテストを作成できる(): void
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create(['instructor_id' => $instructor->id]);

        $response = $this->actingAs($instructor)->post('/tests', [
            'curriculum_id' => $curriculum->id,
            'title' => '複数選択テスト',
            'description' => null,
            'time_limit_minutes' => null,
            'opens_at' => null,
            'closes_at' => null,
            'questions' => [
                [
                    'body' => '単一問題',
                    'question_type' => 'single',
                    'score' => 1,
                    'choices' => [
                        ['body' => 'A', 'is_correct' => true],
                        ['body' => 'B', 'is_correct' => false],
                    ],
                ],
                [
                    'body' => '複数問題',
                    'question_type' => 'multiple',
                    'score' => 2,
                    'choices' => [
                        ['body' => 'X', 'is_correct' => true],
                        ['body' => 'Y', 'is_correct' => true],
                        ['body' => 'Z', 'is_correct' => false],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('tests.index'));
        $this->assertDatabaseHas('questions', ['body' => '複数問題', 'question_type' => 'multiple']);
    }

    public function test_複数選択で全正解を選ぶと満点(): void
    {
        $data = $this->createTestWithMultipleChoice();

        // 受験開始
        $this->actingAs($data['student'])->get("/tests/{$data['test']->id}/take");

        $response = $this->actingAs($data['student'])->post("/tests/{$data['test']->id}/submissions", [
            'answers' => [
                ['question_id' => $data['q1']->id, 'choice_id' => $data['q1c1']->id, 'choice_ids' => []],
                ['question_id' => $data['q2']->id, 'choice_id' => null, 'choice_ids' => [$data['q2c1']->id, $data['q2c3']->id]],
            ],
        ]);

        $response->assertRedirect();
        $submission = Submission::where('test_id', $data['test']->id)
            ->where('user_id', $data['student']->id)
            ->first();
        $this->assertEquals(3, $submission->score); // 1 + 2 = 3
    }

    public function test_複数選択で一部のみ選ぶと0点(): void
    {
        $data = $this->createTestWithMultipleChoice();

        $this->actingAs($data['student'])->get("/tests/{$data['test']->id}/take");

        $this->actingAs($data['student'])->post("/tests/{$data['test']->id}/submissions", [
            'answers' => [
                ['question_id' => $data['q1']->id, 'choice_id' => $data['q1c1']->id, 'choice_ids' => []],
                // 正解は q2c1 と q2c3 だが q2c1 のみ選択
                ['question_id' => $data['q2']->id, 'choice_id' => null, 'choice_ids' => [$data['q2c1']->id]],
            ],
        ]);

        $submission = Submission::where('test_id', $data['test']->id)
            ->where('user_id', $data['student']->id)
            ->first();
        $this->assertEquals(1, $submission->score); // 単一:1 + 複数:0 = 1
    }

    public function test_複数選択で不正解を含めると0点(): void
    {
        $data = $this->createTestWithMultipleChoice();

        $this->actingAs($data['student'])->get("/tests/{$data['test']->id}/take");

        $this->actingAs($data['student'])->post("/tests/{$data['test']->id}/submissions", [
            'answers' => [
                ['question_id' => $data['q1']->id, 'choice_id' => $data['q1c1']->id, 'choice_ids' => []],
                // 正解+不正解を両方選択
                ['question_id' => $data['q2']->id, 'choice_id' => null, 'choice_ids' => [$data['q2c1']->id, $data['q2c2']->id, $data['q2c3']->id]],
            ],
        ]);

        $submission = Submission::where('test_id', $data['test']->id)
            ->where('user_id', $data['student']->id)
            ->first();
        $this->assertEquals(1, $submission->score); // 単一:1 + 複数:0 = 1
    }

    public function test_期間外のテストは受験できない(): void
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create(['instructor_id' => $instructor->id]);
        $student = User::factory()->student()->create(['organization_id' => $instructor->organization_id]);
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        $test = Test::factory()->create([
            'curriculum_id' => $curriculum->id,
            'created_by' => $instructor->id,
            'opens_at' => Carbon::tomorrow(),
            'closes_at' => Carbon::tomorrow()->addDays(7),
        ]);

        $response = $this->actingAs($student)->get("/tests/{$test->id}/take");

        $response->assertForbidden();
    }

    public function test_終了後のテストは受験できない(): void
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create(['instructor_id' => $instructor->id]);
        $student = User::factory()->student()->create(['organization_id' => $instructor->organization_id]);
        Enrollment::factory()->create(['user_id' => $student->id, 'curriculum_id' => $curriculum->id]);

        $test = Test::factory()->create([
            'curriculum_id' => $curriculum->id,
            'created_by' => $instructor->id,
            'opens_at' => Carbon::yesterday()->subDays(7),
            'closes_at' => Carbon::yesterday(),
        ]);

        $response = $this->actingAs($student)->get("/tests/{$test->id}/take");

        $response->assertForbidden();
    }
}
