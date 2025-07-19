<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StatusSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            AdminSeeder::class,
            // LibrarySeeder::class,
            // BookSeeder::class,
            // UserSeeder::class,
            // UserRatingSeeder::class,
        ]);
    }
}
