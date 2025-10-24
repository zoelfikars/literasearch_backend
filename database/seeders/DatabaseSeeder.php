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
            SubjectSeeder::class,
            LanguageSeeder::class,
            AuthorRoleSeeder::class,
            AuthorSeeder::class,
            // LibrarySeeder::class,
            // BookSeeder::class,
            // UserSeeder::class,
            // UserRatingSeeder::class,
            // ClearPrivateFolderSeeder::class, // jangan dipake kalo udah deploy.
        ]);
    }
}
