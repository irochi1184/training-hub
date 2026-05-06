<?php

namespace App\Http\Controllers;

use App\Enums\CurriculumInstructorRole;
use App\Enums\UserRole;
use App\Http\Requests\StoreCurriculumRequest;
use App\Http\Requests\UpdateCurriculumRequest;
use App\Models\Curriculum;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CurriculumController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Curriculum::class);

        $curricula = Curriculum::with(['mainInstructors:id,name', 'subInstructors:id,name'])
            ->withCount(['enrollments', 'tests'])
            ->orderByDesc('starts_on')
            ->paginate(20);

        return Inertia::render('Curricula/Index', ['curricula' => $curricula]);
    }

    public function create(): Response
    {
        $this->authorize('create', Curriculum::class);

        return Inertia::render('Curricula/Create', [
            'instructors' => $this->instructorOptions(),
        ]);
    }

    public function store(StoreCurriculumRequest $request): RedirectResponse
    {
        $this->authorize('create', Curriculum::class);

        $validated = $request->validated();

        $curriculum = Curriculum::create([
            'organization_id' => $request->user()->organization_id,
            'name' => $validated['name'],
            'starts_on' => $validated['starts_on'],
            'ends_on' => $validated['ends_on'],
        ]);

        $this->syncInstructors($curriculum, $validated);

        return redirect()->route('curricula.index')->with('success', 'カリキュラムを作成しました');
    }

    public function edit(Curriculum $curriculum): Response
    {
        $this->authorize('update', $curriculum);

        $curriculum->load(['mainInstructors:id,name', 'subInstructors:id,name']);

        return Inertia::render('Curricula/Edit', [
            'curriculum' => $curriculum,
            'instructors' => $this->instructorOptions(),
        ]);
    }

    public function update(UpdateCurriculumRequest $request, Curriculum $curriculum): RedirectResponse
    {
        $this->authorize('update', $curriculum);

        $validated = $request->validated();

        $curriculum->update([
            'name' => $validated['name'],
            'starts_on' => $validated['starts_on'],
            'ends_on' => $validated['ends_on'],
        ]);

        $this->syncInstructors($curriculum, $validated);

        return redirect()->route('curricula.index')->with('success', 'カリキュラムを更新しました');
    }

    public function destroy(Curriculum $curriculum): RedirectResponse
    {
        $this->authorize('delete', $curriculum);

        $curriculum->delete();

        return redirect()->route('curricula.index')->with('success', 'カリキュラムを削除しました');
    }

    private function syncInstructors(Curriculum $curriculum, array $validated): void
    {
        $syncData = [];

        foreach ($validated['main_instructor_ids'] ?? [] as $id) {
            $syncData[$id] = ['role' => CurriculumInstructorRole::Main->value];
        }

        foreach ($validated['sub_instructor_ids'] ?? [] as $id) {
            $syncData[$id] = ['role' => CurriculumInstructorRole::Sub->value];
        }

        $curriculum->instructors()->sync($syncData);
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function instructorOptions(): \Illuminate\Support\Collection
    {
        return User::where('role', UserRole::Instructor)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
