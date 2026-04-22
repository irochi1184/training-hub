<?php

namespace Tests\Unit;

use App\Http\Requests\ExportDailyReportsRequest;
use App\Models\Curriculum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExportDailyReportsRequestTest extends TestCase
{
    use RefreshDatabase;

    /** 日付範囲なしでも通る */
    public function test_日付範囲未指定でも通る(): void
    {
        $curriculum = Curriculum::factory()->create();

        $validator = $this->makeValidator([
            'curriculum_id' => $curriculum->id,
        ]);

        $this->assertTrue($validator->passes());
    }

    /** date_to が date_from より前だとエラー */
    public function test_date_toがdate_fromより前だとエラー(): void
    {
        $curriculum = Curriculum::factory()->create();

        $validator = $this->makeValidator([
            'curriculum_id' => $curriculum->id,
            'date_from' => '2026-05-01',
            'date_to' => '2026-04-30',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date_to', $validator->errors()->toArray());
    }

    /** date_to == date_from は通る（after_or_equal） */
    public function test_date_toとdate_fromが同日なら通る(): void
    {
        $curriculum = Curriculum::factory()->create();

        $validator = $this->makeValidator([
            'curriculum_id' => $curriculum->id,
            'date_from' => '2026-04-22',
            'date_to' => '2026-04-22',
        ]);

        $this->assertTrue($validator->passes());
    }

    /** curriculum_id が存在しないとエラー */
    public function test_curriculum_idが存在しないとエラー(): void
    {
        $validator = $this->makeValidator([
            'curriculum_id' => 99999,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('curriculum_id', $validator->errors()->toArray());
    }

    private function makeValidator(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($data, (new ExportDailyReportsRequest())->rules());
    }
}
