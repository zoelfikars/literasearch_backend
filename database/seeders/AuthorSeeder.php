<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Str;

class AuthorSeeder extends Seeder
{
    public function run(): void
    {
        $name = 'Pramoedya Ananta Toer';
        $slug = Str::appSlug($name);
        $disambiguator = '2006-04-30';
        Author::create([
            'name' => $name,
            'slug' => $slug,
            'disambiguator' => $disambiguator,
        ]);
    }
}
