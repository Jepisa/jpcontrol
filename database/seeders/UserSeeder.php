<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('Admin00123456789'),
            'role' => 'administrador',
        ]);

        User::create([
            'name' => 'Maintainer User',
            'email' => 'maintainer@example.com',
            'password' => bcrypt('PasswordMantainer2025'),
            'role' => 'mantenedor',
        ]);
    }
}
