<?php

namespace App\Services;

use App\Enums\SelectionType;
use App\Models\OptionGroup;
use App\Models\Product;
use App\Models\ProductOption;
use App\Support\Money;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for what a configured product costs.
 *
 * Options that belong to the product's recipe are part of the menu price and
 * are never charged; removing them does not lower the price. Any other option
 * is charged at its configured price adjustment.
 */
class MenuPricing
{
    /**
     * @return list<int>
     */
    public function includedOptionIds(Product $product): array
    {
        $product->loadMissing('optionGroups.activeOptions');

        $recipe = config('menu.defaults.'.$product->slug, []);
        $ids = [];

        foreach ($product->optionGroups as $group) {
            foreach ($group->activeOptions as $option) {
                $included = $recipe
                    ? in_array($option->name, $recipe, true)
                    : $option->is_default;

                if ($included) {
                    $ids[] = $option->id;
                }
            }
        }

        return $ids;
    }

    /**
     * What a customer pays for this option on this product (0 when it is part of the recipe).
     *
     * @param  list<int>  $includedIds
     */
    public function chargeFor(ProductOption $option, array $includedIds): float
    {
        if (in_array($option->id, $includedIds, true)) {
            return 0.0;
        }

        return max(0.0, Money::from($option->price_adjustment));
    }

    /**
     * Validate a selection against the product's active option groups.
     *
     * @param  list<int>  $optionIds
     *
     * @throws ValidationException
     */
    public function validateSelection(Product $product, array $optionIds): void
    {
        if (! $product->is_customizable) {
            return;
        }

        $product->loadMissing('optionGroups.activeOptions');

        $groups = $product->optionGroups->where('is_active', true);
        $allowedIds = $groups->flatMap(fn (OptionGroup $group) => $group->activeOptions->pluck('id'))->all();

        if (array_diff($optionIds, $allowedIds) !== []) {
            throw ValidationException::withMessages([
                'options' => 'Some of your choices are no longer available. Please review them and try again.',
            ]);
        }

        foreach ($groups as $group) {
            $selected = $group->activeOptions->whereIn('id', $optionIds)->count();
            $max = $group->selection_type === SelectionType::Single ? 1 : max(1, $group->max_selections);

            if ($group->is_required && $selected < max(1, $group->min_selections)) {
                throw ValidationException::withMessages([
                    'options' => "Please choose your {$group->name}.",
                ]);
            }

            if ($selected > $max) {
                throw ValidationException::withMessages([
                    'options' => "You can choose up to {$max} for {$group->name}.",
                ]);
            }
        }
    }

    /**
     * Price a validated selection from current database values.
     *
     * @param  list<int>  $optionIds
     * @return array{unit_price: float, options_total: float, options: list<array{id: int, name: string, group: string, type: string, price_adjustment: float, included: bool}>}
     */
    public function quote(Product $product, array $optionIds): array
    {
        $options = [];

        if ($product->is_customizable && $optionIds !== []) {
            $product->loadMissing('optionGroups.activeOptions');
            $includedIds = $this->includedOptionIds($product);

            foreach ($product->optionGroups as $group) {
                foreach ($group->activeOptions as $option) {
                    if (! in_array($option->id, $optionIds, true)) {
                        continue;
                    }

                    $options[] = [
                        'id' => $option->id,
                        'name' => $option->name,
                        'group' => $group->name,
                        'type' => $group->type->value,
                        'price_adjustment' => $this->chargeFor($option, $includedIds),
                        'included' => in_array($option->id, $includedIds, true),
                    ];
                }
            }
        }

        return [
            'unit_price' => Money::from($product->base_price),
            'options_total' => Money::from(array_sum(array_column($options, 'price_adjustment'))),
            'options' => $options,
        ];
    }
}
