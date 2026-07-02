<?php

namespace App\Enums;

enum InventoryActionStatus: string
{
    case Restock = 'restock';
    case Repair = 'repair_usage';
    case Waste = 'waste';
    case Adjustment = 'adjustment';
}