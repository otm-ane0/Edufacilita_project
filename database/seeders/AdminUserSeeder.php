<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if it doesn't exist
        User::firstOrCreate(
            ['email' => 'admin@supermath.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'admin@supermath.com',
                'password' => Hash::make('admin123'),
                'phone' => '1234567890',
                'institution' => 'Super Math Platform',
                'role' => 'admin',
                'credit' => 1000,
                'credit_expires_at' => now()->addYears(10),
                'email_verified_at' => now(),
            ]
        );

        echo "Admin user created successfully!\n";
        echo "Email: admin@supermath.com\n";
        echo "Password: admin123\n";
    }
}
