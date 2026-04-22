<?php

namespace Tests\Unit;

use App\Http\Requests\StoreTestRequest;
use App\Models\Curriculum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreTestRequestTest extends TestCase
{
    use RefreshDatabase;

    /** 正しい入力でバリデーションが通る */
    public function test_有効な入力でバリデーションが通る(): void
    {
        $curriculum = Curriculum::factory()->create();

        $validator = $this->makeValidator([
            'curriculum_id' => $curriculum->id,
            'title' => 'テストタイトル',
            'questions' => [
                [
                    'body' => '設問1',
                    'score' => 10,
                    'choices' => [
                        ['body' => '選択肢A', 'is_correct' => true],
                        ['body' => '選択肢B', 'is_correct' => false],
                    ],
                ],
            ],
        ]);

        $this->assertTrue($validator->passes());
    }

    /** closes_at が opens_at 以前だと失敗する */
    public function test_closes_atがopens_atより前だとエラー(): void
    {
        $curriculum = Curriculum::factory()->create();

        $validator = $this->makeValidator([
            'curriculum_id' => $curriculum->id,
            'title' => 'テスト',
            'opens_at' => '2026-05-01 00:00:00',
            'closes_at' => '2026-04-30 00:00:00',
            'questions' => [[
                'body' => '設問',
                'score' => 1,
                'choices' => [
                    ['body' => 'A', 'is_correct' => true],
                    ['body' => 'B', 'is_correct' => false],
                ],
            ]],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('closes_at', $validator->errors()->toArray());
    }

    /** 選択肢が1つしかないと失敗する */
    public function test_選択肢が1つだけだとエラー(): void
    {
        $curriculum = Curriculum::factory()->create();

        $validator = $this->makeValidator([
            'curriculum_id' => $curriculum->id,
            'title' => 'テスト',
            'questions' => [[
                'body' => '設問',
                'score' => 1,
                'choices' => [
                    ['body' => '唯一の選択肢', 'is_correct' => true],
                ],
            ]],
        ]);

        $this->assertTrue($validator->fails());
    }

    /** questions が空だと失敗する */
    public function test_questionsが空だとエラー(): void
    {
        $curriculum = Curriculum::factory()->create();

        $validator = $this->makeValidator([
            'curriculum_id' => $curriculum->id,
            'title' => 'テスト',
            'questions' => [],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('questions', $validator->errors()->toArray());
    }

    /** score が0以下だと失敗する */
    public function test_scoreが0以下だとエラー(): void
    {
        $curriculum = Curriculum::factory()->create();

        $validator = $this->makeValidator([
            'curriculum_id' => $curriculum->id,
            'title' => 'テスト',
            'questions' => [[
                'body' => '設問',
                'score' => 0,
                'choices' => [
                    ['body' => 'A', 'is_correct' => true],
                    ['body' => 'B', 'is_correct' => false],
                ],
            ]],
        ]);

        $this->assertTrue($validator->fails());
    }

    private function makeValidator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, (new StoreTestRequest())->rules());
    }
}
