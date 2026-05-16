<?php

namespace App\Http\Controllers;

use App\Enums\NotificationEventType;
use App\Http\Requests\StoreDailyReportCommentRequest;
use App\Models\DailyReport;
use App\Models\DailyReportComment;
use App\Notifications\CommentAddedNotification;
use App\Services\SlackNotificationService;
use Illuminate\Http\RedirectResponse;

class DailyReportCommentController extends Controller
{
    public function store(StoreDailyReportCommentRequest $request, DailyReport $report, SlackNotificationService $slack): RedirectResponse
    {
        $this->authorize('create', [DailyReportComment::class, $report]);

        $report->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        // コメント追加通知を非同期送信
        $report->loadMissing('user');
        $instructor = $request->user();
        $org = $instructor->organization;
        $slack->send(
            $org,
            NotificationEventType::CommentAdded,
            (new CommentAddedNotification($report, $instructor))->toSlackPayload(),
        );

        return back()->with('success', 'コメントを追加しました');
    }

    public function destroy(DailyReport $report, DailyReportComment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        if ($comment->daily_report_id !== $report->id) {
            abort(404);
        }

        $comment->delete();

        return back()->with('success', 'コメントを削除しました');
    }
}
