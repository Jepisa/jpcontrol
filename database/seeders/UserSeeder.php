<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('Admin00123456789'),
                'status' => UserStatus::Active,
            ]
        );
        $admin->assignRole('Administrador');

        $maintainer = User::updateOrCreate(
            ['email' => 'mantenimiento@example.com'],
            [
                'name' => 'Mantenimiento User',
                'password' => bcrypt('PasswordMantainer2025'),
                'status' => UserStatus::Active,
            ]
        );
        $maintainer->assignRole('Mantenimiento');

        $soporte = User::updateOrCreate(
            ['email' => 'soporte@example.com'],
            [
                'name' => 'Soporte User',
                'password' => bcrypt('PasswordSoporte2025'),
                'status' => UserStatus::Active,
            ]
        );
        $soporte->assignRole('Soporte');
    }
}
