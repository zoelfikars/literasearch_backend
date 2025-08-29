<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\LibrarianApplicationController;
use App\Http\Controllers\Api\LibraryApplicationController;
use App\Http\Controllers\Api\LibraryController;
use App\Http\Controllers\Api\Management\AuthorController;
use App\Http\Controllers\Api\Management\BookSubjectController;
use App\Http\Controllers\Api\Management\BookTitleController;
use App\Http\Controllers\Api\Management\LanguageController;
use App\Http\Controllers\Api\Management\LibraryController as ManageLibraryController;
use App\Http\Controllers\Api\Management\BookController as ManageBookController;
use App\Http\Controllers\Api\Management\PublisherController;
use App\Http\Controllers\Api\MembershipApplicationController;
use App\Http\Controllers\Api\UserIdentityController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::get('/user', [AuthController::class, 'user'])->name('user');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        });
        Route::prefix('forgot-password')->name('forgot-password.')->group(function () {
            Route::post('request-otp', [ForgotPasswordController::class, 'forgotPasswordRequestOtp'])->name('request-otp')
                ->middleware('throttle:3,1');
            Route::post('validate-otp', [ForgotPasswordController::class, 'forgotPasswordValidateOtp'])->name('validate-otp')->middleware('throttle:3,1');
            Route::post('reset', [ForgotPasswordController::class, 'forgotPasswordReset'])->name('update')->middleware('throttle:3,1');
        });

    });
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::prefix('user')->name('user.')->group(function (): void {
            Route::prefix('profile')->name('profile.')->group(function () {
                Route::get('/', [ProfileController::class, 'profile'])->name('get');
                Route::post('/', [ProfileController::class, 'update'])->name('update');
                Route::get('/picture/{user}', [ProfileController::class, 'serveProfilePicture'])
                    ->name('picture')
                    ->middleware(['signed']);
                Route::prefix('email/verification')->name('email.verification.')->group(function () {
                    Route::get('/request', [EmailVerificationController::class, 'emailVerificationRequest'])->name('request')
                        ->middleware('throttle:3,1');
                    Route::get('/verify/{id}/{hash}', [EmailVerificationController::class, 'emailVerificationVerify'])
                        ->middleware(['signed'])
                        ->name('verify');
                });
            });
            Route::prefix('identity')->name('identity.')->group(function () {
                Route::get('/', [UserIdentityController::class, 'show'])->name('get');
                Route::post('/', [UserIdentityController::class, 'store'])->name('store');
                Route::put('/', [UserIdentityController::class, 'update'])->name('update');
                Route::get('/image/{user}', [UserIdentityController::class, 'serveIdentityImage'])
                    ->name('picture')
                    ->middleware(['signed']);
            });
        });
    });
    Route::prefix('statuses')->name('statuses.')->group(function () {
        Route::get('/', [StatusController::class, 'list'])->name('list');
    });
    Route::prefix('books')->name('books.')->group(function () {
        Route::get('/', [LibraryController::class, 'list'])->name('list');
        Route::get('/libraries', [LibraryController::class, 'libraries'])->name('libraries');
        Route::get('/libraries', [LibraryController::class, 'libraries'])->name('list');
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('/', [ManageBookController::class, 'store'])->name('store');
            Route::prefix('titles')->name('titles.')->group(function () {
                Route::get('/', [BookTitleController::class, 'list'])->name('list');
                Route::post('/', [BookTitleController::class, 'store'])->name('store');
                Route::put('/{bookTitle}', [BookTitleController::class, 'update'])->name('update');
                Route::delete('/{bookTitle}', [BookTitleController::class, 'destroy'])->name('destroy');
            });
            Route::prefix('subjects')->name('subjects.')->group(function () {
                Route::get('/', [BookSubjectController::class, 'list'])->name('list');
                Route::post('/', [BookSubjectController::class, 'store'])->name('store');
                Route::put('/{subject}', [BookSubjectController::class, 'update'])->name('update');
                Route::delete('/{subject}', [BookSubjectController::class, 'destroy'])->name('destroy');
            });
            Route::prefix('languages')->name('languages.')->group(function () {
                Route::get('/', [LanguageController::class, 'list'])->name('list');
                // Route::post('/', [LanguageController::class, 'store'])->name('store');
                // Route::put('/{subject}', [LanguageController::class, 'update'])->name('update');
                // Route::delete('/{subject}', [LanguageController::class, 'destroy'])->name('destroy');
            });
            Route::prefix('publishers')->name('publishers.')->group(function () {
                Route::get('/', [PublisherController::class, 'list'])->name('list');
                Route::post('/', [PublisherController::class, 'store'])->name('store');
                Route::put('/{publisher}', [PublisherController::class, 'update'])->name('update');
                Route::delete('/{publisher}', [PublisherController::class, 'destroy'])->name('destroy');
            });
            Route::prefix('authors')->name('authors.')->group(function () {
                Route::get('/', [AuthorController::class, 'list'])->name('list');
                Route::post('/', [AuthorController::class, 'store'])->name('store');
                Route::put('/{author}', [AuthorController::class, 'update'])->name('update');
                Route::delete('/{author}', [AuthorController::class, 'destroy'])->name('destroy');
            });
        });
    });
    Route::prefix('libraries')->name('libraries.')->group(function () {
        Route::get('/', [LibraryController::class, 'list'])->name('list');
        Route::get('/{id}', [LibraryController::class, 'show'])->name('show');
        Route::get('/{id}/cover', [LibraryController::class, 'serveLibraryImage'])
            ->name('cover')
            ->middleware(['signed']);
        Route::get('/{id}/directions', [LibraryController::class, 'directions'])->name('directions');
        Route::get('/{id}/comments', [LibraryController::class, 'commentList'])->name('comments.list');
        Route::get('/{id}/books', [LibraryController::class, 'books'])->name('books');
        Route::middleware(['auth:sanctum', 'role:Verified'])->group(function () {
            Route::post('/{id}/comments', [LibraryController::class, 'commentStore'])->name('comments.store');
            Route::delete('/{library}/comments/{comment}', [LibraryController::class, 'commentDelete'])->name('comments.delete');
            Route::post('/{id}/rate', [LibraryController::class, 'rate'])->name('rate');
            Route::middleware(['role:Verified', 'role:Completed Identity'])->group(function () {
                Route::post('{id}/applications/member', [MembershipApplicationController::class, 'store'])->name('member.store');
                Route::post('{id}/applications/librarian', [LibrarianApplicationController::class, 'store'])->name('librarian.store');
                Route::post('/applications', [LibraryApplicationController::class, 'store'])->name('applications.store');
            });
        });
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('/{id}', [ManageLibraryController::class, 'update'])->name('update');
            Route::post('/extend', [ManageLibraryController::class, 'extend'])->name('extend');
            Route::prefix('applications')->name('applications.')->group(function () {
                Route::middleware(['role:Pustakawan|Pustakawan Nasional'])->group(function () {
                    Route::get('document/{application}', [LibraryApplicationController::class, 'serveLibraryApplicationDocument'])
                        ->name('document')
                        ->middleware(['signed']);
                });
                Route::middleware(['role:Pustakawan Nasional'])->group(function () {
                    Route::get('{application}/', [LibraryApplicationController::class, 'show'])->name('show');
                    Route::post('{application}/approve', [LibraryApplicationController::class, 'approve'])->name('approve');
                    Route::post('{application}/reject', [LibraryApplicationController::class, 'reject'])->name('reject');
                });
            });
            Route::prefix('member')->name('member.')->group(function () {
                Route::middleware(['role:Pustakawan'])->group(function () {
                    Route::get('{member}/', [MembershipApplicationController::class, 'show'])->name('show');
                    Route::post('{member}/approve', [MembershipApplicationController::class, 'approve'])->name('approve');
                    Route::post('{member}/reject', [MembershipApplicationController::class, 'reject'])->name('reject');
                });
            });
            Route::middleware(['role:Pustakawan'])->prefix('books')->name('books.')->group(function () {
                Route::get('/{book}', [ManageBookController::class, 'edit'])->name('edit');
                Route::get('/{book}', [ManageBookController::class, 'update'])->name('update');
                Route::post('/{book}', [ManageBookController::class, 'storeCollection'])->name('store');
                Route::get('/{book}', [ManageBookController::class, 'delete'])->name('delete');
                Route::prefix('stock')->name('stock.')->group(function () {
                    Route::get('/{book}', [ManageBookController::class, 'addStock'])->name('add');
                    Route::get('/{book}', [ManageBookController::class, 'removeStock'])->name('remove');
                });
            });
        });

    });
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::prefix('submissions/history')->name('submissions.history.')->group(function () {
            Route::get('/', [SubmissionController::class, 'list'])->name('list');
        });
    });
});
