<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FulfillmentType;
use App\Enums\OptionType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CheckoutRequest;
use App\Models\BusinessSetting;
use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use App\Services\MenuPricing;
use App\Services\OrderService;
use App\Support\BusinessTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly CartService $cart,
        private readonly MenuPricing $pricing,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_merge(array_column(OrderStatus::cases(), 'value'), ['open', 'in_progress']))],
            'type' => ['nullable', Rule::enum(FulfillmentType::class)],
            'payment' => ['nullable', Rule::enum(PaymentStatus::class)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $orders = $this->filtered($filters)
            ->with('latestPayment')
            ->withCount('items')
            ->withSum('items', 'quantity')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Tab counts follow every filter except status, so they explain the list below.
        $statusCounts = $this->filtered(array_merge($filters, ['status' => null]))
            ->reorder()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'statusCounts' => $statusCounts,
            'statuses' => OrderStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'fulfillmentTypes' => FulfillmentType::cases(),
        ]);
    }

    public function create(): View
    {
        $products = Product::query()
            ->active()
            ->with(['category', 'optionGroups.activeOptions'])
            ->orderBy('sort_order')
            ->get();

        return view('admin.orders.create', [
            'products' => $products,
            'styles' => $products->mapWithKeys(fn (Product $product) => [
                $product->id => $product->optionGroups
                    ->firstWhere('type', OptionType::Style)?->activeOptions->pluck('name')->all() ?? [],
            ]),
            'deliveryAreas' => config('menu.delivery_areas', []),
            'deliveryFee' => (float) BusinessSetting::getValue('delivery_fee', 0),
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:9', 'max:30', 'regex:/^\+?[0-9\s()-]+$/'],
            'fulfillment_type' => ['required', Rule::enum(FulfillmentType::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'delivery_area' => ['nullable', 'required_if:fulfillment_type,delivery', Rule::in(CheckoutRequest::areaTitles())],
            'address.street' => ['nullable', 'string', 'max:255'],
            'address.landmark' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1', 'max:30'],
            'lines.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:'.CartService::MAX_QUANTITY],
            'lines.*.style' => ['nullable', 'string', 'max:50'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ], [
            'lines.required' => 'Add at least one item.',
            'delivery_area.required_if' => 'Choose the delivery area.',
        ]);

        $products = Product::query()
            ->with('optionGroups.activeOptions')
            ->whereIn('id', collect($data['lines'])->pluck('product_id'))
            ->get()
            ->keyBy('id');

        try {
            $lines = collect($data['lines'])->map(function (array $line) use ($products) {
                $product = $products[$line['product_id']];

                return $this->cart->makeLine(
                    $product,
                    $this->recipeWithStyle($product, $line['style'] ?? null),
                    $line['notes'] ?? null,
                    (int) $line['quantity'],
                );
            })->all();

            $order = $this->orders->place(
                null,
                $data,
                $lines,
                $request->user(),
                'Order entered by '.$request->user()->name,
            );
        } catch (CheckoutException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.orders.show', $order)->with('success', "Order {$order->order_number} created.");
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'items.options', 'customer', 'delivery', 'payments', 'statusHistories.user']);

        return view('admin.orders.show', [
            'order' => $order,
            'transitions' => array_values(array_filter(
                $order->status->allowedTransitions(),
                fn (OrderStatus $s) => ! ($s === OrderStatus::OutForDelivery && $order->fulfillment_type === FulfillmentType::Pickup)
            )),
            'paymentMethods' => PaymentMethod::cases(),
            'isPaid' => $order->payments->contains('status', PaymentStatus::Paid),
        ]);
    }

    public function receipt(Order $order): View
    {
        $order->load(['items.options', 'delivery', 'payments']);

        return view('admin.orders.receipt', [
            'order' => $order,
            'business' => [
                'name' => BusinessSetting::getValue('business_name', 'MunchBox'),
                'phone' => BusinessSetting::getValue('phone', config('menu.phone')),
                'address' => BusinessSetting::getValue('address', 'Kigali, Rwanda'),
            ],
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reason' => [
                Rule::requiredIf(in_array($request->input('status'), OrderStatus::excludedFromSales(), true)),
                'nullable', 'string', 'max:1000',
            ],
        ], ['reason.required' => 'Please give a reason for cancelling or rejecting the order.']);

        $status = OrderStatus::from($data['status']);

        try {
            $this->orders->transition($order, $status, $request->user(), $data['notes'] ?? null, $data['reason'] ?? null);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Order moved to '.$status->label().'.');
    }

    public function recordPayment(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $this->orders->recordPayment($order, PaymentMethod::from($data['method']), $data['reference'] ?? null, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payment recorded.');
    }

    /**
     * @return list<int>
     */
    private function recipeWithStyle(Product $product, ?string $style): array
    {
        $ids = $this->pricing->includedOptionIds($product);
        $styleGroup = $product->optionGroups->firstWhere('type', OptionType::Style);

        if (! $style || ! $styleGroup) {
            return $ids;
        }

        $chosen = $styleGroup->activeOptions->firstWhere('name', $style);

        if (! $chosen) {
            throw ValidationException::withMessages(['lines' => "“{$style}” isn’t available for {$product->name}."]);
        }

        $styleIds = $styleGroup->activeOptions->pluck('id')->all();

        return array_values(array_merge(array_diff($ids, $styleIds), [$chosen->id]));
    }

    private function filtered(array $filters): Builder
    {
        return Order::query()
            ->when($filters['q'] ?? null, function (Builder $q, string $search) {
                $term = '%'.mb_strtolower(trim($search)).'%';
                $digits = preg_replace('/\D+/', '', $search);

                $q->where(function (Builder $inner) use ($term, $digits) {
                    $inner->whereRaw('LOWER(order_number) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(customer_name) LIKE ?', [$term]);

                    if (strlen($digits) >= 3) {
                        $inner->orWhereRaw("REPLACE(REPLACE(customer_phone, ' ', ''), '+', '') LIKE ?", ['%'.ltrim($digits, '0').'%']);
                    }
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => match ($status) {
                'open' => $q->whereIn('status', array_map(fn ($s) => $s->value, OrderStatus::active())),
                'in_progress' => $q->inProgress(),
                default => $q->where('status', $status),
            })
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('fulfillment_type', $type))
            ->when($filters['payment'] ?? null, fn (Builder $q, string $payment) => $q->whereHas('latestPayment', fn ($p) => $p->where('status', $payment)))
            ->when(($filters['from'] ?? null) || ($filters['to'] ?? null), function (Builder $q) use ($filters) {
                $from = BusinessTime::parseDate($filters['from'] ?? null, BusinessTime::day(-3650));
                $to = BusinessTime::parseDate($filters['to'] ?? null, BusinessTime::day());
                $q->placedBetween($from, $to);
            });
    }
}
