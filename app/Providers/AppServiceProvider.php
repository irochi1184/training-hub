<?php

namespace App\Providers;

use App\Models\Curriculum;
use App\Models\DailyReport;
use App\Models\DailyReportComment;
use App\Models\RiskAlert;
use App\Models\Submission;
use App\Models\Test;
use App\Models\User;
use App\Policies\CurriculumPolicy;
use App\Policies\DailyReportCommentPolicy;
use App\Policies\DailyReportPolicy;
use App\Policies\ExportPolicy;
use App\Policies\RiskAlertPolicy;
use App\Policies\SubmissionPolicy;
use App\Policies\TestPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Curriculum::class, CurriculumPolicy::class);
        Gate::policy(DailyReport::class, DailyReportPolicy::class);
        Gate::policy(DailyReportComment::class, DailyReportCommentPolicy::class);
        Gate::policy(Test::class, TestPolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);
        Gate::policy(RiskAlert::class, RiskAlertPolicy::class);

        $exportPolicy = new ExportPolicy();
        Gate::define('viewAny-export', fn (User $user) => $exportPolicy->viewAny($user));
        Gate::define('exportDailyReports', fn (User $user) => $exportPolicy->exportDailyReports($user));
        Gate::define('exportTestResults', fn (User $user) => $exportPolicy->exportTestResults($user));
    }
}
