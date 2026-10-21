<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super_admin';
    case Provost = 'provost';
    case HouseTutor = 'house_tutor';
    case OfficeStaff = 'office_staff';
    case ClubAdmin = 'club_admin';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Provost => 'Provost',
            self::HouseTutor => 'House Tutor',
            self::OfficeStaff => 'Hall Office Staff',
            self::ClubAdmin => 'Club Admin',
            self::Student => 'Student',
        };
    }
}
