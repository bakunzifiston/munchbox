<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    protected $fillable = [
        'order_id',
        'address_id',
        'address_line',
        'city',
        'district',
        'landmark',
        'driver_name',
        'driver_phone',
        'status',
        'estimated_minutes',
        'delivered_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'estimated_minutes' => 'integer',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function fullAddress(): string
    {
        return collect([$this->address_line, $this->district, $this->city, $this->landmark])
            ->filter()
            ->implode(', ');
    }
}
