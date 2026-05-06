<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $curriculaQuery = Curriculum::withCount('enrollments')->orderBy('name');
        if ($user->isInstructor()) {
            $curriculaQuery->where('instructor_id', $user->id);
        }
        $curricula = $curriculaQuery->get();

        $selectedCurriculumId = $request->input('curriculum_id', $curricula->first()?->id);

        $enrollments = collect();
        $availableStudents = collect();

        if ($selectedCurriculumId) {
            $curriculum = $curricula->firstWhere('id', (int) $selectedCurriculumId);

            if ($curriculum) {
                $enrollments = Enrollment::with('user:id,name,email')
                    ->where('curriculum_id', $selectedCurriculumId)
                    ->orderBy('enrolled_at', 'desc')
                    ->get();

                $enrolledUserIds = $enrollments->pluck('user_id')->all();

                $availableStudents = User::where('role', UserRole::Student->value)
                    ->whereNotIn('id', $enrolledUserIds)
                    ->orderBy('name')
                    ->get(['id', 'name', 'email']);
            }
        }

        return Inertia::render('Enrollments/Index', [
            'curricula' => $curricula,
            'selectedCurriculumId' => (int) $selectedCurriculumId,
            'enrollments' => $enrollments,
            'availableStudents' => $availableStudents,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'curriculum_id' => ['required', 'integer', 'exists:curricula,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $this->authorizeCurriculum($request->user(), $validated['curriculum_id']);

        Enrollment::firstOrCreate(
            ['curriculum_id' => $validated['curriculum_id'], 'user_id' => $validated['user_id']],
            ['enrolled_at' => today()],
        );

        return back()->with('success', '受講生を登録しました');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'curriculum_id' => ['required', 'integer', 'exists:curricula,id'],
            'emails' => ['required', 'string'],
        ]);

        $this->authorizeCurriculum($request->user(), $validated['curriculum_id']);

        $emails = array_filter(
            array_map('trim', preg_split('/[\r\n,]+/', $validated['emails'])),
            fn ($e) => $e !== '',
        );

        $students = User::where('role', UserRole::Student->value)
            ->whereIn('email', $emails)
            ->get();

        $count = 0;
        foreach ($students as $student) {
            $created = Enrollment::firstOrCreate(
                ['curriculum_id' => $validated['curriculum_id'], 'user_id' => $student->id],
                ['enrolled_at' => today()],
            );
            if ($created->wasRecentlyCreated) {
                $count++;
            }
        }

        $notFound = count($emails) - $students->count();
        $message = "{$count} 名を登録しました";
        if ($notFound > 0) {
            $message .= "（{$notFound} 件のメールアドレスが見つかりませんでした）";
        }

        return back()->with('success', $message);
    }

    public function destroy(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $this->authorizeCurriculum($request->user(), $enrollment->curriculum_id);

        $enrollment->delete();

        return back()->with('success', '受講登録を解除しました');
    }

    private function authorizeCurriculum(User $user, int $curriculumId): void
    {
        if ($user->isAdmin()) {
            return;
        }

        $curriculum = Curriculum::findOrFail($curriculumId);
        if ($curriculum->instructor_id !== $user->id) {
            abort(403);
        }
    }
}
