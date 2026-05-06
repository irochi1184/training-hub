<?php

namespace App\Http\Controllers;

use App\Enums\AnnouncementTargetType;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Curriculum;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Announcement::class);

        $user = $request->user();

        $query = $this->buildQueryForUser($user);

        $announcements = $query
            ->with('creator:id,name')
            ->orderByDesc('published_at')
            ->paginate(20)
            ->withQueryString();

        $readIds = AnnouncementRead::where('user_id', $user->id)
            ->whereIn('announcement_id', $announcements->pluck('id'))
            ->pluck('announcement_id')
            ->all();

        return Inertia::render('Announcements/Index', [
            'announcements' => $announcements,
            'readIds' => $readIds,
        ]);
    }

    public function show(Request $request, Announcement $announcement): Response
    {
        $this->authorize('view', $announcement);

        $user = $request->user();

        $announcement->load('creator:id,name');

        // 既読マーク
        AnnouncementRead::firstOrCreate(
            ['announcement_id' => $announcement->id, 'user_id' => $user->id],
            ['read_at' => Carbon::now()],
        );

        return Inertia::render('Announcements/Show', [
            'announcement' => $announcement,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Announcement::class);

        $user = $request->user();

        return Inertia::render('Announcements/Create', [
            'curricula' => $this->getCurriculaForUser($user),
            'students' => $this->getStudentsForUser($user),
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $this->authorize('create', Announcement::class);

        $user = $request->user();
        $validated = $request->validated();

        Announcement::create([
            'organization_id' => $user->organization_id,
            'created_by' => $user->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'priority' => $validated['priority'],
            'target_type' => $validated['target_type'],
            'target_id' => $validated['target_type'] === 'all' ? null : $validated['target_id'],
            'published_at' => ($validated['publish_now'] ?? true) ? Carbon::now() : null,
        ]);

        return redirect()->route('announcements.index')
            ->with('success', 'お知らせを作成しました');
    }

    public function edit(Request $request, Announcement $announcement): Response
    {
        $this->authorize('update', $announcement);

        $user = $request->user();

        return Inertia::render('Announcements/Edit', [
            'announcement' => $announcement,
            'curricula' => $this->getCurriculaForUser($user),
            'students' => $this->getStudentsForUser($user),
        ]);
    }

    public function update(StoreAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $validated = $request->validated();

        $announcement->update([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'priority' => $validated['priority'],
            'target_type' => $validated['target_type'],
            'target_id' => $validated['target_type'] === 'all' ? null : $validated['target_id'],
            'published_at' => ($validated['publish_now'] ?? true)
                ? ($announcement->published_at ?? Carbon::now())
                : null,
        ]);

        return redirect()->route('announcements.index')
            ->with('success', 'お知らせを更新しました');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorize('delete', $announcement);

        $announcement->delete();

        return redirect()->route('announcements.index')
            ->with('success', 'お知らせを削除しました');
    }

    /**
     * ユーザーに表示すべきお知らせクエリを構築する
     */
    private function buildQueryForUser(User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = Announcement::query()
            ->where('organization_id', $user->organization_id);

        if ($user->isAdmin()) {
            // admin は全件（下書き含む）
            return $query;
        }

        if ($user->isInstructor()) {
            // instructor は自分作成 + 公開済みで自分宛
            $curriculumIds = $user->instructedCurricula()->pluck('curricula.id');

            return $query->where(function ($q) use ($user, $curriculumIds) {
                $q->where('created_by', $user->id)
                    ->orWhere(function ($q2) use ($user, $curriculumIds) {
                        $q2->whereNotNull('published_at')
                            ->where('published_at', '<=', Carbon::now())
                            ->where(function ($q3) use ($user, $curriculumIds) {
                                $q3->where('target_type', AnnouncementTargetType::All)
                                    ->orWhere(function ($q4) use ($curriculumIds) {
                                        $q4->where('target_type', AnnouncementTargetType::Curriculum)
                                            ->whereIn('target_id', $curriculumIds);
                                    })
                                    ->orWhere(function ($q4) use ($user) {
                                        $q4->where('target_type', AnnouncementTargetType::User)
                                            ->where('target_id', $user->id);
                                    });
                            });
                    });
            });
        }

        // student は公開済み＋自分宛のみ
        $curriculumIds = $user->enrollments()->pluck('curriculum_id');

        return $query
            ->whereNotNull('published_at')
            ->where('published_at', '<=', Carbon::now())
            ->where(function ($q) use ($user, $curriculumIds) {
                $q->where('target_type', AnnouncementTargetType::All)
                    ->orWhere(function ($q2) use ($curriculumIds) {
                        $q2->where('target_type', AnnouncementTargetType::Curriculum)
                            ->whereIn('target_id', $curriculumIds);
                    })
                    ->orWhere(function ($q2) use ($user) {
                        $q2->where('target_type', AnnouncementTargetType::User)
                            ->where('target_id', $user->id);
                    });
            });
    }

    /** @return \Illuminate\Support\Collection<int, array{id: int, name: string}> */
    private function getCurriculaForUser(User $user): \Illuminate\Support\Collection
    {
        $query = Curriculum::orderBy('name');

        if ($user->isInstructor()) {
            $query->whereHas('instructors', fn ($q) => $q->where('users.id', $user->id));
        }

        return $query->get(['id', 'name']);
    }

    /** @return \Illuminate\Support\Collection<int, array{id: int, name: string}> */
    private function getStudentsForUser(User $user): \Illuminate\Support\Collection
    {
        $query = User::where('role', 'student')->orderBy('name');

        if ($user->isInstructor()) {
            $curriculumIds = $user->instructedCurricula()->pluck('curricula.id');
            $query->whereHas('enrollments', fn ($q) => $q->whereIn('curriculum_id', $curriculumIds));
        }

        return $query->get(['id', 'name']);
    }
}
