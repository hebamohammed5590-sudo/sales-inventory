<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'System Admin',
                'email' => 'admin@example.com',
                'password' => 'password',
                'phone' => '01000000001',
                'role' => Role::Admin,
                'is_active' => true,
            ],
            [
                'name' => 'Store Manager',
                'email' => 'manager@example.com',
                'password' => 'password',
                'phone' => '01000000002',
                'role' => Role::Manager,
                'is_active' => true,
            ],
            [
                'name' => 'Store Cashier',
                'email' => 'cashier@example.com',
                'password' => 'password',
                'phone' => '01000000003',
                'role' => Role::Cashier,
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
