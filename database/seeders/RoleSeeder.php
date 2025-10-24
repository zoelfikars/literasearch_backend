<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $role = [
            'Super Admin',
            'Pustakawan Nasional',
            'Pustakawan',
            'User',
            'Member',
            'Verified',
            'Completed Identity',
            'Blacklist',
        ];

        foreach ($role as $item) {
            Role::firstOrCreate(['name' => $item]);
        }
    }
}
