<?php

namespace App\Enums;

enum RiskAlertReason: string
{
    case LowUnderstanding = 'low_understanding';
    case ReportMissing = 'report_missing';
    case LowScore = 'low_score';
}
