<?php

namespace App\Providers;

use App\Events\FileUploadedButDbFailed;
use App\Listeners\DeleteUploadedFile;
use App\Models\Author;
use App\Models\BookTitle;
use App\Models\Edition;
use App\Models\Library;
use App\Models\LibraryApplication;
use App\Models\Publisher;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserIdentity;
use App\Policies\BookPolicy;
use App\Policies\LibraryApplicationPolicy;
use App\Policies\LibraryPolicy;
use App\Policies\UserIdentityPolicy;
use App\Policies\UserPolicy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        Gate::policy(UserIdentity::class, UserIdentityPolicy::class);
        Gate::policy(LibraryApplication::class, LibraryApplicationPolicy::class);
        Gate::policy(Library::class, LibraryPolicy::class);
        Gate::policy(Edition::class, BookPolicy::class);
        Gate::policy(BookTitle::class, BookPolicy::class);
        Gate::policy(Author::class, BookPolicy::class);
        Gate::policy(Subject::class, BookPolicy::class);
        Gate::policy(Publisher::class, BookPolicy::class);

        Str::macro('appSlug', function (string $value): string {
            $t = Str::of($value)->lower()->squish()
                ->replace(['+', '&', '@', '#'], [' plus ', ' and ', ' at ', ' sharp '])
                ->value();

            $slug = Str::slug($t);
            return $slug !== '' ? Str::limit($slug, 191, '') : Str::random(8);
        });

    }
}
