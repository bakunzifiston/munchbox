@extends('layouts.admin')

@section('title', 'Users')
@section('heading', 'Users & Roles')
@section('subheading', 'Staff access and account control')

@section('content')
<x-admin.kpi-grid :items="$kpis" />

<div class="grid gap-6 xl:grid-cols-3">
    <form method="POST" action="{{ route('admin.users.store') }}" class="h-fit space-y-3 rounded-[28px] border border-munch/10 bg-white p-5 shadow-sm">
        @csrf
        <h2 class="text-lg font-extrabold text-munch">Add user</h2>
        <div>
            <label class="label-field">Name</label>
            <input name="name" class="input-field" placeholder="Name" required>
        </div>
        <div>
            <label class="label-field">Email</label>
            <input type="email" name="email" class="input-field" placeholder="Email" required>
        </div>
        <div>
            <label class="label-field">Phone</label>
            <input name="phone" class="input-field" placeholder="Phone">
        </div>
        <div>
            <label class="label-field">Role</label>
            <select name="role_id" class="input-field" required>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label-field">Password</label>
            <input type="password" name="password" class="input-field" placeholder="Password" required>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" checked> Active</label>
        <button class="btn-primary w-full">Create user</button>
    </form>

    <div class="overflow-hidden rounded-[28px] border border-munch/10 bg-white shadow-sm xl:col-span-2">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-[#FAF8F4] text-[11px] font-bold uppercase tracking-wider text-ink/40">
                <tr>
                    <th class="px-5 py-3">User</th>
                    <th class="px-5 py-3">Role</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Update</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-munch/8">
                @foreach($users as $user)
                    <tr class="align-top">
                        <td class="px-5 py-4">
                            <p class="font-bold text-munch">{{ $user->name }}</p>
                            <p class="text-xs text-ink/45">{{ $user->email }}</p>
                            @if($user->phone)
                                <p class="text-xs text-ink/45">{{ $user->phone }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-ink/70">{{ $user->role?->name }}</td>
                        <td class="px-5 py-4">
                            @if($user->is_active)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-200">Active</span>
                            @else
                                <span class="rounded-full bg-stone-100 px-2.5 py-1 text-[11px] font-bold text-stone-600 ring-1 ring-stone-200">Inactive</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
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
