<?php

namespace App\Enums;

enum OptionType: string
{
    case Style = 'style';
    case Protein = 'protein';
    case Topping = 'topping';
    case Sauce = 'sauce';
    case Bread = 'bread';
    case Extra = 'extra';

    public function label(): string
    {
        return match ($this) {
            self::Style => 'Serving Style',
            self::Protein => 'Protein',
            self::Topping => 'Fresh Toppings',
            self::Sauce => 'Sauces',
            self::Bread => 'Bread',
            self::Extra => 'Extras',
        };
    }
}
