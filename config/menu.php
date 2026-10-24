<?php

return [
    'phone' => '0799303456',
    'tagline' => 'Unbox the Flavor.',
    'slogan' => 'Unbox the Flavor.',
    'serving_style' => 'Served Fresh or Toasted',

    /*
    | Areas we deliver to. Checkout only accepts these, so add a row here
    | before promising delivery anywhere new.
    */
    'delivery_areas' => [
        ['title' => 'Kigali City', 'detail' => 'Amahoro, Kigali'],
        ['title' => 'Nyarutarama', 'detail' => 'Gasabo, Kigali'],
        ['title' => 'Kimironko', 'detail' => 'Gasabo, Kigali'],
        ['title' => 'Kacyiru', 'detail' => 'Gasabo, Kigali'],
        ['title' => 'Remera', 'detail' => 'Gasabo, Kigali'],
        ['title' => 'Gikondo', 'detail' => 'Kicukiro, Kigali'],
        ['title' => 'Nyamirambo', 'detail' => 'Nyarugenge, Kigali'],
        ['title' => 'Kicukiro', 'detail' => 'Kicukiro, Kigali'],
    ],

    /*
    | Default customization selections per product slug,
    | matching the printed sandwich menu recipes. These options are part of
    | the menu price, so they are never charged as extras for that product.
    */
    'defaults' => [
        'classic-ham-cheese' => [
            'Fresh', 'White Bread', 'Ham', 'Cheese',
            'Lettuce', 'Tomatoes', 'Cucumbers', 'Pickled Onions', 'Mayo',
        ],
        'ham-cheese-deluxe' => [
            'Fresh', 'Multigrain Bread', 'Ham', 'Salami', 'Cheese',
            'Lettuce', 'Tomatoes', 'Cucumbers', 'Bell Peppers', 'Pickled Onions', 'Garlic Mayo',
        ],
        'crispy-chicken-deluxe' => [
            'Fresh', 'Multigrain Bread', 'Crispy Chicken', 'Cheese',
            'Lettuce', 'Tomatoes', 'Cucumbers', 'Bell Peppers', 'Pickled Onions', 'Sweet Chili Sauce',
        ],
        'beef-jambon-supreme' => [
            'Fresh', 'Baguette', 'Beef Jambon', 'Cheese',
            'Lettuce', 'Tomatoes', 'Cucumbers', 'Bell Peppers', 'Pickled Onions', 'Mustard',
        ],
        'all-in-one-supreme' => [
            'Fresh', 'Multigrain Bread', 'Beef Jambon', 'Crispy Chicken', 'Ham', 'Salami', 'Cheese',
            'Lettuce', 'Tomatoes', 'Cucumbers', 'Bell Peppers', 'Pickled Onions', 'Garlic Herb Sauce',
        ],
    ],
];
