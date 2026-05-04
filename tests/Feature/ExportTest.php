<?php

namespace Tests\Feature;

use App\Models\Curriculum;
use App\Models\DailyReport;
use App\Models\Submission;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    /** admin が日報 CSV をダウンロードできる */
    public function test_adminが日報CSVをダウンロードできる(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create();

        $response = $this->actingAs($admin)->get('/exports/daily-reports?' . http_build_query([
            'curriculum_id' => $curriculum->id,
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    /** admin がテスト結果 CSV をダウンロードできる */
    public function test_adminがテスト結果CSVをダウンロードできる(): void
    {
        $admin = User::factory()->admin()->create();
        $test = Test::factory()->create();

        $response = $this->actingAs($admin)->get('/exports/test-results?' . http_build_query([
            'test_id' => $test->id,
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    /** instructor が CSV にアクセスすると 403 */
    public function test_instructorがCSVにアクセスすると403(): void
    {
        $instructor = User::factory()->instructor()->create();

        $response = $this->actingAs($instructor)->get('/exports');

        $response->assertStatus(403);
    }

    /** 日報 CSV の先頭に BOM があり、ヘッダー行に「日付」「受講生名」が含まれる */
    public function test_日報CSVにBOMとヘッダー行が含まれる(): void
    {
        $admin = User::factory()->admin()->create();
        $curriculum = Curriculum::factory()->create();

        $response = $this->actingAs($admin)->get('/exports/daily-reports?' . http_build_query([
            'curriculum_id' => $curriculum->id,
        ]));

        $response->assertStatus(200);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // UTF-8 BOM (0xEF 0xBB 0xBF) が先頭にある
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        // ヘッダー行に必須カラムが含まれる
        $this->assertStringContainsString('日付', $content);
        $this->assertStringContainsString('受講生名', $content);
    }

    /** DailyReport を作成してダウンロードすると、レポートデータが CSV に含まれる */
    public function test_日報CSVにレポートデータが含まれる(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create(['name' => 'テスト太郎']);
        $curriculum = Curriculum::factory()->create();
        DailyReport::factory()->create([
            'user_id' => $student->id,
            'curriculum_id' => $curriculum->id,
            'reported_on' => '2026-01-15',
            'content' => 'Laravelを学んだ',
        ]);

        $response = $this->actingAs($admin)->get('/exports/daily-reports?' . http_build_query([
            'curriculum_id' => $curriculum->id,
        ]));

        $response->assertStatus(200);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('テスト太郎', $content);
        $this->assertStringContainsString('2026-01-15', $content);
    }

    /** student が CSV ページにアクセスすると 403 */
    public function test_studentがCSVにアクセスすると403(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/exports');

        $response->assertStatus(403);
    }

    /** Submission を作成してテスト結果 CSV をダウンロードすると、受験者名が含まれる */
    public function test_テスト結果CSVに受験データが含まれる(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create(['name' => '受験花子']);
        $test = Test::factory()->create();
        Submission::factory()->submitted()->create([
            'test_id' => $test->id,
            'user_id' => $student->id,
        ]);

        $response = $this->actingAs($admin)->get('/exports/test-results?' . http_build_query([
            'test_id' => $test->id,
        ]));

        $response->assertStatus(200);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('受験花子', $content);
    }
}
