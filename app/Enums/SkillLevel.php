<?php

namespace App\Enums;

enum SkillLevel: int
{
    case Beginner = 1;
    case Intermediate = 2;
    case Advanced = 3;

    public function label(): string
    {
        return match ($this) {
            self::Beginner => '初級',
            self::Intermediate => '中級',
            self::Advanced => '上級',
        };
    }
}
