<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD = 'Crispy fries paired with a chilled soda — the perfect combo add-on.';

    private const NEW = 'Crispy fries paired with a chilled soda.';

    /**
     * Only touches the row if it still has the originally seeded text, so
     * descriptions edited in the admin panel are left alone.
     */
    public function up(): void
    {
        DB::table('products')
            ->where('slug', 'fries-soda-extra')
            ->where('description', self::OLD)
            ->update(['description' => self::NEW, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('products')
            ->where('slug', 'fries-soda-extra')
            ->where('description', self::NEW)
            ->update(['description' => self::OLD, 'updated_at' => now()]);
    }
};
