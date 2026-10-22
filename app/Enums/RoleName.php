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

    /**
     * Roles the Super Admin sets on the Users screen. Student, House Tutor and Club Admin
     * come with a record of their own (registration, tutor profile, club), so they are
     * given and removed by those flows instead.
     *
     * @return list<self>
     */
    public static function editable(): array
    {
        return [self::SuperAdmin, self::Provost, self::OfficeStaff];
    }

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
