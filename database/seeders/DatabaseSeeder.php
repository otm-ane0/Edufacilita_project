<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run the AdminSeeder first
        $this->call([
            AdminSeeder::class,
            SubjectTopicSeeder::class,
        ]);

        // Create test user (optional)
        User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '0987654321',
            'institution' => 'Test Institution',
        ]);
    }
}
