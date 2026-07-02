<?php

namespace App\Enums;

enum InvoiceType: string
{
    case Estimated = 'estimated_price';
    case Final = 'final_commercial_invoice';
    case Materials = 'materials_invoice';
   case Supplemental = 'supplemental_invoice';
}
