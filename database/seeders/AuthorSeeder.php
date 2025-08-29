<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AuthorSeeder extends Seeder
{
    public function run(): void
    {
        Author::create([
            'name' => 'Pramoedya Ananta Toer',
            'bio' => 'Sastrawan legendaris Indonesia yang dikenal luas lewat karya seperti "Bumi Manusia".',
            'birth_date' => '1925-02-06',
            'death_date' => '2006-04-30',
        ]);
    }
}
