<?php

namespace App\Listeners;

use App\Events\FileUploadedButDbFailed;
use Illuminate\Support\Facades\Storage;

class DeleteUploadedFile
{
    public function __construct()
    {
    }
    public function handle(FileUploadedButDbFailed $event): void
    {
        foreach ($event->filePaths as $filePath) {
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }
        }
    }
}
