<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Roles
        $roles = collect([
            'super_admin',
            'national_librarian',
            'librarian',
            'member',
        ])->map(fn($role) => Role::firstOrCreate(['name' => $role]));

        // Permissions
        $permissions = collect([
            'approve_librarian_applications',
            'approve_membership_applications',
            'manage_own_library',
            'manage_books',
            'view_books',
            'borrow_books',
            'rate_books',
            'wishlist_books',
        ])->map(fn($permission) => Permission::firstOrCreate(['name' => $permission]));

        // Role-Permission mapping
        $rolePermissions = [
            'super_admin' => [
                'approve_librarian_applications',
                'approve_membership_applications',
                'manage_own_library',
                'manage_books',
                'view_books',
                'borrow_books',
                'rate_books',
                'wishlist_books',
            ],
            'national_librarian' => [
                'approve_librarian_applications',
                'view_books'
            ],
            'librarian' => [
                'manage_own_library',
                'manage_books',
                'approve_membership_applications',
                'view_books',
                'borrow_books',
                'rate_books',
                'wishlist_books'
            ],
            'member' => [
                'view_books',
                'borrow_books',
                'rate_books',
                'wishlist_books'
            ],
        ];

        foreach ($rolePermissions as $role => $perms) {
            $roleModel = Role::where('name', $role)->first();
            $permIds = Permission::whereIn('name', $perms)->pluck('id');
            $roleModel->permissions()->sync($permIds);
        }

        // Statuses
        $statuses = [
            // User statuses
            ['type' => 'user', 'name' => 'pending_verification', 'description' => 'Belum verifikasi'],
            ['type' => 'user', 'name' => 'verified', 'description' => 'Sudah verifikasi'],
            ['type' => 'user', 'name' => 'blacklisted', 'description' => 'Akun diblokir'],

            // Librarian application
            ['type' => 'librarian_application', 'name' => 'pending', 'description' => 'Menunggu verifikasi pustakawan'],
            ['type' => 'librarian_application', 'name' => 'approved', 'description' => 'Pengajuan disetujui'],
            ['type' => 'librarian_application', 'name' => 'rejected', 'description' => 'Pengajuan ditolak'],

            // Membership application
            ['type' => 'membership_application', 'name' => 'pending', 'description' => 'Menunggu verifikasi member'],
            ['type' => 'membership_application', 'name' => 'approved', 'description' => 'Pengajuan disetujui'],
            ['type' => 'membership_application', 'name' => 'rejected', 'description' => 'Pengajuan ditolak'],

            // Loan statuses
            ['type' => 'loan', 'name' => 'pending', 'description' => 'Menunggu persetujuan'],
            ['type' => 'loan', 'name' => 'approved', 'description' => 'Peminjaman disetujui'],
            ['type' => 'loan', 'name' => 'rejected', 'description' => 'Peminjaman ditolak'],
            ['type' => 'loan', 'name' => 'returned', 'description' => 'Buku dikembalikan'],
            ['type' => 'loan', 'name' => 'overdue', 'description' => 'Terlambat mengembalikan'],
        ];

        foreach ($statuses as $status) {
            Status::firstOrCreate([
                'type' => $status['type'],
                'name' => $status['name'],
            ], ['description' => $status['description']]);
        }
        $superAdmin = User::firstOrCreate([
            'email' => env('SUPER_ADMIN_EMAIL'),
        ], [
            'username' => 'Super Admin',
            'password' => bcrypt(env('SUPER_ADMIN_PASSWORD')),
            'status_id' => Status::where('type', 'user')->where('name', 'verified')->value('id'),
            'email_verified_at' => now(),
            'is_deleted' => false,
        ]);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);

    }
}
