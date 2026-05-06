<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\DailyReportCommentController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\RiskAlertController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

// 認証
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// 認証済みルート
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 受講生管理
    Route::middleware('role:admin,instructor')->group(function () {
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/{user}', [StudentController::class, 'show'])->name('students.show');
    });

    // エンロールメント管理
    Route::middleware('role:admin,instructor')->group(function () {
        Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
        Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
        Route::post('/enrollments/bulk', [EnrollmentController::class, 'bulkStore'])->name('enrollments.bulk-store');
        Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
    });

    // 日報（管理者・講師向け一覧）
    Route::middleware('role:admin,instructor')->group(function () {
        Route::get('/daily-reports', [DailyReportController::class, 'index'])->name('daily-reports.index');
    });

    // プロフィール（受講生）
    Route::middleware('role:student')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    // 日報（受講生向け提出）
    Route::middleware('role:student')->group(function () {
        Route::get('/daily-reports/create', [DailyReportController::class, 'create'])->name('daily-reports.create');
        Route::post('/daily-reports', [DailyReportController::class, 'store'])->name('daily-reports.store');
    });

    // 日報詳細（全ロール）
    Route::get('/daily-reports/{report}', [DailyReportController::class, 'show'])->name('daily-reports.show');

    // 講師コメント
    Route::middleware('role:admin,instructor')->group(function () {
        Route::post('/daily-reports/{report}/comments', [DailyReportCommentController::class, 'store'])
            ->name('daily-reports.comments.store');
        Route::delete('/daily-reports/{report}/comments/{comment}', [DailyReportCommentController::class, 'destroy'])
            ->name('daily-reports.comments.destroy');
    });

    // テスト
    Route::get('/tests', [TestController::class, 'index'])->name('tests.index');

    Route::middleware('role:admin,instructor')->group(function () {
        Route::get('/tests/create', [TestController::class, 'create'])->name('tests.create');
        Route::post('/tests', [TestController::class, 'store'])->name('tests.store');
        Route::get('/tests/{test}/analytics', [TestController::class, 'show'])->name('tests.show');
        Route::get('/tests/{test}/edit', [TestController::class, 'edit'])->name('tests.edit');
        Route::put('/tests/{test}', [TestController::class, 'update'])->name('tests.update');
        Route::delete('/tests/{test}', [TestController::class, 'destroy'])->name('tests.destroy');
    });

    // テスト受験
    Route::middleware('role:student')->group(function () {
        Route::get('/tests/{test}/take', [SubmissionController::class, 'create'])->name('tests.take');
        Route::post('/tests/{test}/submissions', [SubmissionController::class, 'store'])->name('tests.submissions.store');
    });

    // 受験結果
    Route::get('/submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');

    // 要注意者
    Route::middleware('role:admin,instructor')->group(function () {
        Route::get('/risk-alerts', [RiskAlertController::class, 'index'])->name('risk-alerts.index');
        Route::patch('/risk-alerts/{alert}/resolve', [RiskAlertController::class, 'resolve'])->name('risk-alerts.resolve');
    });

    // CSV出力
    Route::middleware('role:admin')->group(function () {
        Route::get('/exports', [ExportController::class, 'index'])->name('exports.index');
        Route::get('/exports/daily-reports', [ExportController::class, 'dailyReports'])->name('exports.daily-reports');
        Route::get('/exports/test-results', [ExportController::class, 'testResults'])->name('exports.test-results');
    });

    // お知らせ
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');

    Route::middleware('role:admin,instructor')->group(function () {
        Route::get('/announcements-create', [AnnouncementController::class, 'create'])->name('announcements.create');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::get('/announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
        Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    });

    // カリキュラム管理（admin のみ）
    Route::middleware('role:admin')->group(function () {
        Route::get('/curricula', [CurriculumController::class, 'index'])->name('curricula.index');
        Route::get('/curricula/create', [CurriculumController::class, 'create'])->name('curricula.create');
        Route::post('/curricula', [CurriculumController::class, 'store'])->name('curricula.store');
        Route::get('/curricula/{curriculum}/edit', [CurriculumController::class, 'edit'])->name('curricula.edit');
        Route::put('/curricula/{curriculum}', [CurriculumController::class, 'update'])->name('curricula.update');
        Route::delete('/curricula/{curriculum}', [CurriculumController::class, 'destroy'])->name('curricula.destroy');
    });
});
