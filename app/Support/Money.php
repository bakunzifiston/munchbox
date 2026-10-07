<?php

namespace App\Support;

class Money
{
    public static function format(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 0, '.', ',').' RWF';
    }

    public static function from(float|int|string|null $amount): float
    {
        return round((float) $amount, 2);
    }
}
