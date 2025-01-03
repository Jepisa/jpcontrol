<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('Admin00123456789'),
                'role' => 'administrador',
            ]
        );

        User::updateOrCreate(
            ['email' => 'maintainer@example.com'],
            [
                'name' => 'Maintainer User',
                'password' => bcrypt('PasswordMantainer2025'),
                'role' => 'mantenedor',
            ]
        );
    }
}
