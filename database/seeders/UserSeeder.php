<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use League\Csv\Reader;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = database_path('/seeders/data/Users.csv');
        if (!file_exists($filePath)) {
            $this->command->error("File CSV tidak ditemukan di: $filePath");
            return;
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv->getRecords() as $index => $data) {
            try {
                $age = trim($data['Age']);

                $age = is_numeric($age) ? (int) $age : null;


                User::updateOrCreate(
                    ['temp_id' => $data['User-ID']],
                    [
                        'name' => fake()->name(),
                        'email' => fake()->unique()->safeEmail(),
                        'location' => $data['Location'],
                        'age' => empty($data['Age']) ? 0 : $data['Age'],
                    ]
                );
            } catch (\Throwable $e) {
                dump("Error di baris ke-$index:", $data, $e->getMessage());
                report($e);
            }
        }
    }
}
