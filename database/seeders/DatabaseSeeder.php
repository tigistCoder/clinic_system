<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'System Admin',
                'username' => 'admin',
                'email' => 'admin@clinic.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => 'active',
            ],
            [
                'name' => 'Reception User',
                'username' => 'reception',
                'email' => 'reception@clinic.com',
                'password' => Hash::make('password'),
                'role' => 'reception',
                'status' => 'active',
            ],
            [
                'name' => 'Nurse Sara',
                'username' => 'nurse',
                'email' => 'nurse@clinic.com',
                'password' => Hash::make('password'),
                'role' => 'nurse',
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Abebe',
                'username' => 'doctor',
                'email' => 'doctor@clinic.com',
                'password' => Hash::make('password'),
                'role' => 'doctor',
                'status' => 'active',
            ],
            [
                'name' => 'Lab Tech Kebede',
                'username' => 'lab',
                'email' => 'lab@clinic.com',
                'password' => Hash::make('password'),
                'role' => 'lab',
                'status' => 'active',
            ],
            [
                'name' => 'Pharmacist Almaz',
                'username' => 'pharmacy',
                'email' => 'pharmacy@clinic.com',
                'password' => Hash::make('password'),
                'role' => 'pharmacy',
                'status' => 'active',
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
    }
}