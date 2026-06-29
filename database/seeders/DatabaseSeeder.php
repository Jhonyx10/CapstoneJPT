<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create your 4 main roles directly without factories
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'painter']);
        Role::firstOrCreate(['name' => 'body_builder']);
        Role::firstOrCreate(['name' => 'customer']);

        // 2. Create your default admin user
        User::factory()->create([
            'name' => 'admin',
            'email' => 'test@example.com',
            'role_id' => $adminRole->id, // Use role_id to match your table schema
            'password' => Hash::make('password'), // Sets a clear default password
        ]);
    }
}