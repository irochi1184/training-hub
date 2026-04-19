<?php

namespace Tests\Feature;

use App\Models\Cohort;
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
        $cohort = Cohort::factory()->create();

        $response = $this->actingAs($admin)->get('/exports/daily-reports?' . http_build_query([
            'cohort_id' => $cohort->id,
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
}
