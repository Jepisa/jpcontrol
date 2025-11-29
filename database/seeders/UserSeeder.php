<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('Admin00123456789'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'maintainer@example.com'],
            [
                'name' => 'Maintainer User',
                'password' => bcrypt('PasswordMantainer2025'),
            ]
        );
    }
}
