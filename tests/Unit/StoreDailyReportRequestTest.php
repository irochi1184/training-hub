<?php

namespace Tests\Unit;

use App\Http\Requests\StoreDailyReportRequest;
use App\Models\Cohort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreDailyReportRequestTest extends TestCase
{
    use RefreshDatabase;

    /** 理解度レベル 1〜5 は通る */
    public function test_理解度が1から5の範囲内ならOK(): void
    {
        $cohort = Cohort::factory()->create();

        foreach ([1, 3, 5] as $level) {
            $validator = $this->makeValidator([
                'cohort_id' => $cohort->id,
                'reported_on' => '2026-04-22',
                'understanding_level' => $level,
                'content' => '本日の学習内容',
            ]);

            $this->assertTrue(
                $validator->passes(),
                "理解度 {$level} で通るはずだがエラー: "
                . json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE),
            );
        }
    }

    /** 理解度 0 や 6 はエラー */
    public function test_理解度が範囲外だとエラー(): void
    {
        $cohort = Cohort::factory()->create();

        foreach ([0, 6, -1] as $level) {
            $validator = $this->makeValidator([
                'cohort_id' => $cohort->id,
                'reported_on' => '2026-04-22',
                'understanding_level' => $level,
                'content' => '本日の学習内容',
            ]);

            $this->assertTrue(
                $validator->fails(),
                "理解度 {$level} はエラーになるべき",
            );
            $this->assertArrayHasKey('understanding_level', $validator->errors()->toArray());
        }
    }

    /** content が空だとエラー */
    public function test_contentが空だとエラー(): void
    {
        $cohort = Cohort::factory()->create();

        $validator = $this->makeValidator([
            'cohort_id' => $cohort->id,
            'reported_on' => '2026-04-22',
            'understanding_level' => 3,
            'content' => '',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('content', $validator->errors()->toArray());
    }

    /** reported_on が日付形式でないとエラー */
    public function test_reported_onが日付でないとエラー(): void
    {
        $cohort = Cohort::factory()->create();

        $validator = $this->makeValidator([
            'cohort_id' => $cohort->id,
            'reported_on' => 'not-a-date',
            'understanding_level' => 3,
            'content' => '内容',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('reported_on', $validator->errors()->toArray());
    }

    private function makeValidator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, (new StoreDailyReportRequest())->rules());
    }
}
