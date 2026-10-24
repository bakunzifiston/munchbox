<?php

namespace App\Models;

use App\Models\Concerns\UsesBusinessTimezone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use UsesBusinessTimezone;

    protected $fillable = [
        'customer_id',
        'label',
        'street',
        'city',
        'district',
        'landmark',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function fullAddress(): string
    {
        return collect([$this->street, $this->district, $this->city, $this->landmark])
            ->filter()
            ->implode(', ');
    }
}
