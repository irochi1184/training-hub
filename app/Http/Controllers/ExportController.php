<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportDailyReportsRequest;
use App\Http\Requests\ExportTestResultsRequest;
use App\Models\Cohort;
use App\Models\Test;
use App\Services\CsvExportService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(private readonly CsvExportService $csvExportService) {}

    public function index(): Response
    {
        Gate::authorize('viewAny-export');

        $cohorts = Cohort::with('course')->orderBy('name')->get();
        $tests = Test::with('cohort')->orderByDesc('created_at')->get();

        return Inertia::render('Exports/Index', [
            'cohorts' => $cohorts,
            'tests' => $tests,
        ]);
    }

    public function dailyReports(ExportDailyReportsRequest $request): StreamedResponse
    {
        Gate::authorize('exportDailyReports');

        return $this->csvExportService->exportDailyReports(
            cohortId: (int) $request->validated('cohort_id'),
            dateFrom: $request->validated('date_from'),
            dateTo: $request->validated('date_to'),
        );
    }

    public function testResults(ExportTestResultsRequest $request): StreamedResponse
    {
        Gate::authorize('exportTestResults');

        return $this->csvExportService->exportTestResults(
            testId: (int) $request->validated('test_id'),
        );
    }
}
