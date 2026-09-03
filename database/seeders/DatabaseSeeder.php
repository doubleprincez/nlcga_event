<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Package;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create default admin user
        User::firstOrCreate(
            ['email' => 'admin@eventms.com'],
            [
                'name'       => 'Admin',
                'username'   => 'admin',
                'password'   => Hash::make('password'),
                'role'       => 'admin',
                'is_admin'   => true,
                'is_staff'   => true,
                'isApproved' => 'YES',
            ]
        );

        // Default roles
        foreach (['Member', 'Corporate Member', 'Associate Member', 'Honorary Member'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Default packages
        $packages = [
            ['name' => 'Basic', 'description' => 'Basic membership package', 'duration' => '1 year', 'amount' => 50000],
            ['name' => 'Standard', 'description' => 'Standard membership package', 'duration' => '1 year', 'amount' => 100000],
            ['name' => 'Premium', 'description' => 'Premium membership package', 'duration' => '1 year', 'amount' => 250000],
        ];

        foreach ($packages as $pkg) {
            Package::firstOrCreate(['name' => $pkg['name']], $pkg);
        }
    }
}
