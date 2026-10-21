<?php

namespace App\Enums;

enum NoticeCategory: string
{
    case ProvostOffice = 'provost_office';
    case HallOffice = 'hall_office';
    case Club = 'club';

    public function label(): string
    {
        return match ($this) {
            self::ProvostOffice => 'Provost Office',
            self::HallOffice => 'Hall Office',
            self::Club => 'Club',
        };
    }
}
