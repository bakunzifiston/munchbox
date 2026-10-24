<?php

namespace Tests\Feature;

use App\Enums\OptionType;
use App\Enums\OrderStatus;
use App\Enums\SelectionType;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\OptionGroup;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\MenuPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OrderingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Cache::flush();
    }

    public function test_menu_lists_products_with_prices_and_descriptions(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSeeInOrder(['Signature Sandwiches', 'Classic Ham &amp; Cheese', '6,000 RWF'], false);
        $response->assertSee('Ham, cheese, lettuce, tomatoes, cucumbers, pickled onions.');
        $response->assertSee('All-In-One Supreme');
        $response->assertSee('10,000 RWF');
        $response->assertSee('Crispy fries paired with a chilled soda.');
        $response->assertSee('Customize &amp; order', false);
        $response->assertSee('Add to order');
        $response->assertDontSee('Complete the meal');
    }

    public function test_recipe_options_are_part_of_the_menu_price(): void
    {
        foreach (['crispy-chicken-deluxe' => 8500, 'beef-jambon-supreme' => 8500, 'all-in-one-supreme' => 10000] as $slug => $price) {
            $this->delete(route('cart.clear'));
            $product = $this->product($slug);

            $this->addToCart($product, $this->recipe($product))->assertSessionHasNoErrors();

            $this->assertSame((float) $price, session('cart')[0]['line_total'], $slug);
        }
    }

    public function test_paid_extras_are_charged_and_removals_do_not_reduce_price(): void
    {
        $product = $this->product('classic-ham-cheese');
        $options = $product->optionGroups->flatMap->activeOptions;
        $avocado = $options->firstWhere('name', 'Avocado');
        $lettuce = $options->firstWhere('name', 'Lettuce');

        $withAvocado = array_values(array_diff([...$this->recipe($product), $avocado->id], [$lettuce->id]));

        $this->addToCart($product, $withAvocado, quantity: 2)->assertSessionHasNoErrors();

        $line = session('cart')[0];
        $this->assertSame(7000.0, $line['unit_with_options']);
        $this->assertSame(14000.0, $line['line_total']);
    }

    public function test_options_from_other_groups_are_rejected(): void
    {
        $product = $this->product('classic-ham-cheese');
        $foreignGroup = OptionGroup::query()->create([
            'name' => 'Secret', 'slug' => 'secret', 'type' => OptionType::Extra,
            'selection_type' => SelectionType::Multiple, 'is_required' => false,
            'min_selections' => 0, 'max_selections' => 1, 'is_active' => true,
        ]);
        $foreign = $foreignGroup->options()->create(['name' => 'Gold Leaf', 'price_adjustment' => -5000, 'is_active' => true]);

        $this->addToCart($product, [...$this->recipe($product), $foreign->id])->assertSessionHasErrors('options');

        $this->assertEmpty(session('cart', []));
    }

    public function test_required_choices_and_limits_are_validated(): void
    {
        $product = $this->product('classic-ham-cheese');
        $style = $product->optionGroups->firstWhere('slug', 'serving-style');

        $withoutStyle = array_values(array_diff($this->recipe($product), $style->activeOptions->pluck('id')->all()));
        $this->addToCart($product, $withoutStyle)->assertSessionHasErrors('options');

        $bothStyles = [...$withoutStyle, ...$style->activeOptions->pluck('id')->all()];
        $this->addToCart($product, $bothStyles)->assertSessionHasErrors('options');

        $this->assertEmpty(session('cart', []));
    }

    public function test_cart_items_can_be_updated_removed_and_cleared(): void
    {
        $classic = $this->product('classic-ham-cheese');
        $fries = $this->product('fries-soda-extra');

        $this->addToCart($classic, $this->recipe($classic));
        $this->addToCart($fries, []);

        $key = session('cart')[0]['key'];
        $this->patch(route('cart.update', $key), ['quantity' => 3]);
        $this->assertSame(18000.0, session('cart')[0]['line_total']);

        $this->get(route('cart.index'))->assertOk()->assertSee('21,000 RWF');

        $this->delete(route('cart.destroy', $key));
        $this->assertCount(1, session('cart'));

        $this->delete(route('cart.clear'));
        $this->assertEmpty(session('cart', []));
    }

    public function test_checkout_uses_current_prices_and_ignores_client_totals(): void
    {
        $classic = $this->product('classic-ham-cheese');
        $this->addToCart($classic, $this->recipe($classic));

        $classic->update(['base_price' => 6500]);

        $this->get(route('checkout.create'))->assertOk()->assertSee('The price of Classic Ham &amp; Cheese is now 6,500 RWF', false);

        $this->post(route('checkout.store'), $this->checkoutPayload([
            'total' => 1, 'subtotal' => 1, 'delivery_fee' => 0, 'price' => 1,
        ]))->assertRedirect();

        $order = Order::query()->with('items.options')->sole();
        $this->assertSame('6500.00', $order->subtotal);
        $this->assertSame('1500.00', $order->delivery_fee);
        $this->assertSame('8000.00', $order->total);
        $this->assertSame('Classic Ham & Cheese', $order->items[0]->product_name);
        $this->assertNotEmpty($order->items[0]->options);
        $this->assertSame('Remera', $order->delivery->district);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertEmpty(session('cart', []));
    }

    public function test_repeated_checkout_submission_creates_one_order(): void
    {
        $fries = $this->product('fries-soda-extra');
        $this->addToCart($fries, []);
        $this->get(route('checkout.create'));

        $payload = $this->checkoutPayload();
        $first = $this->post(route('checkout.store'), $payload);
        $second = $this->post(route('checkout.store'), $payload);

        $this->assertSame(1, Order::query()->count());
        $order = Order::query()->sole();
        $first->assertRedirect(route('orders.confirmation', $order));
        $second->assertRedirect(route('orders.confirmation', $order));
    }

    public function test_stale_checkout_token_is_rejected(): void
    {
        $fries = $this->product('fries-soda-extra');
        $this->addToCart($fries, []);
        $this->get(route('checkout.create'));

        $this->post(route('checkout.store'), array_merge($this->checkoutPayload(), ['checkout_token' => 'forged']))
            ->assertRedirect(route('checkout.create'));

        $this->assertSame(0, Order::query()->count());
    }

    public function test_minimum_order_is_enforced(): void
    {
        BusinessSetting::setValue('min_order_amount', 10000, 'orders', 'decimal');
        $fries = $this->product('fries-soda-extra');
        $this->addToCart($fries, []);

        $this->get(route('checkout.create'))->assertRedirect(route('cart.index'));
        $this->get(route('cart.index'))->assertSee('The minimum order is 10,000 RWF');
    }

    public function test_delivery_requires_a_supported_area_but_pickup_does_not(): void
    {
        $fries = $this->product('fries-soda-extra');
        $this->addToCart($fries, []);
        $this->get(route('checkout.create'));

        $this->post(route('checkout.store'), $this->checkoutPayload(['delivery_area' => 'Mars']))
            ->assertSessionHasErrors('delivery_area');
        $this->post(route('checkout.store'), $this->checkoutPayload(['delivery_area' => '']))
            ->assertSessionHasErrors('delivery_area');
        $this->assertSame(0, Order::query()->count());

        $this->post(route('checkout.store'), $this->checkoutPayload(['fulfillment_type' => 'pickup', 'delivery_area' => '']))
            ->assertSessionHasNoErrors();

        $order = Order::query()->sole();
        $this->assertSame('3000.00', $order->total);
        $this->assertNull($order->delivery);
    }

    public function test_saved_address_of_another_customer_is_rejected(): void
    {
        $other = Customer::query()->create(['name' => 'Other', 'phone' => '0788000000']);
        $address = $other->addresses()->create(['label' => 'Home', 'street' => 'Secret St', 'city' => 'Kigali']);

        $user = User::factory()->create();
        $fries = $this->product('fries-soda-extra');
        $this->actingAs($user);
        $this->addToCart($fries, []);
        $this->get(route('checkout.create'));

        $this->post(route('checkout.store'), $this->checkoutPayload(['address_id' => $address->id]))
            ->assertSessionHasErrors('address_id');
    }

    public function test_out_of_stock_ingredient_blocks_checkout_and_keeps_cart(): void
    {
        $classic = $this->product('classic-ham-cheese');
        $this->addToCart($classic, $this->recipe($classic));
        InventoryItem::query()->update(['quantity_on_hand' => 0]);

        $this->get(route('checkout.create'));
        $this->post(route('checkout.store'), $this->checkoutPayload())
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'running low'));

        $this->assertSame(0, Order::query()->count());
        $this->assertCount(1, session('cart'));
    }

    public function test_only_the_owner_can_cancel_an_order(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $fries = $this->product('fries-soda-extra');

        $this->actingAs($owner);
        $this->addToCart($fries, []);
        $this->get(route('checkout.create'));
        $this->post(route('checkout.store'), $this->checkoutPayload(['phone' => '0788111222']));
        $order = Order::query()->sole();

        $this->actingAs($intruder)
            ->post(route('orders.cancel', ['order' => $order, 'phone' => '0788111222']))
            ->assertForbidden();
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);

        $this->actingAs($owner)->post(route('orders.cancel', $order));
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_tracking_shows_progress_and_details(): void
    {
        $fries = $this->product('fries-soda-extra');
        $this->addToCart($fries, []);
        $this->get(route('checkout.create'));
        $this->post(route('checkout.store'), $this->checkoutPayload(['phone' => '0788555666']));
        $order = Order::query()->sole();

        $this->flushSession();

        $this->get(route('orders.show', $order))->assertForbidden();

        $this->get(route('orders.track', ['order_number' => $order->order_number, 'phone' => '+250 788 555 666']))
            ->assertOk()
            ->assertSee('Order placed')
            ->assertSee('Out for Delivery')
            ->assertSee('Fries &amp; Soda Extra', false)
            ->assertSee('Cash on Delivery')
            ->assertSee('Remera');
    }

    private function product(string $slug): Product
    {
        return Product::query()->with('optionGroups.activeOptions')->where('slug', $slug)->firstOrFail();
    }

    /**
     * @return list<int>
     */
    private function recipe(Product $product): array
    {
        return app(MenuPricing::class)->includedOptionIds($product);
    }

    private function addToCart(Product $product, array $optionIds, int $quantity = 1)
    {
        return $this->post(route('cart.store', $product), [
            'quantity' => $quantity,
            'options' => $optionIds,
        ]);
    }

    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'checkout_token' => session('checkout_token'),
            'name' => 'Aline Uwase',
            'phone' => '0788123456',
            'fulfillment_type' => 'delivery',
            'payment_method' => 'cash',
            'delivery_area' => 'Remera',
            'address' => ['street' => 'KG 11 Ave', 'landmark' => 'Blue gate'],
        ], $overrides);
    }
}
