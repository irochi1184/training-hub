<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyReportCommentRequest;
use App\Models\DailyReport;
use App\Models\DailyReportComment;
use Illuminate\Http\RedirectResponse;

class DailyReportCommentController extends Controller
{
    public function store(StoreDailyReportCommentRequest $request, DailyReport $report): RedirectResponse
    {
        $this->authorize('create', [DailyReportComment::class, $report]);

        $report->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

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
