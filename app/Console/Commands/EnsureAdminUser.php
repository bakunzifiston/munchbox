<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

class EnsureAdminUser extends Command
{
    protected $signature = 'munchbox:ensure-admin
                            {--email= : Admin email (defaults to ADMIN_EMAIL or admin@munchbox.rw)}
                            {--password= : Admin password (defaults to ADMIN_PASSWORD or password)}
                            {--name= : Admin display name}
                            {--reset-password : Force-update the password even if the user already exists}';

    protected $description = 'Ensure roles and an active admin user exist (safe for production)';

    public function handle(): int
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

        $email = $this->option('email') ?: env('ADMIN_EMAIL', 'admin@munchbox.rw');
        $password = $this->option('password') ?: env('ADMIN_PASSWORD', 'password');
        $name = $this->option('name') ?: env('ADMIN_NAME', 'MunchBox Admin');

        $user = User::query()->where('email', $email)->first();
        $created = false;

        if (! $user) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role_id' => $roles['admin']->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $created = true;
        } else {
            $user->fill([
                'name' => $name,
                'role_id' => $roles['admin']->id,
                'is_active' => true,
            ]);

            if ($this->option('reset-password') || blank($user->password)) {
                $user->password = $password;
            }

            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
            }

            $user->save();
        }

        $this->info($created
            ? "Admin user created: {$email}"
            : "Admin user updated: {$email}");

        if ($created || $this->option('reset-password')) {
            $this->warn('Password was set from --password / ADMIN_PASSWORD / default.');
        }

        return self::SUCCESS;
    }
}
