<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $rolePermissions = [
            'super_admin' => [
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
            ],
            'national_librarian' => [
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
            ],
            'librarian' => [
                'approve_membership_applications',
                'manage_own_library',
                'manage_books',
                'view_books',
                'view_libraries',
                'borrow_physical_books',
                'borrow_digital_books',
                'rate_books',
                'wishlist_books',
            ],
            'member' => [
                'view_books',
                'view_libraries',
                'borrow_physical_books',
                'borrow_digital_books',
                'rate_books',
                'wishlist_books',
            ],
            'user' => [
                'view_books',
                'view_libraries',
                'borrow_digital_books',
                'wishlist_books',
            ],
        ];

        foreach ($rolePermissions as $role => $perms) {
            $roleModel = Role::firstOrCreate(['name' => $role]);
            $roleModel->syncPermissions($perms);
        }
    }
}
