<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function overview(?Carbon $from = null, ?Carbon $to = null, string $range = '7d'): array
    {
        $from ??= today();
        $to ??= today()->endOfDay();

        $todayStart = today()->startOfDay();
        $todayEnd = today()->endOfDay();
        $yesterdayStart = today()->subDay()->startOfDay();
        $yesterdayEnd = today()->subDay()->endOfDay();

        $ordersToday = Order::query()->whereBetween('created_at', [$todayStart, $todayEnd])->get();
        $ordersYesterday = Order::query()->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])->count();

        $revenueToday = (float) Order::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Rejected->value])
            ->sum('total');

        $revenueYesterday = (float) Order::query()
            ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Rejected->value])
            ->sum('total');

        $pending = Order::query()->pending()->count();
        $active = Order::query()->active()->count();

        $chart = $this->salesSeries($range);
        $popular = $this->popularProducts($todayStart, $todayEnd);
        $maxPopularQty = max(1, (int) $popular->max('total_qty'));

        $recent = Order::query()
            ->with(['customer', 'items'])
            ->withCount('items')
            ->latest()
            ->limit(8)
            ->get();

        $lowStock = InventoryItem::query()
            ->with('ingredient')
            ->lowStock()
            ->get()
            ->sortBy('quantity_on_hand')
            ->take(8)
            ->values();

        return [
            'orders_today' => $ordersToday->count(),
            'orders_today_change' => $this->percentChange($ordersToday->count(), $ordersYesterday),
            'pending_orders' => $pending,
            'active_orders' => $active,
            'completed_today' => $ordersToday->where('status', OrderStatus::Delivered)->count(),
            'revenue_today' => $revenueToday,
            'revenue_today_formatted' => Money::format($revenueToday),
            'revenue_today_change' => $this->percentChange($revenueToday, $revenueYesterday),
            'popular_products' => $popular,
            'popular_max_qty' => $maxPopularQty,
            'low_stock' => $lowStock,
            'critical_stock_count' => $lowStock->filter(fn (InventoryItem $item) => (float) $item->quantity_on_hand <= ((float) $item->low_stock_threshold / 2))->count(),
            'recent_orders' => $recent,
            'product_count' => Product::query()->active()->count(),
            'chart' => $chart,
            'chart_range' => $range,
        ];
    }

    /**
     * @return array{labels: list<string>, revenue: list<float>, orders: list<int>, total_revenue: float, total_orders: int}
     */
    public function salesSeries(string $range = '7d'): array
    {
        [$start, $end, $groupByHour] = match ($range) {
            'today' => [today()->startOfDay(), today()->endOfDay(), true],
            '30d' => [today()->subDays(29)->startOfDay(), today()->endOfDay(), false],
            default => [today()->subDays(6)->startOfDay(), today()->endOfDay(), false],
        };

        $orders = Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Rejected->value])
            ->get(['created_at', 'total']);

        $labels = [];
        $revenue = [];
        $counts = [];

        if ($groupByHour) {
            for ($h = 0; $h < 24; $h += 3) {
                $bucketStart = today()->startOfDay()->addHours($h);
                $bucketEnd = $bucketStart->copy()->addHours(3)->subSecond();
                $slice = $orders->filter(fn (Order $o) => $o->created_at->between($bucketStart, $bucketEnd));
                $labels[] = $bucketStart->format('H:i');
                $revenue[] = round((float) $slice->sum('total'), 0);
                $counts[] = $slice->count();
            }
        } else {
            $days = $start->copy();
            while ($days->lte($end)) {
                $slice = $orders->filter(fn (Order $o) => $o->created_at->isSameDay($days));
                $labels[] = $days->format('d M');
                $revenue[] = round((float) $slice->sum('total'), 0);
                $counts[] = $slice->count();
                $days->addDay();
            }
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'orders' => $counts,
            'total_revenue' => array_sum($revenue),
            'total_orders' => array_sum($counts),
            'max_revenue' => max(1, ...array_merge($revenue, [1])),
            'max_orders' => max(1, ...array_merge($counts, [1])),
        ];
    }

    private function popularProducts(Carbon $from, Carbon $to): Collection
    {
        $rows = OrderItem::query()
            ->select(
                'product_name',
                'product_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(line_total) as revenue')
            )
            ->whereHas('order', function ($q) use ($from, $to) {
                $q->whereBetween('created_at', [$from, $to])
                    ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Rejected->value]);
            })
            ->groupBy('product_name', 'product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $products = Product::query()
            ->whereIn('id', $rows->pluck('product_id')->filter())
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($products) {
            $product = $row->product_id ? $products->get($row->product_id) : null;
            $row->image = $product?->image;
            $row->slug = $product?->slug;

            return $row;
        });
    }

    private function percentChange(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
