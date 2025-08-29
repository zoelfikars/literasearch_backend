<?php

namespace App\Console\Commands;

use App\Models\Library;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
class CheckLibraryExpiration extends Command
{
    protected $signature = 'libraries:check-library-expiration';
    protected $description = 'Cek expiration_date pada libraries dan mengubah is_verified';
    public function handle()
    {
        $expiredLibraries = Library::whereHas('approvedApplications', function ($query) {
            $query->where('expiration_date', '<', Carbon::today())
                ->orderByDesc('expiration_date');
        })
            ->where('is_verified', true)
            ->get();
        $count = 0;
        foreach ($expiredLibraries as $document) {
            $document->is_verified = false;
            $document->save();
            $count++;
        }
    }
}
