<?php

namespace Database\Seeders;

use App\Models\Library;
use File;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LibrarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('/seeders/data/Perpustakaan.csv');

        if (!File::exists($path)) {
            $this->command->error("File CSV tidak ditemukan di: $path");
            return;
        }

        $file = fopen($path, 'r');
        $header = fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($header, $row);

            Library::updateOrCreate(
                [
                    'name' => $data['Perpustakaan'],
                    'address' => $data['Alamat'],
                    'lon' => $data['Lon'],
                    'lat' => $data['Lat'],
                ]
            );
        }

        fclose($file);
    }
}
