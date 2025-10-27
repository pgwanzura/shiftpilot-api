<?php

use App\Http\Controllers\Auth\{
    AuthenticatedSessionController,
    RegisteredUserController,
    EmailVerificationNotificationController,
    PasswordResetLinkController,
    NewPasswordController,
    VerifyEmailController
};
use App\Http\Controllers\{
    UserController,
    // RefreshTokenController
};

use Illuminate\Support\Facades\Route;

/*-------------------------------------------------------------------------
| Authentication Routes
|------------------------------------------------------------------------*/

Route::prefix('auth')->middleware('api')->group(function () {
    // Public auth endpoints
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
    Route::post('/reset-password', [NewPasswordController::class, 'store']);

    // Email verification should be PUBLIC (no auth required)
    Route::get('email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
        ->name('verification.verify');

    // Authenticated-only auth endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [UserController::class, 'show']);
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->name('verification.send');
    });
});


/*-------------------------------------------------------------------------
| Public Routes
|------------------------------------------------------------------------*/
Route::get('/healthcheck', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'ShiftPilot API',
        'timestamp' => now()->toDateTimeString(),
        'version' => '1.0.0'
    ]);
});

// Public availability check (for employees/pilots)

