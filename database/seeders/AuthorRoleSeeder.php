<?php

namespace Database\Seeders;

use App\Models\AuthorRole;
use Illuminate\Database\Seeder;
use Str;

class AuthorRoleSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // Penulisan & penyuntingan
            ['name' => 'Penulis'],
            ['name' => 'Co-penulis'],
            ['name' => 'Editor'],
            ['name' => 'Editor Seri'],
            ['name' => 'Editor Volume'],
            ['name' => 'Penyusun (Compiler)'],
            ['name' => 'Penyunting Teknis'],
            ['name' => 'Revisor/Pemutakhir'],

            // Terjemahan & adaptasi
            ['name' => 'Penerjemah'],
            ['name' => 'Adaptor'],

            // Ilustrasi & visual
            ['name' => 'Ilustrator'],
            ['name' => 'Fotografer'],
            ['name' => 'Desainer Sampul'],
            ['name' => 'Kartografer (Peta)'],

            // Materi pendamping
            ['name' => 'Penulis Prakata (Foreword)'],
            ['name' => 'Penulis Kata Pengantar'],
            ['name' => 'Penulis Pengantar (Introduction)'],
            ['name' => 'Penulis Epilog/Afterword'],
            ['name' => 'Penulis Anotasi'],
            ['name' => 'Penyusun Indeks'],
            ['name' => 'Komentator'],
            ['name' => 'Narator (Audio)'],

            // Kontributor umum
            ['name' => 'Kontributor'],
            ['name' => 'Peneliti'],
            ['name' => 'Konsultan'],
            ['name' => 'Penasihat'],
            ['name' => 'Koordinator'],
        ];
        foreach ($rows as $row) {
            AuthorRole::updateOrCreate(
                [
                    'name' => $row['name'],
                    'slug' => Str::appSlug($row['name']),
                ]
            );
        }
    }
}
