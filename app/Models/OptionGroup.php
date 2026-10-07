<?php

namespace App\Models;

use App\Enums\OptionType;
use App\Enums\SelectionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OptionGroup extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'selection_type',
        'is_required',
        'min_selections',
        'max_selections',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => OptionType::class,
            'selection_type' => SelectionType::class,
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'min_selections' => 'integer',
            'max_selections' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('sort_order');
    }

    public function activeOptions(): HasMany
    {
        return $this->options()->where('is_active', true);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'option_group_product');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
