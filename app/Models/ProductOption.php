<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductOption extends Model
{
    protected $fillable = [
        'option_group_id',
        'ingredient_id',
        'name',
        'price_adjustment',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_adjustment' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function optionGroup(): BelongsTo
    {
        return $this->belongsTo(OptionGroup::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'ingredient_product_option')
            ->withPivot('quantity_required');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function formattedPriceAdjustment(): string
    {
        $amount = (float) $this->price_adjustment;

        if ($amount == 0.0) {
            return 'Included';
        }

        $prefix = $amount > 0 ? '+' : '';

        return $prefix.number_format($amount, 0, '.', ',').' RWF';
    }
}
