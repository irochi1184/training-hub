<?php

namespace App\Http\Middleware;

use App\Enums\AnnouncementTargetType;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\RiskAlert;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'unread_announcement_count' => fn () => $this->getUnreadAnnouncementCount($request->user()),
            'risk_alert_count' => fn () => $this->getRiskAlertCount($request->user()),
        ];
    }

    private function getUnreadAnnouncementCount(?User $user): int
    {
        if (!$user) {
            return 0;
        }

        $query = Announcement::query()
            ->where('organization_id', $user->organization_id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', Carbon::now());

        // 対象絞り込み
        if ($user->isStudent()) {
            $curriculumIds = $user->enrollments()->pluck('curriculum_id');
            $query->where(function ($q) use ($user, $curriculumIds) {
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
        } elseif ($user->isInstructor()) {
            $curriculumIds = $user->instructedCurricula()->pluck('id');
            $query->where(function ($q) use ($user, $curriculumIds) {
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
        // admin は全件対象

        $readIds = AnnouncementRead::where('user_id', $user->id)->pluck('announcement_id');

        if ($readIds->isNotEmpty()) {
            $query->whereNotIn('id', $readIds);
        }

        return $query->count();
    }

    private function getRiskAlertCount(?User $user): int
    {
        if (!$user || $user->isStudent()) {
            return 0;
        }

        $query = RiskAlert::whereNull('resolved_at');

        if ($user->isInstructor()) {
            $curriculumIds = $user->instructedCurricula()->pluck('id');
            $query->whereIn('curriculum_id', $curriculumIds);
        }

        return $query->count();
    }
}
