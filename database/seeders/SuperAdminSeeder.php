<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the first Super Admin from RESBACK_ADMIN_* environment variables.
 * Safe to run on every deploy: an existing account is never changed.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('RESBACK_ADMIN_EMAIL', ''));
        $password = (string) env('RESBACK_ADMIN_PASSWORD', '');

        if ($email === '' || $password === '') {
            $this->command?->info('RESBACK_ADMIN_EMAIL/PASSWORD not set; skipping Super Admin.');

            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->command?->info('Super Admin account already exists; leaving it unchanged.');

            return;
        }

        $nickname = env('RESBACK_ADMIN_NICKNAME', 'superadmin');

        User::create([
            'name' => $nickname,
            'nickname' => $nickname,
            'email' => $email,
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make($password),
        ]);

        $this->command?->info('Super Admin account created.');
    }
}
