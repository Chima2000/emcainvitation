<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the account that onboards and manages hosts.
     */
    public function run(): void
    {
        $name = (string) config('emca.super_admin.name');
        $parts = preg_split('/\s+/', trim($name), 2) ?: [$name];

        User::query()->updateOrCreate(
            ['phone' => (string) config('emca.super_admin.phone')],
            [
                'name' => $name,
                'first_name' => $parts[0] !== '' ? $parts[0] : 'Super',
                'last_name' => $parts[1] ?? 'Admin',
                'email' => null,
                'password' => (string) config('emca.super_admin.password'),
                'role' => UserRole::SuperAdmin,
                'email_verified_at' => now(),
            ],
        );
    }
}
