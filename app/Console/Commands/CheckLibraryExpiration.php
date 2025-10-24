<?php

namespace App\Console\Commands;

use App\Models\Library;
use Illuminate\Console\Command;
class CheckLibraryExpiration extends Command
{
    protected $signature = 'libraries:check-library-expiration';
    protected $description = 'Cek expiration_date pada libraries dan mengubah is_verified';
    public function handle()
    {
        $expiredLibraries = Library::whereHas('latestApprovedByExpiration')
            ->where('is_active', true)
            ->get();
        $count = 0;
        foreach ($expiredLibraries as $document) {
            $document->is_active = false;
            $document->save();
            $count++;
        }
    }
}
