<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
class ClearPrivateFolderSeeder extends Seeder
{
    public function run(): void
    {
        $privateFolderPath = "private";
        if (Storage::disk($privateFolderPath)) {
            $files = Storage::disk($privateFolderPath)->allFiles();
            $count = count($files);
            if ($count > 0) {
                foreach ($files as $file) {
                    if ($file != ".gitignore") {
                        Storage::delete($files);
                    }
                }
            }
            // $directories = Storage::disk($privateFolderPath)->allDirectories($privateFolderPath);
            // $directoriesCount = count($directories);
            // if ($directoriesCount > 0) {
            //     foreach ($directories as $directory) {
            //         Log::info("looping directories $directory");
            //         Storage::deleteDirectory($directory);
            //     }
            // }
        }
    }
}
