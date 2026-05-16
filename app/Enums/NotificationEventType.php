<?php

namespace App\Enums;

enum NotificationEventType: string
{
    case DailyReportSubmitted = 'daily_report_submitted';
    case CommentAdded = 'comment_added';
    case RiskDetected = 'risk_detected';
    case TestCompleted = 'test_completed';
    case AnnouncementPosted = 'announcement_posted';
}
