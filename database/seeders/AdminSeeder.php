<?php

namespace Database\Seeders;

use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate([
            'email' => env('SUPER_ADMIN_EMAIL'),
        ], [
            'nickname' => 'Super Admin',
            'password' => bcrypt(env('SUPER_ADMIN_PASSWORD')),
            'status_id' => Status::where('type', 'user')->where('name', 'verified')->value('id'),
            'email_verified_at' => now(),
            'is_deleted' => false,
        ]);

        $superAdmin->syncRoles('super_admin');
    }
}
