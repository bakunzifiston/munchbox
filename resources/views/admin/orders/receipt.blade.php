@php use App\Support\Money; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $order->order_number }}</title>
    <style>
        body { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 13px; color: #111; max-width: 320px; margin: 16px auto; padding: 0 12px; }
        h1 { font-size: 16px; margin: 0; text-align: center; }
        .center { text-align: center; }
        .muted { color: #555; }
        .row { display: flex; justify-content: space-between; gap: 8px; }
        hr { border: 0; border-top: 1px dashed #999; margin: 10px 0; }
        .opts { color: #555; font-size: 12px; margin: 0 0 4px 12px; }
        .total { font-weight: bold; font-size: 15px; }
        button { margin-top: 16px; width: 100%; padding: 8px; }
        @media print { button { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <h1>{{ $business['name'] }}</h1>
    <p class="center muted">{{ $business['address'] }} · {{ $business['phone'] }}</p>
    <hr>
    <div class="row"><span>Order</span><strong>{{ $order->order_number }}</strong></div>
    <div class="row"><span>Date</span><span>{{ $order->created_at->format('d M Y, H:i') }}</span></div>
    <div class="row"><span>Customer</span><span>{{ $order->customer_name }}</span></div>
    <div class="row"><span>Phone</span><span>{{ $order->customer_phone }}</span></div>
    <div class="row"><span>Type</span><span>{{ $order->fulfillment_type->label() }}</span></div>
    @if($order->delivery)
        <p class="muted" style="margin:4px 0 0">{{ $order->delivery->fullAddress() }}</p>
    @endif
    <hr>
    @foreach($order->items as $item)
        <div class="row"><span>{{ $item->quantity }}× {{ $item->product_name }}</span><span>{{ Money::format($item->line_total) }}</span></div>
        @if($item->options->isNotEmpty())
            <p class="opts">{{ $item->options->pluck('option_name')->join(', ') }}</p>
        @endif
        @if($item->notes)
            <p class="opts">“{{ $item->notes }}”</p>
        @endif
    @endforeach
    <hr>
    <div class="row"><span>Subtotal</span><span>{{ Money::format($order->subtotal) }}</span></div>
    @if((float) $order->delivery_fee > 0)
        <div class="row"><span>Delivery</span><span>{{ Money::format($order->delivery_fee) }}</span></div>
    @endif
    @if((float) $order->discount > 0)
        <div class="row"><span>Discount</span><span>−{{ Money::format($order->discount) }}</span></div>
    @endif
    <div class="row total"><span>Total</span><span>{{ Money::format($order->total) }}</span></div>
    @php $payment = $order->payments->sortByDesc('id')->first(); @endphp
    @if($payment)
        <div class="row muted"><span>{{ $payment->method->label() }}</span><span>{{ $payment->status->label() }}</span></div>
    @endif
    <hr>
    <p class="center">Thank you! · Unbox the Flavor.</p>
    <button type="button" onclick="window.print()">Print</button>
</body>
</html>
