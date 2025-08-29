<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name' => 'Computer Science'],
            ['name' => 'Mathematics'],
            ['name' => 'Literature'],
            ['name' => 'Engineering'],
            ['name' => 'History'],
        ];
        foreach ($subjects as $subject) {
            Subject::firstOrCreate([
                'name'=> $subject['name'],
            ]);
        }
    }
}
