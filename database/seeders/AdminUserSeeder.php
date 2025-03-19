<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a default admin user if none exists
        if (User::where('is_admin', true)->count() === 0) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@digiscout.com',
                'password' => Hash::make('digiscout123'),
                'is_admin' => true,
            ]);
            
            $this->command->info('Admin user created: admin@digiscout.com / digiscout123');
        } else {
            $this->command->info('Admin user already exists');
        }
    }
}