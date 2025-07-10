<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;
use League\Csv\Reader;

class UserRatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = database_path('/seeders/data/Ratings.csv');
        if (!file_exists($filePath)) {
            $this->command->error("File CSV tidak ditemukan di: $filePath");
            return;
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        $error = [];
        foreach ($csv->getRecords() as $index => $data) {
            try {
                $user = User::findOrFail($data['User-ID']);
                $book = Book::where('isbn', $data['ISBN'])->firstOrFail();
                $rating = is_numeric($data['Book-Rating']) ? (int) $data['Book-Rating'] : 0;
                $user->bookRatings()->attach($book->id, [
                    'rating' => $rating,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } catch (\Throwable $e) {
                $error[] = $index;
                // dump("Error di baris ke-$index:", $data, $e->getMessage());
                // report($e);
            }
        }
        dump("Error di ", $error);
    }
}
