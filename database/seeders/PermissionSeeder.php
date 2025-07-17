<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use \App\Models\Permission;

class PermissionSeeder extends Seeder
{

    public function run(): void
    {
        $permissions = [
            'approve_librarian_applications',
            'approve_membership_applications',
            'manage_own_library',
            'manage_books',
            'view_books',
            'view_libraries',
            'borrow_physical_books',
            'borrow_digital_books',
            'rate_books',
            'wishlist_books',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
