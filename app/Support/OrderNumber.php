<?php

namespace App\Support;

class OrderNumber
{
    public static function normalize(?string $orderNumber): string
    {
        return strtoupper(trim((string) $orderNumber));
    }
}
