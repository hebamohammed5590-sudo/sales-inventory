<?php

namespace App\Enums;

enum InvoiceType: string
{
    case Purchase = 'purchase';

    case Sale = 'sale';
}
