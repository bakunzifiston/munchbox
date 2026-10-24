<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\SalesMetrics;
use App\Support\BusinessTime;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const MAX_DAYS = 366;

    public function __construct(private readonly SalesMetrics $metrics) {}

    public function index(Request $request): View
    {
        [$from, $to] = $this->period($request);

        $summary = $this->metrics->summary($from, $to);

        $byStatus = Order::query()
            ->placedBetween($from, $to)
            ->selectRaw('status, COUNT(*) as total, SUM(total) as value')
            ->groupBy('status')
            ->get()
            ->keyBy(fn ($row) => $row->status instanceof OrderStatus ? $row->status->value : $row->status);

        $topCustomers = Order::query()
            ->placedBetween($from, $to)
            ->countsTowardsSales()
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->groupBy('customers.id', 'customers.name', 'customers.phone')
            ->select('customers.id', 'customers.name', 'customers.phone')
            ->selectRaw('COUNT(orders.id) as orders, SUM(orders.total) as spent')
            ->orderByDesc('spent')
            ->limit(10)
            ->get();

        return view('admin.reports.index', [
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'byStatus' => $byStatus,
            'daily' => $this->metrics->dailyPoints($from, $to),
            'topProducts' => $this->metrics->topProducts($from, $to, 10),
            'topCustomers' => $topCustomers,
            'delivery' => $this->deliveryPerformance($from, $to),
            'stockAlerts' => $this->metrics->stockAlerts(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->period($request);
        $summary = $this->metrics->summary($from, $to);
        $filename = 'munchbox-orders-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($from, $to, $summary) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Order', 'Placed (Kigali)', 'Status', 'Type', 'Customer', 'Phone', 'Items', 'Subtotal', 'Delivery fee', 'Total', 'Payment', 'Payment status', 'Counts towards sales']);

            Order::query()
                ->placedBetween($from, $to)
                ->with('latestPayment')
                ->withSum('items', 'quantity')
                ->orderBy('created_at')
                ->chunk(500, function ($orders) use ($out) {
                    foreach ($orders as $order) {
                        fputcsv($out, [
                            $order->order_number,
                            $order->created_at->format('Y-m-d H:i'),
                            $order->status->label(),
                            $order->fulfillment_type->label(),
                            $order->customer_name,
                            $order->customer_phone,
                            (int) $order->items_sum_quantity,
                            $order->subtotal,
                            $order->delivery_fee,
                            $order->total,
                            $order->latestPayment?->method->label(),
                            $order->latestPayment?->status->label(),
                            in_array($order->status->value, OrderStatus::excludedFromSales(), true) ? 'No' : 'Yes',
                        ]);
                    }
                });

            fputcsv($out, []);
            fputcsv($out, ['Orders placed', $summary['orders']]);
            fputcsv($out, ['Booked sales (RWF)', $summary['booked']]);
            fputcsv($out, ['Collected (RWF)', $summary['collected']]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function period(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $to = BusinessTime::parseDate($request->input('to'), BusinessTime::day());
        $from = BusinessTime::parseDate($request->input('from'), $to->startOfMonth());

        if ($from->diffInDays($to) >= self::MAX_DAYS) {
            $from = $to->subDays(self::MAX_DAYS - 1);
        }

        return [$from, $to];
    }

    /**
     * @return array{orders: int, delivered: int, failed: int, average_minutes: ?int, fees: float}
     */
    private function deliveryPerformance(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $orders = Order::query()
            ->placedBetween($from, $to)
            ->where('fulfillment_type', FulfillmentType::Delivery);

        $row = (clone $orders)
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as delivered', [OrderStatus::Delivered->value])
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as failed', OrderStatus::excludedFromSales())
            ->selectRaw('COALESCE(SUM(CASE WHEN status NOT IN (?, ?) THEN delivery_fee ELSE 0 END), 0) as fees', OrderStatus::excludedFromSales())
            ->first();

        $minutes = (clone $orders)
            ->where('status', OrderStatus::Delivered)
            ->whereNotNull('delivered_at')
            ->get(['created_at', 'delivered_at'])
            ->map(fn (Order $order) => $order->created_at->diffInMinutes($order->delivered_at));

        return [
            'orders' => (int) $row->orders,
            'delivered' => (int) $row->delivered,
            'failed' => (int) $row->failed,
            'average_minutes' => $minutes->isNotEmpty() ? (int) round($minutes->avg()) : null,
            'fees' => (float) $row->fees,
        ];
    }
}
