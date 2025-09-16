<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        User::updateOrCreate(
            ['email' => 'admin@edufacilita.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@edufacilita.com',
                'password' => Hash::make('admin123'),
                'phone' => '1234567890',
                'institution' => 'Edufacilita',
                'role' => 'admin',
                'credit' => 0,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@edufacilita.com');
        $this->command->info('Password: admin123');
    }
}
