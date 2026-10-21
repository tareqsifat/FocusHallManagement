<?php

namespace App\Enums;

enum ComplaintCategory: string
{
    case Water = 'water';
    case Electricity = 'electricity';
    case Internet = 'internet';
    case Cleanliness = 'cleanliness';
    case Furniture = 'furniture';
    case Security = 'security';
    case Other = 'other';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
