<?php

namespace App\Enums;

enum AnnouncementTargetType: string
{
    case All = 'all';
    case Curriculum = 'curriculum';
    case User = 'user';
}
