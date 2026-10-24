<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\BusinessTime;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->withCount('orders')
            ->withSum(['orders as spent' => fn ($q) => $q->countsTowardsSales()], 'total')
            ->withMax('orders as last_order_at', 'created_at')
            ->when($request->q, function ($q, $search) {
                $term = '%'.mb_strtolower(trim($search)).'%';
                $q->where(function ($inner) use ($term) {
                    $inner->whereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(phone) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$term]);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $kpis = [
            ['label' => 'Customers', 'value' => Customer::query()->count(), 'hint' => 'Total profiles'],
            ['label' => 'With orders', 'value' => Customer::query()->has('orders')->count(), 'hint' => 'Placed at least one', 'tone' => 'success'],
            ['label' => 'New (7 days)', 'value' => Customer::query()->where('created_at', '>=', BusinessTime::dayRange(-6)[0])->count(), 'hint' => 'Recent signups', 'tone' => 'info'],
            ['label' => 'No orders yet', 'value' => Customer::query()->doesntHave('orders')->count(), 'hint' => 'Registered only'],
        ];

        return view('admin.customers.index', compact('customers', 'kpis'));
    }

    public function show(Customer $customer): View
    {
        $customer->load(['addresses', 'orders' => fn ($q) => $q->latest()->limit(20), 'user']);

        $spent = (float) $customer->orders()->countsTowardsSales()->sum('total');

        $kpis = [
            ['label' => 'Orders', 'value' => $customer->orders()->count(), 'hint' => 'All time'],
            ['label' => 'Spent', 'value' => number_format($spent, 0).' RWF', 'hint' => 'Excludes cancelled & rejected', 'tone' => 'success'],
            ['label' => 'Addresses', 'value' => $customer->addresses->count(), 'hint' => 'Saved locations'],
            ['label' => 'Last order', 'value' => optional($customer->orders->first()?->created_at)->format('d M Y') ?? '—', 'hint' => 'Most recent'],
        ];

        return view('admin.customers.show', compact('customer', 'kpis'));
    }
}
