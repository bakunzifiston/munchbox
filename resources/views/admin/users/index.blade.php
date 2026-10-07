@extends('layouts.admin')

@section('title', 'Users')
@section('heading', 'Users & Roles')

@section('content')
<div class="grid gap-6 xl:grid-cols-3">
    <form method="POST" action="{{ route('admin.users.store') }}" class="rounded-3xl border border-munch/10 bg-white p-5 shadow-sm space-y-3 h-fit">
        @csrf
        <h2 class="font-display text-lg font-bold text-munch">Add user</h2>
        <input name="name" class="input-field" placeholder="Name" required>
        <input type="email" name="email" class="input-field" placeholder="Email" required>
        <input name="phone" class="input-field" placeholder="Phone">
        <select name="role_id" class="input-field" required>
            @foreach($roles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
            @endforeach
        </select>
        <input type="password" name="password" class="input-field" placeholder="Password" required>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> Active</label>
        <button class="btn-primary w-full">Create user</button>
    </form>

    <div class="xl:col-span-2 overflow-hidden rounded-3xl border border-munch/10 bg-white shadow-sm">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-cream/80 text-xs uppercase text-ink/40">
                <tr>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-forest/5">
                @foreach($users as $user)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-semibold">{{ $user->name }}</p>
                            <p class="text-xs text-ink/45">{{ $user->email }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $user->role?->name }}</td>
                        <td class="px-4 py-3">{{ $user->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="flex flex-wrap gap-2">
                                @csrf @method('PATCH')
                                <input type="hidden" name="name" value="{{ $user->name }}">
                                <input type="hidden" name="email" value="{{ $user->email }}">
                                <input type="hidden" name="phone" value="{{ $user->phone }}">
                                <select name="role_id" class="input-field !py-1.5 text-xs">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" @selected($user->role_id == $role->id)>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="is_active" value="1" @checked($user->is_active)> Active</label>
                                <button class="btn-secondary !px-3 !py-1.5 text-xs">Save</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
