<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $user = $request->user();

        $query = User::with(['enrollments.cohort'])
            ->where('role', UserRole::Student->value);

        if ($user->isInstructor()) {
            $cohortIds = $user->instructedCohorts()->pluck('id');
            $query->whereHas('enrollments', fn ($q) => $q->whereIn('cohort_id', $cohortIds));
        }

        $students = $query->orderBy('name')->paginate(30);

        return Inertia::render('Students/Index', ['students' => $students]);
    }

    public function show(Request $request, User $user): Response
    {
        $this->authorize('view', $user);

        $user->load([
            'enrollments.cohort',
            'dailyReports' => fn ($q) => $q->orderByDesc('reported_on')->limit(30),
            'submissions' => fn ($q) => $q->with('test')->whereNotNull('submitted_at')->orderByDesc('submitted_at'),
        ]);

        return Inertia::render('Students/Show', ['student' => $user]);
    }
}
