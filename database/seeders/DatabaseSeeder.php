<?php

namespace Database\Seeders;

use App\Enums\OptionType;
use App\Enums\SelectionType;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\InventoryItem;
use App\Models\OptionGroup;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = collect([
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Full system access'],
            ['name' => 'Staff', 'slug' => 'staff', 'description' => 'Kitchen and order operations'],
            ['name' => 'Customer', 'slug' => 'customer', 'description' => 'Online ordering customer'],
        ])->mapWithKeys(function (array $role) {
            $model = Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                ['name' => $role['name'], 'description' => $role['description']],
            );

            return [$role['slug'] => $model];
        });

        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@munchbox.rw')],
            [
                'name' => env('ADMIN_NAME', 'MunchBox Admin'),
                'phone' => '+250799303456',
                'password' => env('ADMIN_PASSWORD', 'password'),
                'role_id' => $roles['admin']->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'staff@munchbox.rw'],
            [
                'name' => 'Kitchen Staff',
                'phone' => '+250799303457',
                'password' => 'password',
                'role_id' => $roles['staff']->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $customerUser = User::query()->updateOrCreate(
            ['email' => 'customer@munchbox.rw'],
            [
                'name' => 'Jean Uwimana',
                'phone' => '+250788123456',
                'password' => 'password',
                'role_id' => $roles['customer']->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $customer = Customer::query()->updateOrCreate(
            ['user_id' => $customerUser->id],
            [
                'name' => $customerUser->name,
                'email' => $customerUser->email,
                'phone' => $customerUser->phone,
            ],
        );

        if (! $customer->addresses()->exists()) {
            $customer->addresses()->create([
                'label' => 'Home',
                'street' => 'KG 11 Ave, Nyarutarama',
                'city' => 'Kigali',
                'district' => 'Gasabo',
                'landmark' => 'Near MTN Centre',
                'is_default' => true,
            ]);
        }

        foreach ([
            ['key' => 'business_name', 'value' => 'MunchBox', 'group' => 'general', 'type' => 'string', 'label' => 'Business Name'],
            ['key' => 'tagline', 'value' => 'Unbox the Flavor.', 'group' => 'general', 'type' => 'string', 'label' => 'Tagline'],
            ['key' => 'slogan', 'value' => 'Unbox the Flavor.', 'group' => 'general', 'type' => 'string', 'label' => 'Slogan'],
            ['key' => 'phone', 'value' => '+250 799 303 456', 'group' => 'general', 'type' => 'string', 'label' => 'Phone'],
            ['key' => 'email', 'value' => 'hello@munchbox.rw', 'group' => 'general', 'type' => 'string', 'label' => 'Email'],
            ['key' => 'address', 'value' => 'Kigali, Rwanda', 'group' => 'general', 'type' => 'string', 'label' => 'Address'],
            ['key' => 'delivery_fee', 'value' => '1500', 'group' => 'orders', 'type' => 'decimal', 'label' => 'Delivery Fee (RWF)'],
            ['key' => 'delivery_eta_minutes', 'value' => '45', 'group' => 'orders', 'type' => 'integer', 'label' => 'Delivery ETA (minutes)'],
            ['key' => 'min_order_amount', 'value' => '3000', 'group' => 'orders', 'type' => 'decimal', 'label' => 'Minimum Order'],
            ['key' => 'inventory_deduct_on_status', 'value' => 'preparing', 'group' => 'inventory', 'type' => 'string', 'label' => 'Deduct Inventory On Status'],
            ['key' => 'currency', 'value' => 'RWF', 'group' => 'general', 'type' => 'string', 'label' => 'Currency'],
            ['key' => 'opening_hours', 'value' => 'Mon–Sun 09:00–22:00', 'group' => 'general', 'type' => 'string', 'label' => 'Opening Hours'],
            ['key' => 'serving_style', 'value' => 'Served Fresh or Toasted', 'group' => 'general', 'type' => 'string', 'label' => 'Serving Style'],
        ] as $setting) {
            BusinessSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'type' => $setting['type'],
                    'label' => $setting['label'],
                ],
            );
        }

        $ingredientDefs = [
            // Proteins
            ['name' => 'Ham', 'unit' => 'pcs', 'qty' => 80, 'threshold' => 15],
            ['name' => 'Salami', 'unit' => 'pcs', 'qty' => 70, 'threshold' => 12],
            ['name' => 'Jambon Cuit', 'unit' => 'pcs', 'qty' => 55, 'threshold' => 10],
            ['name' => 'Chicken Jambon', 'unit' => 'pcs', 'qty' => 55, 'threshold' => 10],
            ['name' => 'Crispy Chicken', 'unit' => 'pcs', 'qty' => 60, 'threshold' => 12],
            ['name' => 'Beef Jambon', 'unit' => 'pcs', 'qty' => 50, 'threshold' => 10],
            ['name' => 'Eggs', 'unit' => 'pcs', 'qty' => 70, 'threshold' => 15],
            ['name' => 'Cheese', 'unit' => 'pcs', 'qty' => 100, 'threshold' => 20],
            // Toppings
            ['name' => 'Lettuce', 'unit' => 'g', 'qty' => 5000, 'threshold' => 800],
            ['name' => 'Tomatoes', 'unit' => 'pcs', 'qty' => 90, 'threshold' => 20],
            ['name' => 'Cucumbers', 'unit' => 'pcs', 'qty' => 60, 'threshold' => 12],
            ['name' => 'Pickled Onions', 'unit' => 'g', 'qty' => 2500, 'threshold' => 400],
            ['name' => 'Red Onions', 'unit' => 'pcs', 'qty' => 70, 'threshold' => 15],
            ['name' => 'Bell Peppers', 'unit' => 'pcs', 'qty' => 45, 'threshold' => 10],
            ['name' => 'Avocado', 'unit' => 'pcs', 'qty' => 40, 'threshold' => 8],
            // Sauces
            ['name' => 'Mayo', 'unit' => 'ml', 'qty' => 4000, 'threshold' => 500],
            ['name' => 'Garlic Mayo', 'unit' => 'ml', 'qty' => 1500, 'threshold' => 250],
            ['name' => 'Mustard', 'unit' => 'ml', 'qty' => 2000, 'threshold' => 300],
            ['name' => 'Honey Mustard', 'unit' => 'ml', 'qty' => 1500, 'threshold' => 250],
            ['name' => 'Sweet Chili Sauce', 'unit' => 'ml', 'qty' => 1200, 'threshold' => 200],
            ['name' => 'Garlic Herb Sauce', 'unit' => 'ml', 'qty' => 1200, 'threshold' => 200],
            // Bread
            ['name' => 'White Bread', 'unit' => 'pcs', 'qty' => 100, 'threshold' => 20],
            ['name' => 'Multigrain Bread', 'unit' => 'pcs', 'qty' => 80, 'threshold' => 15],
            ['name' => 'Baguette', 'unit' => 'pcs', 'qty' => 60, 'threshold' => 12],
            // Extras
            ['name' => 'Potato Fries', 'unit' => 'g', 'qty' => 10000, 'threshold' => 1500],
            ['name' => 'Soda Can', 'unit' => 'pcs', 'qty' => 120, 'threshold' => 24],
        ];

        $ingredients = collect();
        foreach ($ingredientDefs as $i => $def) {
            $sku = 'ING-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);

            $ingredient = Ingredient::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $def['name'],
                    'unit' => $def['unit'],
                    'is_active' => true,
                ],
            );

            InventoryItem::query()->updateOrCreate(
                ['ingredient_id' => $ingredient->id],
                [
                    'quantity_on_hand' => $def['qty'],
                    'low_stock_threshold' => $def['threshold'],
                    'unit_cost' => 100,
                ],
            );

            $ingredients->put($def['name'], $ingredient);
        }

        InventoryItem::query()
            ->where('ingredient_id', $ingredients['Beef Jambon']->id)
            ->update(['quantity_on_hand' => 8]);

        $sandwiches = Category::query()->updateOrCreate(
            ['slug' => 'signature-sandwiches'],
            [
                'name' => 'Signature Sandwiches',
                'description' => 'Our handcrafted classics — fresh or toasted',
                'sort_order' => 1,
                'is_active' => true,
            ],
        );

        $sides = Category::query()->updateOrCreate(
            ['slug' => 'sides-combos'],
            [
                'name' => 'Sides & Combos',
                'description' => 'Perfect add-ons for any sandwich',
                'sort_order' => 2,
                'is_active' => true,
            ],
        );

        $groups = [
            'style' => OptionGroup::query()->updateOrCreate(
                ['slug' => 'serving-style'],
                [
                    'name' => 'Serving Style',
                    'type' => OptionType::Style,
                    'selection_type' => SelectionType::Single,
                    'is_required' => true,
                    'min_selections' => 1,
                    'max_selections' => 1,
                    'sort_order' => 0,
                ],
            ),
            'bread' => OptionGroup::query()->updateOrCreate(
                ['slug' => 'bread-options'],
                [
                    'name' => 'Bread Options',
                    'type' => OptionType::Bread,
                    'selection_type' => SelectionType::Single,
                    'is_required' => true,
                    'min_selections' => 1,
                    'max_selections' => 1,
                    'sort_order' => 1,
                ],
            ),
            'protein' => OptionGroup::query()->updateOrCreate(
                ['slug' => 'proteins'],
                [
                    'name' => 'Proteins',
                    'type' => OptionType::Protein,
                    'selection_type' => SelectionType::Multiple,
                    'is_required' => true,
                    'min_selections' => 1,
                    'max_selections' => 5,
                    'sort_order' => 2,
                ],
            ),
            'toppings' => OptionGroup::query()->updateOrCreate(
                ['slug' => 'fresh-toppings'],
                [
                    'name' => 'Fresh Toppings',
                    'type' => OptionType::Topping,
                    'selection_type' => SelectionType::Multiple,
                    'is_required' => false,
                    'min_selections' => 0,
                    'max_selections' => 7,
                    'sort_order' => 3,
                ],
            ),
            'sauces' => OptionGroup::query()->updateOrCreate(
                ['slug' => 'sauces'],
                [
                    'name' => 'Sauces',
                    'type' => OptionType::Sauce,
                    'selection_type' => SelectionType::Multiple,
                    'is_required' => false,
                    'min_selections' => 0,
                    'max_selections' => 3,
                    'sort_order' => 4,
                ],
            ),
            'extras' => OptionGroup::query()->updateOrCreate(
                ['slug' => 'extras'],
                [
                    'name' => 'Extras',
                    'type' => OptionType::Extra,
                    'selection_type' => SelectionType::Multiple,
                    'is_required' => false,
                    'min_selections' => 0,
                    'max_selections' => 3,
                    'sort_order' => 5,
                ],
            ),
        ];

        $optionDefs = [
            'style' => [
                ['Fresh', 0, true],
                ['Toasted', 0, false],
            ],
            'bread' => [
                ['White Bread', 0, true],
                ['Multigrain Bread', 0, false],
                ['Baguette', 500, false],
            ],
            'protein' => [
                ['Ham', 0, false],
                ['Salami', 0, false],
                ['Jambon Cuit', 500, false],
                ['Chicken Jambon', 500, false],
                ['Crispy Chicken', 1000, false],
                ['Beef Jambon', 1000, false],
                ['Eggs', 500, false],
                ['Cheese', 0, true],
            ],
            'toppings' => [
                ['Lettuce', 0, true],
                ['Tomatoes', 0, true],
                ['Cucumbers', 0, true],
                ['Pickled Onions', 0, true],
                ['Red Onions', 0, false],
                ['Bell Peppers', 0, false],
                ['Avocado', 1000, false],
            ],
            'sauces' => [
                ['Mayo', 0, true],
                ['Garlic Mayo', 0, false],
                ['Mustard', 0, false],
                ['Honey Mustard', 0, false],
                ['Sweet Chili Sauce', 0, false],
                ['Garlic Herb Sauce', 0, false],
            ],
            'extras' => [
                ['Fries & Soda Extra', 3000, false],
                ['Extra Cheese', 1000, false],
            ],
        ];

        $options = collect();
        foreach ($optionDefs as $groupKey => $defs) {
            foreach ($defs as $index => [$name, $price, $default]) {
                $ingredient = $ingredients->get($name);

                $option = ProductOption::query()->updateOrCreate(
                    [
                        'option_group_id' => $groups[$groupKey]->id,
                        'name' => $name,
                    ],
                    [
                        'ingredient_id' => $ingredient?->id,
                        'price_adjustment' => $price,
                        'is_default' => $default,
                        'is_active' => true,
                        'sort_order' => $index + 1,
                    ],
                );

                if ($ingredient) {
                    $option->ingredients()->syncWithoutDetaching([
                        $ingredient->id => ['quantity_required' => 1],
                    ]);
                }

                if ($name === 'Fries & Soda Extra') {
                    $option->ingredients()->syncWithoutDetaching([
                        $ingredients['Potato Fries']->id => ['quantity_required' => 150],
                        $ingredients['Soda Can']->id => ['quantity_required' => 1],
                    ]);
                }

                $options->put($name, $option);
            }
        }

        $products = [
            [
                'name' => 'Classic Ham & Cheese',
                'price' => 6000,
                'category' => $sandwiches,
                'featured' => true,
                'description' => 'Ham, cheese, lettuce, tomatoes, cucumbers, pickled onions.',
                'defaults' => ['Ham', 'Cheese', 'Lettuce', 'Tomatoes', 'Cucumbers', 'Pickled Onions', 'Mayo', 'White Bread', 'Fresh'],
                'image' => 'images/sandwiches/classic.jpg',
                'sort' => 1,
            ],
            [
                'name' => 'Ham & Cheese Deluxe',
                'price' => 7000,
                'category' => $sandwiches,
                'featured' => true,
                'description' => 'Ham, salami, cheese, lettuce, tomatoes, cucumbers, bell peppers, pickled onions.',
                'defaults' => ['Ham', 'Salami', 'Cheese', 'Lettuce', 'Tomatoes', 'Cucumbers', 'Bell Peppers', 'Pickled Onions', 'Garlic Mayo', 'Multigrain Bread', 'Fresh'],
                'image' => 'images/sandwiches/deluxe.jpg',
                'sort' => 2,
            ],
            [
                'name' => 'Crispy Chicken Deluxe',
                'price' => 8500,
                'category' => $sandwiches,
                'featured' => true,
                'description' => 'Crispy chicken, cheese, lettuce, tomatoes, cucumbers, bell peppers, pickled onions.',
                'defaults' => ['Crispy Chicken', 'Cheese', 'Lettuce', 'Tomatoes', 'Cucumbers', 'Bell Peppers', 'Pickled Onions', 'Sweet Chili Sauce', 'Multigrain Bread', 'Fresh'],
                'image' => 'images/sandwiches/chicken.jpg',
                'sort' => 3,
            ],
            [
                'name' => 'Beef Jambon Supreme',
                'price' => 8500,
                'category' => $sandwiches,
                'featured' => true,
                'description' => 'Beef jambon, cheese, lettuce, tomatoes, cucumbers, bell peppers, pickled onions.',
                'defaults' => ['Beef Jambon', 'Cheese', 'Lettuce', 'Tomatoes', 'Cucumbers', 'Bell Peppers', 'Pickled Onions', 'Mustard', 'Baguette', 'Fresh'],
                'image' => 'images/sandwiches/beef.jpg',
                'sort' => 4,
            ],
            [
                'name' => 'All-In-One Supreme',
                'price' => 10000,
                'category' => $sandwiches,
                'featured' => true,
                'description' => 'Beef jambon, crispy chicken, ham, salami, cheese, lettuce, tomatoes, cucumbers, bell peppers, pickled onions.',
                'defaults' => ['Beef Jambon', 'Crispy Chicken', 'Ham', 'Salami', 'Cheese', 'Lettuce', 'Tomatoes', 'Cucumbers', 'Bell Peppers', 'Pickled Onions', 'Garlic Herb Sauce', 'Multigrain Bread', 'Fresh'],
                'image' => 'images/sandwiches/supreme.jpg',
                'sort' => 5,
            ],
            [
                'name' => 'Fries & Soda Extra',
                'price' => 3000,
                'category' => $sides,
                'featured' => false,
                'description' => 'Crispy fries paired with a chilled soda — the perfect combo add-on.',
                'defaults' => [],
                'image' => 'images/sandwiches/fries.jpg',
                'sort' => 6,
                'customizable' => false,
            ],
        ];

        foreach ($products as $def) {
            $product = Product::query()->updateOrCreate(
                ['slug' => Str::slug($def['name'])],
                [
                    'category_id' => $def['category']->id,
                    'name' => $def['name'],
                    'description' => $def['description'],
                    'base_price' => $def['price'],
                    'image' => $def['image'] ?? null,
                    'is_customizable' => $def['customizable'] ?? true,
                    'is_active' => true,
                    'is_featured' => $def['featured'],
                    'sort_order' => $def['sort'],
                ],
            );

            if ($product->is_customizable) {
                $product->optionGroups()->sync(collect($groups)->pluck('id'));
            }

            if ($product->category_id === $sandwiches->id) {
                $attach = [
                    $ingredients['Lettuce']->id => ['quantity_required' => 20],
                    $ingredients['Cheese']->id => ['quantity_required' => 1],
                    $ingredients['White Bread']->id => ['quantity_required' => 1],
                ];

                if (str_contains($product->name, 'Ham')) {
                    $attach[$ingredients['Ham']->id] = ['quantity_required' => 1];
                }
                if (str_contains($product->name, 'Crispy Chicken') || str_contains($product->name, 'All-In-One')) {
                    $attach[$ingredients['Crispy Chicken']->id] = ['quantity_required' => 1];
                }
                if (str_contains($product->name, 'Beef') || str_contains($product->name, 'All-In-One')) {
                    $attach[$ingredients['Beef Jambon']->id] = ['quantity_required' => 1];
                }
                if ($product->name === 'Ham & Cheese Deluxe' || $product->name === 'All-In-One Supreme') {
                    $attach[$ingredients['Salami']->id] = ['quantity_required' => 1];
                }

                $product->ingredients()->syncWithoutDetaching($attach);
            }

            if ($product->name === 'Fries & Soda Extra') {
                $product->ingredients()->syncWithoutDetaching([
                    $ingredients['Potato Fries']->id => ['quantity_required' => 150],
                    $ingredients['Soda Can']->id => ['quantity_required' => 1],
                ]);
            }
        }
    }
}
