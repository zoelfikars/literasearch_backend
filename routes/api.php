<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::middleware(['auth:sanctum', 'check.revoked'])->group(function () {
            Route::get('/user', [AuthController::class, 'user'])->name('user');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        });
        Route::prefix('password')->name('password.')->group(function () {
            Route::prefix('reset')->name('reset.')->group(function () {
                Route::post('request-otp', [AuthController::class, 'passwordResetRequestOtp'])->name('request-otp')->middleware('throttle:3,1');
                Route::post('validate-otp', [AuthController::class, 'passwordResetValidateOtp'])->name('validate-otp')->middleware('throttle:5,1');
                Route::post('', [AuthController::class, 'passwordReset'])->name('update')->middleware('throttle:5,1');
            });
        });
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('user')->name('user.')->group(function () {
            Route::prefix('profile')->name('profile.')->group(function () {
                Route::get('/', [UserProfileController::class, 'profile'])->name('profile');
                Route::post('/', [UserProfileController::class, 'update'])->name('update');
                Route::get('/picture/{user}', [UserProfileController::class, 'serveProfilePicture'])
                    ->name('picture')
                    ->middleware(['signed']);
                Route::post('/upload/identity', [UserProfileController::class, 'uploadIdentity'])->name('identity');
            });
        });
    });
    Route::prefix('books')->name('books.')->group(function () {});
    Route::prefix('libraries')->name('libraries.')->group(function () {});
});
// Route::name('api.')->group(function () {
//     Route::prefix('auth')->name('auth.')->group(function () {
//         Route::post('/register', [AuthController::class, 'register'])->name('register');
//         Route::post('/login', [AuthController::class, 'login'])->name('login');
//         Route::middleware('auth:sanctum')->group(function () {
//             Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
//             Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
//         });
//         Route::prefix('books')->name('books.')->group(function () {
//             // Route::post('/', [BookController::class, 'create'])->name('create');
//             // Route::patch('/{id}', [BookController::class, 'update'])->name('update');
//             // Route::post('/{id}', [BookController::class, 'destroy'])->name('destroy');
//         });
//     });

//     // Route::prefix('books')->name('books.')->group(function () {
//     //     Route::get('/', [BookController::class, 'index'])->name('index');
//     //     Route::get('/trending', [BookController::class, 'trending'])->name('trending');
//     //     Route::get('/{id}', [BookController::class, 'show'])->name('show');
//     //     Route::get('/{id}/libraries', [BookController::class, 'libraries'])->name('libraries');
//     // });
//     // Route::prefix('libraries')->name('books.')->group(function () {
//     //     Route::get('/', [LibraryController::class, 'index'])->name('index');
//     //     Route::get('/{id}', [LibraryController::class, 'show'])->name('show');
//     // });

//     // Route::apiResource('book-user-ratings', BookUserRatingController::class);
// });
