<?php

namespace Database\Seeders;

use App\Models\BookTitle;
use Illuminate\Database\Seeder;
use League\Csv\Reader;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = database_path('/seeders/data/Books.csv');
        if (!file_exists($filePath)) {
            $this->command->error("File CSV tidak ditemukan di: $filePath");
            return;
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        $csv->setDelimiter(',');
        $csv->setEnclosure('"');

        foreach ($csv->getRecords() as $index => $data) {
            try {
                BookTitle::updateOrCreate(
                    ['isbn' => $data['ISBN']],
                    [
                        'title' => $data['Book-Title'],
                        'author' => $data['Book-Author'],
                        'publication_year' => $data['Year-Of-Publication'],
                        'publisher' => $data['Publisher'],
                        // 'library_id' => $data['idPerpus'],
                    ]
                );
            } catch (\Throwable $e) {
                dump("Error di baris ke-$index", $e->getMessage(), $data);
                report($e);
            }
        }
    }
}
