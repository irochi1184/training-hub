<?php

namespace App\Actions;

use App\Models\Test;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ImportTestQuestions
{
    private const VALID_QUESTION_TYPES = ['single', 'multiple'];
    private const MIN_CHOICES = 2;
    private const MAX_CHOICES = 8;

    /**
     * @return array{imported: int, errors: array<int, string>}
     */
    public function execute(Test $test, UploadedFile $file): array
    {
        $rows = $this->parseCsv($file);
        $errors = [];
        $questions = [];

        foreach ($rows as $index => $row) {
            $lineNum = $index + 2; // ヘッダー行 + 0始まり補正
            $result = $this->validateRow($row, $lineNum);

            if ($result['error']) {
                $errors[] = $result['error'];
                continue;
            }

            $questions[] = $result['data'];
        }

        if (!empty($errors)) {
            return ['imported' => 0, 'errors' => $errors];
        }

        $startPosition = $test->questions()->max('position') ?? 0;

        DB::transaction(function () use ($test, $questions, $startPosition) {
            foreach ($questions as $i => $data) {
                $question = $test->questions()->create([
                    'body' => $data['body'],
                    'question_type' => $data['question_type'],
                    'position' => $startPosition + $i + 1,
                    'score' => $data['score'],
                ]);

                foreach ($data['choices'] as $ci => $choice) {
                    $question->choices()->create([
                        'body' => $choice['body'],
                        'is_correct' => $choice['is_correct'],
                        'position' => $ci + 1,
                    ]);
                }
            }
        });

        return ['imported' => count($questions), 'errors' => []];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsv(UploadedFile $file): array
    {
        $content = $file->getContent();
        // BOM除去
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $lines = str_getcsv($content, "\n");
        $rows = [];

        foreach ($lines as $i => $line) {
            if ($i === 0) {
                continue; // ヘッダー行スキップ
            }
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $rows[] = str_getcsv($line);
        }

        return $rows;
    }

    /**
     * @return array{data?: array, error?: string}
     */
    private function validateRow(array $row, int $lineNum): array
    {
        // 最低限: 問題文, 問題タイプ, 配点, 選択肢1, 正解1, 選択肢2, 正解2 = 7列
        if (count($row) < 7) {
            return ['data' => null, 'error' => "{$lineNum}行目: 列数が不足しています（最低7列必要）"];
        }

        $body = trim($row[0] ?? '');
        $questionType = trim($row[1] ?? '');
        $score = trim($row[2] ?? '');

        if ($body === '') {
            return ['data' => null, 'error' => "{$lineNum}行目: 問題文が空です"];
        }

        if (!in_array($questionType, self::VALID_QUESTION_TYPES, true)) {
            return ['data' => null, 'error' => "{$lineNum}行目: 問題タイプは single または multiple を指定してください（値: {$questionType}）"];
        }

        if (!is_numeric($score) || (int) $score <= 0) {
            return ['data' => null, 'error' => "{$lineNum}行目: 配点は1以上の数値を指定してください（値: {$score}）"];
        }

        // 選択肢のパース（3列目以降、2列ずつ）
        $choices = [];
        $hasCorrect = false;
        for ($i = 3; $i < count($row) - 1; $i += 2) {
            $choiceBody = trim($row[$i] ?? '');
            $isCorrect = trim($row[$i + 1] ?? '');

            if ($choiceBody === '') {
                continue;
            }

            if (!in_array($isCorrect, ['0', '1'], true)) {
                return ['data' => null, 'error' => "{$lineNum}行目: 正解フラグは 0 または 1 を指定してください（選択肢「{$choiceBody}」の値: {$isCorrect}）"];
            }

            $correct = $isCorrect === '1';
            if ($correct) {
                $hasCorrect = true;
            }

            $choices[] = ['body' => $choiceBody, 'is_correct' => $correct];
        }

        if (count($choices) < self::MIN_CHOICES) {
            return ['data' => null, 'error' => "{$lineNum}行目: 選択肢は最低" . self::MIN_CHOICES . "つ必要です"];
        }

        if (count($choices) > self::MAX_CHOICES) {
            return ['data' => null, 'error' => "{$lineNum}行目: 選択肢は最大" . self::MAX_CHOICES . "つまでです"];
        }

        if (!$hasCorrect) {
            return ['data' => null, 'error' => "{$lineNum}行目: 正解の選択肢が1つもありません"];
        }

        return [
            'error' => null,
            'data' => [
                'body' => $body,
                'question_type' => $questionType,
                'score' => (int) $score,
                'choices' => $choices,
            ],
        ];
    }
}
