<?php

namespace App\Providers;

use App\Events\FileUploadedButDbFailed;
use App\Listeners\DeleteUploadedFile;
use App\Models\User;
use App\Models\UserProfile;
use App\Policies\UserPolicy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }
    public function boot(): void
    {
        JsonResource::withoutWrapping();
        Event::listen(
            [
                FileUploadedButDbFailed::class,
            ],
            DeleteUploadedFile::class,
        );
        Gate::policy(User::class, UserPolicy::class);
    }
}
