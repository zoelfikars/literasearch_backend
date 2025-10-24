<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EditionController;
use App\Http\Controllers\Api\EditionStockController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\LibrarianController;
use App\Http\Controllers\Api\LibraryController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\AuthorRoleController;
use App\Http\Controllers\Api\EditionSubjectController;
use App\Http\Controllers\Api\BookTitleController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\PublisherController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\UserIdentityController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\HistoryController;
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
                    ->middleware(['signed', 'throttle:30,1']);
                Route::prefix('email/verification')->name('email.verification.')->group(function () {
                    Route::get('/request', [EmailVerificationController::class, 'emailVerificationRequest'])->name('request')
                        ->middleware('throttle:3,1');
                    Route::get('/verify/{id}/{hash}', [EmailVerificationController::class, 'emailVerificationVerify'])
                        ->middleware(['signed', 'throttle:30,1'])
                        ->name('verify');
                });
            });
            Route::prefix('identity')->name('identity.')->group(function () {
                Route::get('/', [UserIdentityController::class, 'show'])->name('get');
                Route::post('/', [UserIdentityController::class, 'store'])->name('store');
                Route::put('/', [UserIdentityController::class, 'update'])->name('update');
                Route::get('/image/{user}', [UserIdentityController::class, 'serveIdentityImage'])
                    ->name('picture')
                    ->middleware(['signed', 'throttle:30,1']);
            });
            Route::prefix('/history')->name('submissions.history.')->group(function () {
                Route::get('/', [HistoryController::class, 'list'])->name('list');
            });
        });
    });
    Route::prefix('statuses')->name('statuses.')->group(function () {
        Route::get('/', [StatusController::class, 'list'])->name('list');
    });
    Route::prefix('books')->name('books.')->group(function () {
        Route::middleware(['auth:sanctum', 'role:Verified'])->group(function () {
            Route::delete('/comments/{id}', [EditionController::class, 'commentDelete'])->name('comments.delete');
        });
        Route::prefix('authors')->name('authors.')->group(function () {
            Route::get('/', [AuthorController::class, 'list'])->name('list');
            Route::get('/roles', [AuthorRoleController::class, 'list'])->name('roles.list');
            Route::middleware(['auth:sanctum'])->group(function () {
                Route::post('/', [AuthorController::class, 'store'])->name('store');
                Route::put('/{author}', [AuthorController::class, 'update'])->name('update')->whereUuid('author');
                Route::delete('/{author}', [AuthorController::class, 'destroy'])->name('destroy')->whereUuid('author');
            });
        });
        Route::prefix('titles')->name('titles.')->group(function () {
            Route::get('/', [BookTitleController::class, 'list'])->name('list');
            Route::middleware(['auth:sanctum'])->group(function () {
                Route::post('/', [BookTitleController::class, 'store'])->name('store');
                Route::put('/{bookTitle}', [BookTitleController::class, 'update'])->name('update')->whereUuid('bookTitle');
                Route::delete('/{bookTitle}', [BookTitleController::class, 'destroy'])->name('destroy')->whereUuid('bookTitle');
            });
        });
        Route::prefix('subjects')->name('subjects.')->group(function () {
            Route::get('/', [EditionSubjectController::class, 'list'])->name('list');
            Route::middleware(['auth:sanctum'])->group(function () {
                Route::post('/', [EditionSubjectController::class, 'store'])->name('store');
                Route::put('/{subject}', [EditionSubjectController::class, 'update'])->name('update')->whereUuid('subject');
                Route::delete('/{subject}', [EditionSubjectController::class, 'destroy'])->name('destroy')->whereUuid('subject');
            });
        });
        Route::prefix('languages')->name('languages.')->group(function () {
            Route::get('/', [LanguageController::class, 'list'])->name('list');
        });
        Route::prefix('publishers')->name('publishers.')->group(function () {
            Route::get('/', [PublisherController::class, 'list'])->name('list');
            Route::middleware(['auth:sanctum'])->group(function () {
                Route::post('/', [PublisherController::class, 'store'])->name('store');
                Route::put('/{publisher}', [PublisherController::class, 'update'])->name('update')->whereUuid('publisher');
                Route::delete('/{publisher}', [PublisherController::class, 'destroy'])->name('destroy')->whereUuid('publisher');
            });
        });
        Route::get('/', [EditionController::class, 'list'])->name('list');
        Route::prefix('{book}')->group(function () {
            Route::get('/', [EditionController::class, 'show'])->name('show');
            Route::get('/cover', [EditionController::class, 'serveCover'])
                ->name('cover')
                ->whereUuid('book');
            Route::get('/read', [EditionController::class, 'serveRead'])
                ->name('read')
                ->middleware(['auth:sanctum']);
            Route::get('/libraries', [EditionController::class, 'libraries'])
                ->name('libraries')
                ->whereUuid('book');
            Route::post('/store-position', [EditionController::class, 'storePosition'])
                ->name('store-position')->middleware('auth:sanctum');
            Route::get('/comments', [EditionController::class, 'commentList'])->name('comments.list');

        });
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('/', [EditionController::class, 'store'])->name('store');
            Route::prefix('{book}')->group(function () {
                Route::post('/', [EditionController::class, 'update'])
                    ->name('update')
                    ->whereUuid('book');
                Route::post('/wishlist', [EditionController::class, 'wishlist'])->name('wishlist');
                Route::middleware(['role:Verified'])->group(function () {
                    Route::post('/rate', [EditionController::class, 'rate'])
                        ->name('rate')
                        ->whereUuid('book');
                    Route::post('/comments', [EditionController::class, 'commentStore'])->name('comments.store');
                });
            });
        });
    });
    Route::prefix('libraries')->name('libraries.')->group(function () {
        Route::get('/', [LibraryController::class, 'list'])->name('list');
        Route::middleware(['auth:sanctum', 'role:Verified', 'role:Completed Identity'])->group(function () {
            Route::post('/applications', [LibraryController::class, 'store'])->name('applications.library.store');
        });
        Route::middleware(['auth:sanctum', 'role:Verified'])->group(function () {
            Route::delete('/comments/{id}', [LibraryController::class, 'commentDelete'])->name('comments.delete');
        });
        Route::prefix('{library}')->group(function () {
            Route::get('/', [LibraryController::class, 'show'])->name('show');
            Route::get('/cover', [LibraryController::class, 'serveCover'])
                ->name('cover');
            Route::get('/directions', [LibraryController::class, 'directions'])->name('directions');
            Route::get('/comments', [LibraryController::class, 'commentList'])->name('comments.list');
            Route::prefix('books')->name('books.')->group(function () {
                Route::get('/', [EditionController::class, 'list'])->name('list');
                Route::prefix('{book}')->group(function () {
                    Route::middleware(['auth:sanctum'])->group(function () {
                        Route::middleware(['auth:sanctum', 'role:Pustakawan'])->group(function () {
                            Route::post('/', [EditionStockController::class, 'store'])->name('store');
                            Route::post('/add', [EditionStockController::class, 'add'])->name('add');
                            Route::post('/remove', [EditionStockController::class, 'remove'])->name('remove');
                            Route::delete('/delete', [EditionStockController::class, 'purgeAll'])->name('delete');
                        });
                        Route::middleware(['auth:sanctum', 'role:Verified', 'role:Completed Identity'])->group(function () {
                            Route::post('/rent', [LoanController::class, 'rent'])->name('rent.store');
                        });
                    });
                });
            });
            Route::middleware(['auth:sanctum', 'role:Verified'])->group(function () {
                Route::post('/comments', [LibraryController::class, 'commentStore'])->name('comments.store');
                Route::post('/rate', [LibraryController::class, 'rate'])->name('rate');
                Route::middleware(['role:Completed Identity'])->group(function () {
                    Route::post('/members/applications', [MembershipController::class, 'store'])->name('applications.member.store');
                    Route::post('/librarians/applications', [LibrarianController::class, 'store'])->name('applications.librarian.store');
                });
            });
            Route::middleware(['auth:sanctum', 'role:Pustakawan'])->group(function () {
                Route::post('/', [LibraryController::class, 'update'])->name('update');
                Route::post('/extend', [LibraryController::class, 'extend'])->name('extend');
                Route::prefix('members')->name('members.')->group(function () {
                    Route::get('/', [MembershipController::class, 'list'])->name('list');
                    Route::prefix('{user}')->group(function () {
                        Route::post('/activate', [MembershipController::class, 'activate'])->name('activate');
                        Route::post('/deactivate', [MembershipController::class, 'deactivate'])->name('deactivate');
                        Route::post('/blacklist', [MembershipController::class, 'blacklist'])->name('blacklist');
                        Route::post('/unblacklist', [MembershipController::class, 'unblacklist'])->name('unblacklist');
                    });
                });
                Route::prefix('librarians')->name('librarians.')->group(function () {
                    Route::get('/', [LibrarianController::class, 'list'])->name('list');
                    Route::prefix('{user}')->group(function () {
                        Route::post('/activate', [LibrarianController::class, 'activate'])->name('activate');
                        Route::post('/deactivate', [LibrarianController::class, 'deactivate'])->name('deactivate');
                    });
                });
            });
        });
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::prefix('applications')->name('applications.')->group(function () {
                Route::get('document/{application}', [LibraryController::class, 'serveLibraryApplicationDocument'])
                    ->name('document')
                    ->middleware(['signed', 'throttle:30,1']);
                Route::middleware(['role:Pustakawan Nasional'])->group(function () {
                    Route::get('{application}/', [LibraryController::class, 'showApplication'])->name('show');
                    Route::post('{application}/approve', [LibraryController::class, 'approve'])->name('approve');
                    Route::post('{application}/reject', [LibraryController::class, 'reject'])->name('reject');
                });
                Route::prefix('members')->name('members.')->group(function () {
                    Route::middleware(['role:Pustakawan'])->group(function () {
                        Route::get('{application}', [MembershipController::class, 'show'])->name('show');
                        Route::post('{application}/approve', [MembershipController::class, 'approve'])->name('approve');
                        Route::post('{application}/reject', [MembershipController::class, 'reject'])->name('reject');
                    });
                });
                Route::prefix('librarians')->name('librarians.')->group(function () {
                    Route::middleware(['role:Pustakawan'])->group(function () {
                        Route::get('{application}', [LibrarianController::class, 'show'])->name('show');
                        Route::post('{application}/approve', [LibrarianController::class, 'approve'])->name('approve');
                        Route::post('{application}/reject', [LibrarianController::class, 'reject'])->name('reject');
                    });
                });
            });
            Route::post('/loans/{loan}/return', [LoanController::class, 'return'])->name('loans.return');
            Route::middleware(['role:Pustakawan'])->group(function () {
                Route::get('/{library}/loans', [LoanController::class, 'list'])->name('loans.list');
                Route::prefix('loans/{loan}')->name('loans.')->group(function () {
                    Route::get('/', [LoanController::class, 'show'])->name('show');
                    Route::post('/approve', [LoanController::class, 'approve'])->name('approve');
                    Route::post('/reject', [LoanController::class, 'reject'])->name('reject');
                });
            });
        });
    });
});
