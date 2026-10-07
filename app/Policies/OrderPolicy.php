<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff() || $user->isCustomer();
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $order->user_id === $user->id;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->isStaff();
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $order->user_id === $user->id && $order->status->value === 'pending';
    }
}
