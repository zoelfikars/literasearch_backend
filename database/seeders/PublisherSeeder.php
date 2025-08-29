<?php

namespace Database\Seeders;

use App\Models\Publisher;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PublisherSeeder extends Seeder
{
    public function run(): void
    {
        $publishers = [
            [
                'name' => 'Gramedia Pustaka Utama',
                'address' => 'Kompas Gramedia Building, Jakarta, Indonesia',
            ],
            [
                'name' => 'Erlangga',
                'address' => 'Jl. H. Baping Raya, Ciracas, Jakarta Timur',
            ],
            [
                'name' => 'Deepublish',
                'address' => 'Sleman, Yogyakarta, Indonesia',
            ],
            [
                'name' => 'Mizan Media Utama',
                'address' => 'Bandung, Jawa Barat, Indonesia',
            ],
            [
                'name' => 'Andi Publisher',
                'address' => 'Yogyakarta, Indonesia',
            ],
            [
                'name' => 'Penguin Random House',
                'address' => '1745 Broadway, New York, USA',
            ],
            [
                'name' => 'HarperCollins',
                'address' => '195 Broadway, New York, USA',
            ],
            [
                'name' => 'Oxford University Press',
                'address' => 'Great Clarendon Street, Oxford, UK',
            ],
            [
                'name' => 'Springer Nature',
                'address' => 'Heidelberg, Germany',
            ],
            [
                'name' => 'Pearson Education',
                'address' => '80 Strand, London, UK',
            ],
        ];

        foreach ($publishers as $publisher) {
            Publisher::firstOrCreate([
                'name' => $publisher['name'],
                'address' => $publisher['address'],
            ]);
        }
    }
}
