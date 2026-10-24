<?php

namespace Tests\Feature;

use App\Enums\FulfillmentType;
use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\SalesMetrics;
use App\Support\BusinessTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Cache::flush();
        Order::query()->delete();

        // 10:00 in Kigali (08:00 UTC).
        $this->travelTo(Carbon::parse('2026-10-24 08:00:00', 'UTC'));

        $this->admin = User::factory()->create(['role_id' => Role::query()->where('slug', 'admin')->value('id'), 'is_active' => true]);
        $this->staff = User::factory()->create(['role_id' => Role::query()->where('slug', 'staff')->value('id'), 'is_active' => true]);
    }

    public function test_today_uses_kigali_day_boundaries(): void
    {
        $this->order(createdAtUtc: '2026-10-23 21:30:00'); // 23:30 yesterday in Kigali
        $this->order(createdAtUtc: '2026-10-23 22:30:00'); // 00:30 today in Kigali
        $this->order(createdAtUtc: '2026-10-24 07:00:00'); // 09:00 today in Kigali

        $this->assertSame(2, Order::query()->today()->count());

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('today', fn ($today) => $today['orders'] === 2)
            ->assertViewHas('yesterday', fn ($yesterday) => $yesterday['orders'] === 1);

        $this->actingAs($this->admin)->get(route('admin.orders.index', ['from' => '2026-10-24', 'to' => '2026-10-24']))
            ->assertViewHas('orders', fn ($orders) => $orders->total() === 2);
    }

    public function test_booked_and_collected_revenue_follow_their_definitions(): void
    {
        $this->order(total: 10000, status: OrderStatus::Delivered, paid: true);
        $this->order(total: 6000, status: OrderStatus::Pending);
        $this->order(total: 8500, status: OrderStatus::Cancelled, paid: true);
        $this->order(total: 7000, status: OrderStatus::Rejected);

        $summary = app(SalesMetrics::class)->summary(BusinessTime::day(), BusinessTime::day());

        $this->assertSame(4, $summary['orders']);
        $this->assertSame(2, $summary['sales_orders']);
        $this->assertSame(16000.0, $summary['booked']);
        $this->assertSame(10000.0, $summary['collected']);
        $this->assertSame(8000.0, $summary['average']);
    }

    public function test_comparison_with_zero_previous_has_no_percentage(): void
    {
        $metrics = app(SalesMetrics::class);

        $this->assertSame(['diff' => 3.0, 'percent' => null], $metrics->change(3, 0));
        $this->assertSame(['diff' => -1.0, 'percent' => -25.0], $metrics->change(3, 4));
    }

    public function test_pending_and_in_progress_counts_do_not_overlap(): void
    {
        $this->order(status: OrderStatus::Pending);
        $this->order(status: OrderStatus::Confirmed);
        $this->order(status: OrderStatus::OutForDelivery);
        $this->order(status: OrderStatus::Delivered);
        $this->order(status: OrderStatus::Cancelled);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertViewHas('pendingCount', 1)
            ->assertViewHas('inProgressCount', 2);

        $this->actingAs($this->admin)->get(route('admin.orders.index', ['status' => 'in_progress']))
            ->assertViewHas('orders', fn ($orders) => $orders->total() === 2);
    }

    public function test_seven_day_series_is_continuous_and_matches_orders(): void
    {
        $this->order(total: 5000, createdAtUtc: '2026-10-20 10:00:00');
        $this->order(total: 3000, createdAtUtc: '2026-10-24 07:00:00');
        $this->order(total: 9000, createdAtUtc: '2026-10-24 07:30:00', status: OrderStatus::Cancelled);

        $series = app(SalesMetrics::class)->series('7d');

        $this->assertCount(7, $series['points']);
        $this->assertSame('18 Oct', $series['points'][0]['label']);
        $byKey = collect($series['points'])->keyBy('key');
        $this->assertSame(5000.0, $byKey['2026-10-20']['booked']);
        $this->assertSame(0, $byKey['2026-10-21']['orders']);
        $this->assertSame(3000.0, $byKey['2026-10-24']['booked']);
        $this->assertSame(1, $byKey['2026-10-24']['orders']);

        $today = app(SalesMetrics::class)->series('today');
        $this->assertCount(24, $today['points']);
        $this->assertSame(1, $today['points'][9]['orders']); // 07:00 UTC = 09:00 Kigali
    }

    public function test_recent_orders_are_newest_first(): void
    {
        $old = $this->order(createdAtUtc: '2026-10-10 10:00:00');
        $new = $this->order(createdAtUtc: '2026-10-24 07:00:00');

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertViewHas('recentOrders', fn ($orders) => $orders->first()->is($new) && $orders->last()->is($old))
            ->assertSee('10 Oct, 12:00'); // shown in Kigali time
    }

    public function test_stock_alerts_match_thresholds(): void
    {
        InventoryItem::query()->update(['quantity_on_hand' => 50, 'low_stock_threshold' => 10]);
        $items = InventoryItem::query()->with('ingredient')->take(2)->get();
        $items[0]->update(['quantity_on_hand' => 0]);
        $items[1]->update(['quantity_on_hand' => 10]);

        $alerts = app(SalesMetrics::class)->stockAlerts();

        $this->assertSame([$items[0]->id, $items[1]->id], $alerts->pluck('id')->all());

        $this->actingAs($this->staff)->get(route('admin.dashboard'))
            ->assertSee($items[0]->ingredient->name)
            ->assertSee('Out');
    }

    public function test_popular_products_use_historical_order_lines_and_skip_cancelled(): void
    {
        $classic = Product::query()->where('slug', 'classic-ham-cheese')->first();
        $this->order(lines: [[$classic, 2, 6000]]);
        $this->order(lines: [[$classic, 5, 6000]], status: OrderStatus::Cancelled);
        $classic->update(['base_price' => 9999]);

        $top = app(SalesMetrics::class)->topProducts(BusinessTime::day(), BusinessTime::day());

        $this->assertSame(2, $top->first()->units);
        $this->assertSame(12000.0, $top->first()->sales);
    }

    public function test_staff_cannot_open_restricted_modules(): void
    {
        foreach (['admin.reports.index', 'admin.reports.export', 'admin.settings.edit', 'admin.users.index', 'admin.customers.index', 'admin.products.index', 'admin.categories.index', 'admin.options.index'] as $route) {
            $this->actingAs($this->staff)->get(route($route))->assertForbidden();
        }

        foreach (['admin.dashboard', 'admin.orders.index', 'admin.orders.create', 'admin.deliveries.index', 'admin.inventory.index', 'admin.ingredients.index'] as $route) {
            $this->actingAs($this->staff)->get(route($route))->assertOk();
        }

        $this->actingAs($this->staff)->get(route('admin.dashboard'))->assertDontSee('Booked sales today');
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertSee('Booked sales today');

        $customer = User::factory()->create(['role_id' => Role::query()->where('slug', 'customer')->value('id')]);
        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_status_changes_follow_the_workflow(): void
    {
        $order = $this->order(status: OrderStatus::Delivered);

        $this->actingAs($this->staff)->patch(route('admin.orders.status', $order), ['status' => 'preparing'])
            ->assertSessionHas('error');
        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);

        $ready = $this->order(status: OrderStatus::Ready);
        $this->actingAs($this->staff)->patch(route('admin.orders.status', $ready), ['status' => 'out_for_delivery'])
            ->assertSessionHas('error');
        $this->assertSame(OrderStatus::Ready, $ready->fresh()->status);

        $pending = $this->order(status: OrderStatus::Pending);
        $this->actingAs($this->staff)->patch(route('admin.orders.status', $pending), ['status' => 'rejected'])
            ->assertSessionHasErrors('reason');

        $this->actingAs($this->staff)->patch(route('admin.orders.status', $pending), ['status' => 'rejected', 'reason' => 'Out of bread']);
        $this->assertSame(OrderStatus::Rejected, $pending->fresh()->status);
        $this->assertSame('Out of bread', $pending->fresh()->rejection_reason);
    }

    public function test_cancelling_after_preparation_puts_stock_back_with_history(): void
    {
        $classic = Product::query()->where('slug', 'classic-ham-cheese')->first();
        $order = $this->order(lines: [[$classic, 1, 6000]], status: OrderStatus::Confirmed);
        $before = InventoryItem::query()->pluck('quantity_on_hand', 'id')->map(fn ($q) => (float) $q);

        $this->actingAs($this->staff)->patch(route('admin.orders.status', $order), ['status' => 'preparing'])->assertSessionHas('success');
        $this->assertTrue($order->fresh()->inventory_deducted);
        $this->assertGreaterThan(0, InventoryMovement::query()->where('type', InventoryMovementType::OrderDeduction)->count());

        $this->actingAs($this->staff)->patch(route('admin.orders.status', $order), ['status' => 'cancelled', 'reason' => 'Customer called']);

        $after = InventoryItem::query()->pluck('quantity_on_hand', 'id')->map(fn ($q) => (float) $q);
        $this->assertEquals($before, $after);
        $this->assertFalse($order->fresh()->inventory_deducted);
        $this->assertGreaterThan(0, InventoryMovement::query()->where('type', InventoryMovementType::OrderReversal)->count());
        $this->assertGreaterThan(0, InventoryMovement::query()->where('type', InventoryMovementType::OrderDeduction)->count());
    }

    public function test_payment_is_recorded_once_and_counts_as_collected(): void
    {
        $order = $this->order(total: 9500, status: OrderStatus::Confirmed);

        $this->actingAs($this->staff)->patch(route('admin.orders.payment', $order), ['method' => 'momo', 'reference' => 'TX123'])
            ->assertSessionHas('success');
        $this->actingAs($this->staff)->patch(route('admin.orders.payment', $order), ['method' => 'cash'])
            ->assertSessionHas('error');

        $this->assertSame(1, $order->payments()->count());
        $this->assertSame(9500.0, app(SalesMetrics::class)->collected(BusinessTime::day(), BusinessTime::day()));
    }

    public function test_staff_can_create_a_phone_order_priced_from_the_menu(): void
    {
        $classic = Product::query()->where('slug', 'classic-ham-cheese')->first();
        $fries = Product::query()->where('slug', 'fries-soda-extra')->first();

        $this->actingAs($this->staff)->post(route('admin.orders.store'), [
            'name' => 'Jean Bosco',
            'phone' => '0788123123',
            'fulfillment_type' => 'pickup',
            'payment_method' => 'cash',
            'total' => 1,
            'lines' => [
                ['product_id' => $classic->id, 'quantity' => 2, 'style' => 'Toasted', 'price' => 1],
                ['product_id' => $fries->id, 'quantity' => 1],
            ],
        ])->assertRedirect();

        $order = Order::query()->with('items.options', 'statusHistories')->sole();
        $this->assertSame('15000.00', $order->total);
        $this->assertNull($order->user_id);
        $this->assertTrue($order->items[0]->options->pluck('option_name')->contains('Toasted'));
        $this->assertFalse($order->items[0]->options->pluck('option_name')->contains('Fresh'));
        $this->assertStringContainsString($this->staff->name, $order->statusHistories->first()->notes);

        $classic->update(['is_active' => false]);
        $this->actingAs($this->staff)->post(route('admin.orders.store'), [
            'name' => 'X', 'phone' => '0788123123', 'fulfillment_type' => 'pickup', 'payment_method' => 'cash',
            'lines' => [['product_id' => $classic->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('lines.0.product_id');
    }

    public function test_report_export_totals_match_the_report(): void
    {
        $this->order(total: 10000, status: OrderStatus::Delivered, paid: true);
        $this->order(total: 6000, createdAtUtc: '2026-10-20 10:00:00');
        $this->order(total: 4000, status: OrderStatus::Cancelled);
        $this->order(total: 99999, createdAtUtc: '2026-09-01 10:00:00');

        $query = ['from' => '2026-10-20', 'to' => '2026-10-24'];

        $this->actingAs($this->admin)->get(route('admin.reports.index', $query))
            ->assertOk()
            ->assertViewHas('summary', fn ($s) => $s['orders'] === 3 && $s['booked'] === 16000.0 && $s['collected'] === 10000.0);

        $csv = $this->actingAs($this->admin)->get(route('admin.reports.export', $query))->streamedContent();

        $this->assertSame(3, substr_count($csv, 'MB-TEST-'));
        $this->assertStringContainsString('"Booked sales (RWF)",16000', $csv);
        $this->assertStringContainsString('"Collected (RWF)",10000', $csv);
    }

    public function test_stock_corrections_need_a_reason_and_keep_balances_consistent(): void
    {
        $item = InventoryItem::query()->first();
        $item->update(['quantity_on_hand' => 20]);

        $this->actingAs($this->staff)->post(route('admin.inventory.adjust', $item), ['action' => 'waste', 'quantity' => 2])
            ->assertSessionHasErrors('notes');

        $this->actingAs($this->staff)->post(route('admin.inventory.adjust', $item), ['action' => 'receive', 'quantity' => 5]);
        $this->actingAs($this->staff)->post(route('admin.inventory.adjust', $item), ['action' => 'waste', 'quantity' => 2, 'notes' => 'Dropped']);
        $this->actingAs($this->staff)->post(route('admin.inventory.adjust', $item), ['action' => 'adjustment', 'direction' => 'remove', 'quantity' => 3, 'notes' => 'Count']);
        $this->actingAs($this->staff)->post(route('admin.inventory.adjust', $item), ['action' => 'use', 'quantity' => 500])
            ->assertSessionHas('error');

        $item->refresh();
        $this->assertSame(20.0, (float) $item->quantity_on_hand);
        $movements = $item->movements()->reorder('id')->get();
        $this->assertSame(0.0, $movements->sum(fn ($m) => (float) $m->quantity));
        $this->assertSame(20.0, (float) $movements->last()->balance_after);
    }

    public function test_history_is_preserved_when_deleting_products_and_ingredients(): void
    {
        $classic = Product::query()->where('slug', 'classic-ham-cheese')->first();
        $this->order(lines: [[$classic, 1, 6000]]);

        $this->actingAs($this->admin)->delete(route('admin.products.destroy', $classic));
        $this->assertFalse($classic->fresh()->is_active);

        $item = InventoryItem::query()->with('ingredient')->first();
        $this->actingAs($this->admin)->post(route('admin.inventory.adjust', $item), ['action' => 'receive', 'quantity' => 1]);
        $this->actingAs($this->admin)->delete(route('admin.ingredients.destroy', $item->ingredient));
        $this->assertFalse($item->ingredient->fresh()->is_active);
        $this->assertTrue(InventoryMovement::query()->where('inventory_item_id', $item->id)->exists());
    }

    public function test_the_last_admin_cannot_be_removed(): void
    {
        User::query()->whereKeyNot($this->admin->id)->where('role_id', $this->admin->role_id)->update(['is_active' => false]);
        $staffRole = Role::query()->where('slug', 'staff')->value('id');

        $this->actingAs($this->admin)->patch(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name, 'email' => $this->admin->email, 'role_id' => $staffRole, 'is_active' => 1,
        ])->assertSessionHas('error');

        $this->assertTrue($this->admin->fresh()->isAdmin());
    }

    /**
     * @param  list<array{0: Product, 1: int, 2: float}>  $lines
     */
    private function order(
        float $total = 5000,
        OrderStatus $status = OrderStatus::Pending,
        string $createdAtUtc = '2026-10-24 07:00:00',
        bool $paid = false,
        array $lines = [],
    ): Order {
        $customer = Customer::query()->create(['name' => 'Test', 'phone' => '0788000111']);
        $created = Carbon::parse($createdAtUtc, 'UTC');

        if ($lines) {
            $total = collect($lines)->sum(fn ($l) => $l[1] * $l[2]);
        }

        $order = Order::query()->create([
            'order_number' => 'MB-TEST-'.(++$this->sequence),
            'customer_id' => $customer->id,
            'status' => $status,
            'fulfillment_type' => FulfillmentType::Pickup,
            'customer_name' => 'Test Customer '.$this->sequence,
            'customer_phone' => '0788000111',
            'subtotal' => $total,
            'total' => $total,
        ]);
        $order->forceFill(['created_at' => $created, 'updated_at' => $created])->saveQuietly();

        foreach ($lines as [$product, $qty, $price]) {
            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $qty,
                'unit_price' => $price,
                'line_total' => $qty * $price,
            ]);
        }

        Payment::query()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::Cash,
            'amount' => $total,
            'status' => $paid ? PaymentStatus::Paid : PaymentStatus::Pending,
            'paid_at' => $paid ? $created : null,
        ]);

        return $order;
    }
}
