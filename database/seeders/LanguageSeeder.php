<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            ['iso_639_1' => 'id', 'iso_639_3' => 'ind', 'english_name' => 'Indonesian', 'native_name' => 'Bahasa Indonesia', 'direction' => 'ltr'],
            ['iso_639_1' => 'en', 'iso_639_3' => 'eng', 'english_name' => 'English', 'native_name' => 'English', 'direction' => 'ltr'],
            ['iso_639_1' => 'zh', 'iso_639_3' => 'zho', 'english_name' => 'Chinese', 'native_name' => '中文', 'direction' => 'ltr'],
            ['iso_639_1' => 'ja', 'iso_639_3' => 'jpn', 'english_name' => 'Japanese', 'native_name' => '日本語', 'direction' => 'ltr'],
            ['iso_639_1' => 'ko', 'iso_639_3' => 'kor', 'english_name' => 'Korean', 'native_name' => '한국어', 'direction' => 'ltr'],
            ['iso_639_1' => 'es', 'iso_639_3' => 'spa', 'english_name' => 'Spanish', 'native_name' => 'Español', 'direction' => 'ltr'],
            ['iso_639_1' => 'fr', 'iso_639_3' => 'fra', 'english_name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr'],
            ['iso_639_1' => 'de', 'iso_639_3' => 'deu', 'english_name' => 'German', 'native_name' => 'Deutsch', 'direction' => 'ltr'],
            ['iso_639_1' => 'pt', 'iso_639_3' => 'por', 'english_name' => 'Portuguese', 'native_name' => 'Português', 'direction' => 'ltr'],
            ['iso_639_1' => 'ru', 'iso_639_3' => 'rus', 'english_name' => 'Russian', 'native_name' => 'Русский', 'direction' => 'ltr'],
            ['iso_639_1' => 'hi', 'iso_639_3' => 'hin', 'english_name' => 'Hindi', 'native_name' => 'हिन्दी', 'direction' => 'ltr'],
            ['iso_639_1' => 'tr', 'iso_639_3' => 'tur', 'english_name' => 'Turkish', 'native_name' => 'Türkçe', 'direction' => 'ltr'],
            ['iso_639_1' => 'th', 'iso_639_3' => 'tha', 'english_name' => 'Thai', 'native_name' => 'ไทย', 'direction' => 'ltr'],
            ['iso_639_1' => 'vi', 'iso_639_3' => 'vie', 'english_name' => 'Vietnamese', 'native_name' => 'Tiếng Việt', 'direction' => 'ltr'],
            ['iso_639_1' => 'ms', 'iso_639_3' => 'msa', 'english_name' => 'Malay', 'native_name' => 'Bahasa Melayu', 'direction' => 'ltr'],

            ['iso_639_1' => 'ar', 'iso_639_3' => 'ara', 'english_name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl'],
            ['iso_639_1' => 'he', 'iso_639_3' => 'heb', 'english_name' => 'Hebrew', 'native_name' => 'עברית', 'direction' => 'rtl'],
            ['iso_639_1' => 'fa', 'iso_639_3' => 'fas', 'english_name' => 'Persian', 'native_name' => 'فارسی', 'direction' => 'rtl'],
            ['iso_639_1' => 'ur', 'iso_639_3' => 'urd', 'english_name' => 'Urdu', 'native_name' => 'اُردُو', 'direction' => 'rtl'],
        ];
        foreach ($rows as $row) {
            $existing = Language::query()->where('iso_639_3', $row['iso_639_3'])->first();

            if ($existing) {
                $existing->update([
                    'iso_639_1' => $row['iso_639_1'],
                    'english_name' => $row['english_name'],
                    'native_name' => $row['native_name'],
                    'direction' => $row['direction'],
                ]);
            } else {
                Language::create([
                    'iso_639_1' => $row['iso_639_1'],
                    'iso_639_3' => $row['iso_639_3'],
                    'english_name' => $row['english_name'],
                    'native_name' => $row['native_name'],
                    'direction' => $row['direction'],
                ]);
            }
        }
    }
}
