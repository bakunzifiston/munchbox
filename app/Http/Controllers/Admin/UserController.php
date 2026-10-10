<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()->with('role')->latest()->paginate(20);
        $roles = Role::query()->orderBy('name')->get();

        $allUsers = User::query()->with('role')->get();
        $staffCount = $allUsers->filter(fn (User $user) => $user->isStaff())->count();

        $kpis = [
            ['label' => 'Users', 'value' => $allUsers->count(), 'hint' => 'All accounts'],
            ['label' => 'Active', 'value' => $allUsers->where('is_active', true)->count(), 'hint' => 'Can sign in', 'tone' => 'success'],
            ['label' => 'Staff / admin', 'value' => $staffCount, 'hint' => 'Back-office access', 'tone' => 'info'],
            ['label' => 'Inactive', 'value' => $allUsers->where('is_active', false)->count(), 'hint' => 'Disabled accounts', 'tone' => 'warn'],
        ];

        return view('admin.users.index', compact('users', 'roles', 'kpis'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => ['required', Password::defaults()],
            'is_active' => ['nullable', 'boolean'],
        ]);

        User::query()->create([
            ...$data,
            'password' => $data['password'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'User created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => ['nullable', Password::defaults()],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role_id' => $data['role_id'],
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        return back()->with('success', 'User updated.');
    }
}
