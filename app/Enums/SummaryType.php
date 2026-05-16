<?php

namespace App\Enums;

enum SummaryType: string
{
    case WeeklyStudent = 'weekly_student';
    case WeeklyClass = 'weekly_class';
    case RiskExplanation = 'risk_explanation';

    public function label(): string
    {
        return match ($this) {
            self::WeeklyStudent => '受講生週次サマリー',
            self::WeeklyClass => 'クラス週次レポート',
            self::RiskExplanation => '要注意者状況説明',
        };
    }
}
