<?php

namespace App\Providers;

use App\Events\FileUploadedButDbFailed;
use App\Listeners\DeleteUploadedFile;
use App\Models\Edition;
use App\Models\EditionComment;
use App\Models\LibrarianApplication;
use App\Models\LibraryComment;
use App\Models\LibraryEdition;
use App\Models\Library;
use App\Models\LibraryApplication;
use App\Models\LibraryLibrarian;
use App\Models\LibraryMember;
use App\Models\Loan;
use App\Models\MembershipApplication;
use App\Models\User;
use App\Models\UserIdentity;
use App\Policies\CommentPolicy;
use App\Policies\EditionPolicy;
use App\Policies\LibrarianApplicationPolicy;
use App\Policies\LibraryApplicationPolicy;
use App\Policies\LibraryLibrarianPolicy;
use App\Policies\LibraryMemberPolicy;
use App\Policies\LibraryPolicy;
use App\Policies\LoanPolicy;
use App\Policies\MembershipApplicationPolicy;
use App\Policies\StockPolicy;
use App\Policies\UserIdentityPolicy;
use App\Policies\UserPolicy;
use App\Services\OpenRouteServiceMatrixService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // $this->app->singleton(GoogleRouteMatrixService::class, function () {
        //     return GoogleRouteMatrixService::makeFromConfig();
        // });
        $this->app->singleton(OpenRouteServiceMatrixService::class, function () {
            return OpenRouteServiceMatrixService::makeFromConfig();
        });

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
        Gate::policy(Library::class, LibraryApplicationPolicy::class);
        Gate::policy(MembershipApplication::class, MembershipApplicationPolicy::class);
        Gate::policy(LibraryMember::class, LibraryMemberPolicy::class);
        Gate::policy(LibrarianApplication::class, LibrarianApplicationPolicy::class);
        Gate::policy(LibraryLibrarian::class, LibraryLibrarianPolicy::class);
        Gate::policy(Library::class, LibraryPolicy::class);
        Gate::policy(Edition::class, EditionPolicy::class);
        Gate::policy(LibraryEdition::class, StockPolicy::class);
        Gate::policy(Loan::class, LoanPolicy::class);
        Gate::policy(LibraryComment::class, CommentPolicy::class);
        Gate::policy(EditionComment::class, CommentPolicy::class);
        Str::macro('appSlug', function (string $value): string {
            $t = Str::of($value)->lower()->squish()
                ->replace(['+', '&', '@', '#'], [' plus ', ' and ', ' at ', ' sharp '])
                ->value();

            $slug = Str::slug($t);
            return $slug !== '' ? Str::limit($slug, 191, '') : Str::random(8);
        });
        RateLimiter::for('route-matrix', function (Request $request) {
            $key = $request->user('sanctum')->id ?? $request->ip();
            return [
                Limit::perMinute((int) config('rate.google_matrix.per_minute', 30))->by($key),
                Limit::perHour((int) config('rate.google_matrix.per_hour', 1200))->by($key),
            ];
        });

    }
}
