<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', now()->toDateString()))->endOfDay();

        $orders = Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Rejected->value]);

        $revenue = (float) (clone $orders)->sum('total');
        $orderCount = (clone $orders)->count();
        $avgOrder = $orderCount > 0 ? $revenue / $orderCount : 0;

        $byStatus = Order::query()
            ->select('status', DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('status')
            ->pluck('total', 'status');

        $byDay = Order::query()
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as orders'))
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Rejected->value])
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $topProducts = OrderItem::query()
            ->select('product_name', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(line_total) as revenue'))
            ->whereHas('order', fn ($q) => $q->whereBetween('created_at', [$from, $to])
                ->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Rejected->value]))
            ->groupBy('product_name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        $kpis = [
            ['label' => 'Revenue', 'value' => Money::format($revenue), 'hint' => $from->format('d M').' – '.$to->format('d M'), 'tone' => 'success'],
            ['label' => 'Orders', 'value' => $orderCount, 'hint' => 'Completed range'],
            ['label' => 'Avg order', 'value' => Money::format($avgOrder), 'hint' => 'Revenue / orders'],
            ['label' => 'Pending in range', 'value' => (int) ($byStatus[OrderStatus::Pending->value] ?? 0), 'hint' => 'Still open', 'tone' => 'warn'],
        ];

        return view('admin.reports.index', [
            'from' => $from,
            'to' => $to,
            'revenue' => $revenue,
            'revenueFormatted' => Money::format($revenue),
            'orderCount' => $orderCount,
            'avgOrder' => Money::format($avgOrder),
            'byStatus' => $byStatus,
            'kpis' => $kpis,
            'byDay' => $byDay,
            'topProducts' => $topProducts,
        ]);
    }
}
