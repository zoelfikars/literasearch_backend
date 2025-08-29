<?php

namespace Database\Seeders;

use App\Models\AuthorRole;
use Illuminate\Database\Seeder;

class AuthorRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'author', 'display_name' => 'Penulis'],
            ['name' => 'co_author', 'display_name' => 'Rekan Penulis'],
            ['name' => 'editor', 'display_name' => 'Penyunting'],
            ['name' => 'translator', 'display_name' => 'Penerjemah'],
            ['name' => 'illustrator', 'display_name' => 'Ilustrator'],
            ['name' => 'foreword_author', 'display_name' => 'Penulis Kata Pengantar'],
            ['name' => 'afterword_author', 'display_name' => 'Penulis Kata Penutup'],
            ['name' => 'reviewer', 'display_name' => 'Reviewer'],
            ['name' => 'contributor', 'display_name' => 'Kontributor'],
            ['name' => 'compiler', 'display_name' => 'Penyusun'],
            ['name' => 'supervisor', 'display_name' => 'Pembimbing'],
        ];

        foreach ($roles as $role) {
            AuthorRole::firstOrCreate([
                'name' => $role['name'],
                'display_name' => $role['display_name'],
            ]);
        }

    }
}
