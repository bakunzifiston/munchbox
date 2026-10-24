<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $fillable = [
        'ingredient_id',
        'quantity_on_hand',
        'low_stock_threshold',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:2',
            'low_stock_threshold' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class)->latest();
    }

    public function formattedQuantity(): string
    {
        return self::trimNumber($this->quantity_on_hand).' '.$this->ingredient?->unit;
    }

    public function formattedThreshold(): string
    {
        return self::trimNumber($this->low_stock_threshold).' '.$this->ingredient?->unit;
    }

    private static function trimNumber(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ','), '0'), '.');
    }

    public function isLowStock(): bool
    {
        return (float) $this->quantity_on_hand <= (float) $this->low_stock_threshold;
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity_on_hand', '<=', 'low_stock_threshold');
    }
}
