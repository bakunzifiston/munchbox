<?php

namespace App\Support;

class Phone
{
    public static function digits(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    public static function matches(?string $left, ?string $right): bool
    {
        $left = self::digits($left);
        $right = self::digits($right);

        if ($left === '' || $right === '') {
            return false;
        }

        if ($left === $right) {
            return true;
        }

        return substr($left, -9) === substr($right, -9);
    }
}
