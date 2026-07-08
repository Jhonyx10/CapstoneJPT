<?php

namespace App\Enums;

enum InventoryActionStatus: string
{
    case Restock = 'restock';
    case RepairUsage = 'repair_usage';
    case AddNewItem = 'new_item';
    case Waste = 'waste';
    case Adjustment = 'adjustment';
}