<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $role = [
            'super_admin',
            'national_librarian',
            'librarian',
            'member',
            'user',
        ];

        foreach ($role as $item) {
            Role::firstOrCreate(['name' => $item]);
        }
    }
}
