<?php

namespace App\Http\Controllers;

use App\Enums\SummaryType;
use App\Models\AiSummary;
use App\Models\Curriculum;
use App\Models\RiskAlert;
use App\Models\User;
use App\Services\AiSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AiSummaryController extends Controller
{
    public function __construct(private AiSummaryService $service)
    {
    }

    /**
     * ダッシュボード用に最新のサマリー一覧を返す
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AiSummary::class);

        $user = $request->user();

        $query = AiSummary::with('summarizable')
            ->where('organization_id', $user->organization_id)
            ->orderByDesc('created_at');

        if ($request->filled('summary_type')) {
            $type = SummaryType::tryFrom($request->input('summary_type'));
            if ($type) {
                $query->byType($type);
            }
        }

        if ($request->filled('week_start')) {
            $weekStart = Carbon::parse($request->input('week_start'));
            $query->forWeek($weekStart);
        }

        $summaries = $query->paginate(20)->withQueryString();

        return Inertia::render('AiSummaries/Index', [
            'summaries' => $summaries,
            'summaryTypes' => array_map(
                fn (SummaryType $t) => ['value' => $t->value, 'label' => $t->label()],
                SummaryType::cases()
            ),
            'filters' => $request->only(['summary_type', 'week_start']),
        ]);
    }

    /**
     * 個別サマリーを表示する
     */
    public function show(AiSummary $aiSummary): Response
    {
        $this->authorize('view', $aiSummary);

        $aiSummary->load('summarizable');

        return Inertia::render('AiSummaries/Show', [
            'summary' => $aiSummary,
        ]);
    }

    /**
     * 手動でAI要約を生成する（admin/instructor のみ）
     */
    public function generate(Request $request): RedirectResponse
    {
        $this->authorize('generate', AiSummary::class);

        $validated = $request->validate([
            'summary_type' => ['required', 'string', 'in:' . implode(',', array_column(SummaryType::cases(), 'value'))],
            'target_id' => ['required', 'integer'],
            'week_start' => ['nullable', 'date', 'required_if:summary_type,weekly_student,weekly_class'],
        ]);

        $summaryType = SummaryType::from($validated['summary_type']);
        $weekStart = isset($validated['week_start'])
            ? Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY)
            : null;

        $summary = match ($summaryType) {
            SummaryType::WeeklyStudent => $this->generateStudentSummary($validated['target_id'], $weekStart),
            SummaryType::WeeklyClass => $this->generateClassSummary($validated['target_id'], $weekStart),
            SummaryType::RiskExplanation => $this->generateRiskSummary($validated['target_id']),
        };

        if ($summary === null) {
            return back()->withErrors(['error' => 'AI要約の生成に失敗しました。APIキーの設定または対象データを確認してください。']);
        }

        return back()->with('success', 'AI要約を生成しました');
    }

    private function generateStudentSummary(int $studentId, ?Carbon $weekStart): ?AiSummary
    {
        $student = User::findOrFail($studentId);
        return $this->service->generateWeeklyStudentSummary($student, $weekStart ?? Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY));
    }

    private function generateClassSummary(int $curriculumId, ?Carbon $weekStart): ?AiSummary
    {
        $curriculum = Curriculum::findOrFail($curriculumId);
        return $this->service->generateWeeklyClassSummary($curriculum, $weekStart ?? Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY));
    }

    private function generateRiskSummary(int $alertId): ?AiSummary
    {
        $alert = RiskAlert::findOrFail($alertId);
        return $this->service->generateRiskExplanation($alert);
    }
}
