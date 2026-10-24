<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Support\BusinessTime;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The single place sales, order and stock figures are calculated, so the
 * dashboard, orders page, reports and exports always agree.
 *
 * Definitions:
 * - Orders placed: every order created in the period, whatever its status.
 * - Booked sales: order totals for orders placed in the period, excluding cancelled and rejected.
 * - Collected: payments marked paid during the period, excluding cancelled and rejected orders.
 */
class SalesMetrics
{
    public const RANGES = ['today' => 'Today', '7d' => '7 days', '30d' => '30 days'];

    /**
     * @return array{orders: int, sales_orders: int, cancelled: int, booked: float, collected: float, average: ?float}
     */
    public function summary(CarbonInterface $from, CarbonInterface $to): array
    {
        $row = Order::query()
            ->placedBetween($from, $to)
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(CASE WHEN status NOT IN (?, ?) THEN 1 ELSE 0 END) as sales_orders', OrderStatus::excludedFromSales())
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as cancelled', OrderStatus::excludedFromSales())
            ->selectRaw('COALESCE(SUM(CASE WHEN status NOT IN (?, ?) THEN total ELSE 0 END), 0) as booked', OrderStatus::excludedFromSales())
            ->first();

        $salesOrders = (int) $row->sales_orders;
        $booked = Money::from($row->booked);

        return [
            'orders' => (int) $row->orders,
            'sales_orders' => $salesOrders,
            'cancelled' => (int) $row->cancelled,
            'booked' => $booked,
            'collected' => $this->collected($from, $to),
            'average' => $salesOrders > 0 ? Money::from($booked / $salesOrders) : null,
        ];
    }

    public function collected(CarbonInterface $from, CarbonInterface $to): float
    {
        return Money::from(
            Payment::query()
                ->join('orders', 'orders.id', '=', 'payments.order_id')
                ->where('payments.status', PaymentStatus::Paid)
                ->whereBetween('payments.paid_at', BusinessTime::range($from, $to))
                ->whereNotIn('orders.status', OrderStatus::excludedFromSales())
                ->sum('payments.amount')
        );
    }

    /**
     * Change between two values; the percentage is null when there's nothing to compare against.
     *
     * @return array{diff: float, percent: ?float}
     */
    public function change(float|int $current, float|int $previous): array
    {
        return [
            'diff' => (float) $current - (float) $previous,
            'percent' => (float) $previous == 0.0 ? null : round(($current - $previous) / $previous * 100, 1),
        ];
    }

    /**
     * Continuous series for a dashboard range: hourly for today, daily otherwise.
     *
     * @return array{range: string, from: CarbonImmutable, to: CarbonImmutable, points: list<array{label: string, orders: int, booked: float, collected: float}>}
     */
    public function series(string $range): array
    {
        $range = array_key_exists($range, self::RANGES) ? $range : '7d';
        $to = BusinessTime::day();
        $from = match ($range) {
            'today' => $to,
            '30d' => $to->subDays(29),
            default => $to->subDays(6),
        };

        $points = $range === 'today'
            ? $this->hourlyPoints($to)
            : $this->dailyPoints($from, $to);

        return ['range' => $range, 'from' => $from, 'to' => $to, 'points' => $points];
    }

    /**
     * @return list<array{label: string, orders: int, booked: float, collected: float}>
     */
    public function dailyPoints(CarbonInterface $from, CarbonInterface $to): array
    {
        $day = BusinessTime::localDateSql('orders.created_at');
        $orders = Order::query()
            ->placedBetween($from, $to)
            ->countsTowardsSales()
            ->selectRaw("{$day} as bucket, COUNT(*) as orders, SUM(total) as booked")
            ->groupByRaw($day)
            ->get()
            ->keyBy('bucket');

        $paidDay = BusinessTime::localDateSql('payments.paid_at');
        $collected = $this->collectedQuery($from, $to)
            ->selectRaw("{$paidDay} as bucket, SUM(payments.amount) as collected")
            ->groupByRaw($paidDay)
            ->pluck('collected', 'bucket');

        $points = [];
        for ($date = CarbonImmutable::parse($from->format('Y-m-d')); $date->lte(CarbonImmutable::parse($to->format('Y-m-d'))); $date = $date->addDay()) {
            $key = $date->format('Y-m-d');
            $points[] = [
                'key' => $key,
                'label' => $date->format('d M'),
                'orders' => (int) ($orders[$key]->orders ?? 0),
                'booked' => Money::from($orders[$key]->booked ?? 0),
                'collected' => Money::from($collected[$key] ?? 0),
            ];
        }

        return $points;
    }

    /**
     * @return list<array{label: string, orders: int, booked: float, collected: float}>
     */
    private function hourlyPoints(CarbonInterface $day): array
    {
        $hour = BusinessTime::localHourSql('orders.created_at');
        $orders = Order::query()
            ->placedBetween($day, $day)
            ->countsTowardsSales()
            ->selectRaw("{$hour} as bucket, COUNT(*) as orders, SUM(total) as booked")
            ->groupByRaw($hour)
            ->get()
            ->keyBy('bucket');

        $paidHour = BusinessTime::localHourSql('payments.paid_at');
        $collected = $this->collectedQuery($day, $day)
            ->selectRaw("{$paidHour} as bucket, SUM(payments.amount) as collected")
            ->groupByRaw($paidHour)
            ->pluck('collected', 'bucket');

        $points = [];
        for ($h = 0; $h < 24; $h++) {
            $points[] = [
                'key' => (string) $h,
                'label' => sprintf('%02d:00', $h),
                'orders' => (int) ($orders[$h]->orders ?? 0),
                'booked' => Money::from($orders[$h]->booked ?? 0),
                'collected' => Money::from($collected[$h] ?? 0),
            ];
        }

        return $points;
    }

    /**
     * Best sellers from historical order lines (what customers actually paid).
     *
     * @return Collection<int, object{product_id: ?int, product_name: string, units: int, sales: float, previous_units: ?int}>
     */
    public function topProducts(CarbonInterface $from, CarbonInterface $to, int $limit = 5, bool $withPrevious = false): Collection
    {
        $rows = $this->productQuery($from, $to)->orderByDesc('units')->orderByDesc('sales')->limit($limit)->get();

        $previous = collect();
        if ($withPrevious && $rows->isNotEmpty()) {
            $days = (int) CarbonImmutable::parse($from->format('Y-m-d'))->diffInDays(CarbonImmutable::parse($to->format('Y-m-d'))) + 1;
            $previous = $this->productQuery(
                CarbonImmutable::parse($from->format('Y-m-d'))->subDays($days),
                CarbonImmutable::parse($from->format('Y-m-d'))->subDay()
            )->get()->keyBy('product_name');
        }

        return $rows->map(fn ($row) => (object) [
            'product_id' => $row->product_id,
            'product_name' => $row->product_name,
            'units' => (int) $row->units,
            'sales' => Money::from($row->sales),
            'previous_units' => $withPrevious ? (int) ($previous[$row->product_name]->units ?? 0) : null,
        ]);
    }

    /**
     * Inventory items at or below their minimum, worst first.
     *
     * @return Collection<int, InventoryItem>
     */
    public function stockAlerts(?int $limit = null): Collection
    {
        return InventoryItem::query()
            ->with('ingredient')
            ->lowStock()
            ->orderByRaw('CASE WHEN quantity_on_hand <= 0 THEN 0 ELSE 1 END')
            ->orderBy('quantity_on_hand')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();
    }

    public function stockAlertCount(): int
    {
        return InventoryItem::query()->lowStock()->count();
    }

    public function pendingCount(): int
    {
        return Order::query()->pending()->count();
    }

    public function inProgressCount(): int
    {
        return Order::query()->inProgress()->count();
    }

    private function productQuery(CarbonInterface $from, CarbonInterface $to)
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', BusinessTime::range($from, $to))
            ->whereNotIn('orders.status', OrderStatus::excludedFromSales())
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->select('order_items.product_id', 'order_items.product_name')
            ->selectRaw('SUM(order_items.quantity) as units, SUM(order_items.line_total) as sales');
    }

    private function collectedQuery(CarbonInterface $from, CarbonInterface $to)
    {
        return DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('payments.status', PaymentStatus::Paid->value)
            ->whereBetween('payments.paid_at', BusinessTime::range($from, $to))
            ->whereNotIn('orders.status', OrderStatus::excludedFromSales());
    }
}
