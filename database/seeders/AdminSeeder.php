<?php

namespace Database\Seeders;

use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate([
            'email' => env('SUPER_ADMIN_EMAIL', 'zuulfikarfikrii@gmail.com'),
            'nickname' => 'Super Admin',
            'password' => bcrypt(env('SUPER_ADMIN_PASSWORD', 'password')),
            'status_id' => Status::where('type', 'user')->where('name', 'verified')->value('id'),
            'email_verified_at' => now(),
            'is_deleted' => false,
        ]);
        $nationalLibrarian = User::firstOrCreate([
            'email' => env('NATIONAL_LIBRARIAN_EMAIL', 'pustakawanliterasearch@gmail.com'),
            'nickname' => 'Pustakawan Nasional',
            'password' => bcrypt(env('NATIONAL_LIBRARIAN_PASSWORD', 'password')),
            'status_id' => Status::where('type', 'user')->where('name', 'verified')->value('id'),
            'email_verified_at' => now(),
            'is_deleted' => false,
        ]);
        $user = User::firstOrCreate([
            'email' => 'zulfikarislami@gmail.com',
            'nickname' => 'Muhamad Zulfikar Fikri',
            'password' => bcrypt('password'),
            'status_id' => Status::where('type', 'user')->where('name', 'verified')->value('id'),
            'email_verified_at' => now(),
            'is_deleted' => false,
        ]);
        $nationalLibrarian->syncRoles(['Pustakawan Nasional', 'User', 'Verified', 'Pustakawan']);
        $superAdmin->syncRoles('Super Admin', 'Verified');
        $user->syncRoles('Verified', 'User');
    }
}
