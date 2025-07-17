<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::name('api.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::middleware(['auth:sanctum', 'check.revoked'])->group(function () {
            Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        });
        Route::post('request-otp', [AuthController::class, 'requestOtp'])->middleware('throttle:3,1');
        Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::post('/upload/identity', [UserProfileController::class, 'uploadIdentity'])->name('identity');
            Route::post('/upload/selfie', [UserProfileController::class, 'uploadSelfie'])->name('selfie');
            Route::post('/update', [UserProfileController::class, 'update'])->name('update');
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
