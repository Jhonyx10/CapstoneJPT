<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case ForSale = 'for_sale';
    case ForRepair = 'for_repair';
    case Unavailable = 'unavailable';
    case Sold = 'sold';

    public function requiresRepairFlow(): bool
    {
        return $this === self::ForRepair;
    }

    public static function registrationOptions(): array
    {
        return [
            self::ForSale,
            self::ForRepair,
        ];
    }
}
