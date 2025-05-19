<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find admin role (should be created by RoleSeeder already)
        $adminRole = Role::where('name', 'admin')->first();
        
        if (!$adminRole) {
            $this->command->error('Admin role not found! Make sure RoleSeeder has been run.');
            return;
        }
        
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('admin123'),
            ]
        );
        
        // Assign admin role
        $admin->roles()->syncWithoutDetaching($adminRole);
        
        $this->command->info('Admin user created successfully!');
    }
}
