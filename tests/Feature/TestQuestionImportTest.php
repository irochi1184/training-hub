<?php

namespace Tests\Feature;

use App\Models\Curriculum;
use App\Models\Test;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TestQuestionImportTest extends TestCase
{
    use RefreshDatabase;

    private function createTestWithInstructor(): array
    {
        $instructor = User::factory()->instructor()->create();
        $curriculum = Curriculum::factory()->create();
        $curriculum->instructors()->syncWithoutDetaching([$instructor->id => ['role' => 'main']]);
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id, 'created_by' => $instructor->id]);

        return [$instructor, $test];
    }

    private function makeCsvFile(string $content, string $filename = 'questions.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($filename, $content);
    }

    public function test_CSVで問題を一括インポートできる(): void
    {
        [$instructor, $test] = $this->createTestWithInstructor();

        $csv = "問題文,問題タイプ,配点,選択肢1,正解1,選択肢2,正解2,選択肢3,正解3,選択肢4,正解4\n";
        $csv .= "PHPの変数は何で始まる？,single,10,\$,1,#,0,@,0,&,0\n";
        $csv .= "配列関数はどれ？,multiple,5,array_push,1,strlen,0,array_pop,1,substr,0\n";

        $response = $this->actingAs($instructor)->post(
            "/tests/{$test->id}/import",
            ['csv_file' => $this->makeCsvFile($csv)]
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'imported' => 2]);

        $this->assertDatabaseCount('questions', 2);
        $this->assertDatabaseHas('questions', [
            'test_id' => $test->id,
            'body' => 'PHPの変数は何で始まる？',
            'question_type' => 'single',
            'score' => 10,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('questions', [
            'test_id' => $test->id,
            'body' => '配列関数はどれ？',
            'question_type' => 'multiple',
            'score' => 5,
            'position' => 2,
        ]);

        // 選択肢も確認
        $this->assertDatabaseCount('choices', 8);
    }

    public function test_既存問題がある場合は追加される(): void
    {
        [$instructor, $test] = $this->createTestWithInstructor();

        // 既存問題を1つ作成
        $test->questions()->create([
            'body' => '既存問題',
            'question_type' => 'single',
            'position' => 1,
            'score' => 10,
        ]);

        $csv = "問題文,問題タイプ,配点,選択肢1,正解1,選択肢2,正解2\n";
        $csv .= "新しい問題,single,5,正解,1,不正解,0\n";

        $response = $this->actingAs($instructor)->post(
            "/tests/{$test->id}/import",
            ['csv_file' => $this->makeCsvFile($csv)]
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'imported' => 1]);

        // 既存1 + インポート1 = 2問
        $this->assertDatabaseCount('questions', 2);
        // 新しい問題のpositionは2
        $this->assertDatabaseHas('questions', [
            'test_id' => $test->id,
            'body' => '新しい問題',
            'position' => 2,
        ]);
    }

    public function test_問題文が空だとエラーになる(): void
    {
        [$instructor, $test] = $this->createTestWithInstructor();

        $csv = "問題文,問題タイプ,配点,選択肢1,正解1,選択肢2,正解2\n";
        $csv .= ",single,10,A,1,B,0\n";

        $response = $this->actingAs($instructor)->post(
            "/tests/{$test->id}/import",
            ['csv_file' => $this->makeCsvFile($csv)]
        );

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_正解がない問題はエラーになる(): void
    {
        [$instructor, $test] = $this->createTestWithInstructor();

        $csv = "問題文,問題タイプ,配点,選択肢1,正解1,選択肢2,正解2\n";
        $csv .= "テスト問題,single,10,A,0,B,0\n";

        $response = $this->actingAs($instructor)->post(
            "/tests/{$test->id}/import",
            ['csv_file' => $this->makeCsvFile($csv)]
        );

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_不正な問題タイプはエラーになる(): void
    {
        [$instructor, $test] = $this->createTestWithInstructor();

        $csv = "問題文,問題タイプ,配点,選択肢1,正解1,選択肢2,正解2\n";
        $csv .= "テスト問題,essay,10,A,1,B,0\n";

        $response = $this->actingAs($instructor)->post(
            "/tests/{$test->id}/import",
            ['csv_file' => $this->makeCsvFile($csv)]
        );

        $response->assertStatus(422);
    }

    public function test_列数不足はエラーになる(): void
    {
        [$instructor, $test] = $this->createTestWithInstructor();

        $csv = "問題文,問題タイプ,配点\n";
        $csv .= "テスト問題,single,10\n";

        $response = $this->actingAs($instructor)->post(
            "/tests/{$test->id}/import",
            ['csv_file' => $this->makeCsvFile($csv)]
        );

        $response->assertStatus(422);
    }

    public function test_studentはインポートできない(): void
    {
        $student = User::factory()->student()->create();
        $curriculum = Curriculum::factory()->create();
        $test = Test::factory()->create(['curriculum_id' => $curriculum->id]);

        $csv = "問題文,問題タイプ,配点,選択肢1,正解1,選択肢2,正解2\n";
        $csv .= "テスト問題,single,10,A,1,B,0\n";

        $response = $this->actingAs($student)->post(
            "/tests/{$test->id}/import",
            ['csv_file' => $this->makeCsvFile($csv)]
        );

        $response->assertStatus(403);
    }

    public function test_BOM付きCSVもパースできる(): void
    {
        [$instructor, $test] = $this->createTestWithInstructor();

        $csv = "\xEF\xBB\xBF問題文,問題タイプ,配点,選択肢1,正解1,選択肢2,正解2\n";
        $csv .= "BOMテスト,single,10,A,1,B,0\n";

        $response = $this->actingAs($instructor)->post(
            "/tests/{$test->id}/import",
            ['csv_file' => $this->makeCsvFile($csv)]
        );

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'imported' => 1]);
        $this->assertDatabaseHas('questions', ['body' => 'BOMテスト']);
    }
}
