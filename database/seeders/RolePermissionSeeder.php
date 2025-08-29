<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $rolePermissions = [
            'Super Admin' => [
                'review_library_applications',
                'review_membership_applications',
                'manage_own_library',
                'manage_books',
                'view_books',
                'view_libraries',
                'borrow_physical_books',
                'borrow_digital_books',
                'rate_books',
                'wishlist_books',
                'request_librarian_application',
                'request_member_application',
            ],
            'Pustakawan Nasional' => [
                'review_library_applications',
            ],
            'Pustakawan' => [
                'review_membership_applications',
                'manage_own_library',
                'manage_books',
            ],
            'User' => [
                'view_books',
                'view_libraries',
                'wishlist_books',
            ],
            'Verified' => [
                'borrow_digital_books',
                'rate_books',
            ],
            'Completed Identity' => [
                'request_librarian_application',
                'request_member_application',
            ],
        ];

        foreach ($rolePermissions as $role => $perms) {
            $roleModel = Role::firstOrCreate(['name' => $role]);
            $roleModel->syncPermissions($perms);
        }
    }
}
