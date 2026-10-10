<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $customer = $user->customer;
        $orders = $user->orders()->latest()->limit(5)->get();
        $addresses = $customer?->addresses ?? collect();

        return view('customer.account.show', compact('user', 'customer', 'orders', 'addresses'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'min:9', 'max:30', 'regex:/^\+?[0-9\s()-]+$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
        ]);

        $user = $request->user();
        $user->update($data);

        if ($user->customer) {
            $user->customer->update([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
            ]);
        }

        return back()->with('success', 'Account updated.');
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'street' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $customer = $this->ensureCustomer($request);

        if (! empty($data['is_default'])) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $customer->addresses()->create([
            ...$data,
            'city' => $data['city'] ?? 'Kigali',
            'is_default' => (bool) ($data['is_default'] ?? $customer->addresses()->count() === 0),
        ]);

        return back()->with('success', 'Address saved.');
    }

    public function destroyAddress(Address $address): RedirectResponse
    {
        abort_unless($address->customer?->user_id === auth()->id(), 403);
        $address->delete();

        return back()->with('success', 'Address removed.');
    }

    private function ensureCustomer(Request $request): Customer
    {
        $user = $request->user();

        return $user->customer ?? Customer::query()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
        ]);
    }
}
