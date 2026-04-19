<?php

namespace App\Http\Controllers;

use App\Models\Cohort;
use App\Models\Test;
use App\Services\CsvExportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(private readonly CsvExportService $csvExportService) {}

    public function index(): Response
    {
        $cohorts = Cohort::with('course')->orderBy('name')->get();
        $tests = Test::with('cohort')->orderByDesc('created_at')->get();

        return Inertia::render('Exports/Index', [
            'cohorts' => $cohorts,
            'tests' => $tests,
        ]);
    }

    public function dailyReports(Request $request): StreamedResponse
    {
        $request->validate([
            'cohort_id' => ['required', 'integer', 'exists:cohorts,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        return $this->csvExportService->exportDailyReports(
            cohortId: (int) $request->input('cohort_id'),
            dateFrom: $request->input('date_from'),
            dateTo: $request->input('date_to'),
        );
    }

    public function testResults(Request $request): StreamedResponse
    {
        $request->validate([
            'test_id' => ['required', 'integer', 'exists:tests,id'],
        ]);

        return $this->csvExportService->exportTestResults(
            testId: (int) $request->input('test_id'),
        );
    }
}
