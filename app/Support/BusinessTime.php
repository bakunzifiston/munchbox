<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Business-day boundaries in the shop's timezone, expressed in the storage
 * timezone (UTC) so they can be used directly in queries.
 */
class BusinessTime
{
    public static function timezone(): string
    {
        return (string) config('app.business_timezone', config('app.timezone'));
    }

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::timezone());
    }

    /**
     * The local calendar date for a business day offset (0 = today, -1 = yesterday).
     */
    public static function day(int $offset = 0): CarbonImmutable
    {
        return self::now()->startOfDay()->addDays($offset);
    }

    /**
     * Parse a Y-m-d date as a local business date.
     */
    public static function parseDate(?string $date, CarbonImmutable $fallback): CarbonImmutable
    {
        if (! $date) {
            return $fallback;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $date, self::timezone())->startOfDay();
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * Storage-timezone bounds covering local days $from..$to inclusive.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function range(CarbonInterface $from, CarbonInterface $to): array
    {
        $storage = config('app.timezone');
        $start = CarbonImmutable::parse($from->format('Y-m-d'), self::timezone())->startOfDay();
        $end = CarbonImmutable::parse($to->format('Y-m-d'), self::timezone())->endOfDay();

        return [$start->setTimezone($storage), $end->setTimezone($storage)];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function dayRange(int $offset = 0): array
    {
        $day = self::day($offset);

        return self::range($day, $day);
    }

    /**
     * SQL expression for the local calendar date (Y-m-d) of a stored timestamp column.
     */
    public static function localDateSql(string $column): string
    {
        $offset = self::offsetSeconds();

        return match (DB::connection()->getDriverName()) {
            'pgsql' => "to_char({$column} + interval '{$offset} seconds', 'YYYY-MM-DD')",
            'mysql', 'mariadb' => "DATE_FORMAT(DATE_ADD({$column}, INTERVAL {$offset} SECOND), '%Y-%m-%d')",
            default => "strftime('%Y-%m-%d', {$column}, '{$offset} seconds')",
        };
    }

    /**
     * SQL expression for the local hour (0-23) of a stored timestamp column.
     */
    public static function localHourSql(string $column): string
    {
        $offset = self::offsetSeconds();

        return match (DB::connection()->getDriverName()) {
            'pgsql' => "CAST(EXTRACT(HOUR FROM {$column} + interval '{$offset} seconds') AS INTEGER)",
            'mysql', 'mariadb' => "HOUR(DATE_ADD({$column}, INTERVAL {$offset} SECOND))",
            default => "CAST(strftime('%H', {$column}, '{$offset} seconds') AS INTEGER)",
        };
    }

    /**
     * Seconds between the storage timezone and the business timezone. Kigali has
     * no daylight saving, so a fixed offset is exact.
     */
    private static function offsetSeconds(): int
    {
        $now = CarbonImmutable::now();

        return $now->setTimezone(self::timezone())->utcOffset() * 60
            - $now->setTimezone(config('app.timezone'))->utcOffset() * 60;
    }
}
