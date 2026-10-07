<?php

namespace App\Models;

use App\Enums\OptionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemOption extends Model
{
    protected $fillable = [
        'order_item_id',
        'product_option_id',
        'option_name',
        'option_group_name',
        'option_type',
        'price_adjustment',
    ];

    protected function casts(): array
    {
        return [
            'option_type' => OptionType::class,
            'price_adjustment' => 'decimal:2',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function productOption(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class);
    }
}
