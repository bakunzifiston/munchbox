<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\SalesMetrics;
use App\Support\BusinessTime;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly SalesMetrics $metrics) {}

    public function index(Request $request): View
    {
        $today = BusinessTime::day();
        $yesterday = BusinessTime::day(-1);

        $todaySummary = $this->metrics->summary($today, $today);
        $yesterdaySummary = $this->metrics->summary($yesterday, $yesterday);

        $recentOrders = Order::query()
            ->with('latestPayment')
            ->withCount('items')
            ->withSum('items', 'quantity')
            ->latest()
            ->limit(8)
            ->get();

        $series = $this->metrics->series($request->string('range')->toString());

        return view('admin.dashboard.index', [
            'today' => $todaySummary,
            'yesterday' => $yesterdaySummary,
            'ordersChange' => $this->metrics->change($todaySummary['orders'], $yesterdaySummary['orders']),
            'bookedChange' => $this->metrics->change($todaySummary['booked'], $yesterdaySummary['booked']),
            'collectedChange' => $this->metrics->change($todaySummary['collected'], $yesterdaySummary['collected']),
            'pendingCount' => $this->metrics->pendingCount(),
            'inProgressCount' => $this->metrics->inProgressCount(),
            'deliveredToday' => Order::query()->today()->where('status', OrderStatus::Delivered)->count(),
            'series' => $series,
            'seriesSummary' => $this->metrics->summary($series['from'], $series['to']),
            'ranges' => SalesMetrics::RANGES,
            'recentOrders' => $recentOrders,
            'stockAlerts' => $this->metrics->stockAlerts(6),
            'stockAlertCount' => $this->metrics->stockAlertCount(),
            'popular' => $this->metrics->topProducts($today, $today, 5, withPrevious: true),
            'canSeeMoney' => $request->user()->can('view-financials'),
        ]);
    }
}
