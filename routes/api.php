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

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\PlacementController;

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

});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'show']);
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->name('verification.send');

    Route::prefix('employer')->group(function () {
        Route::get('/placements', [PlacementController::class, 'index']);
        Route::get('/placements/stats', [PlacementController::class, 'stats']);
        Route::post('/placements', [PlacementController::class, 'store']);
        Route::get('/placements/{placement}', [PlacementController::class, 'show']);
        Route::put('/placements/{placement}', [PlacementController::class, 'update']);
        Route::delete('/placements/{placement}', [PlacementController::class, 'destroy']);
        Route::post('/placements/{placement}/activate', [PlacementController::class, 'activate']);
        Route::post('/placements/{placement}/close', [PlacementController::class, 'close']);
        Route::post('/placements/{placement}/cancel', [PlacementController::class, 'cancel']);
    });

    // Route::prefix('agency')->group(function () {
    //     Route::get('/dashboard/stats', [AgencyController::class, 'getDashboardStats']);

    //     Route::get('/placements', [PlacementController::class, 'index']);
    //     Route::get('/placements/{placement}', [PlacementController::class, 'show']);
    // });


    Route::prefix('admin')->group(function () {
        Route::get('/placements', [PlacementController::class, 'index']);
        Route::get('/placements/{placement}', [PlacementController::class, 'show']);
        Route::delete('/placements/{placement}', [PlacementController::class, 'destroy']);
    });
});

Route::prefix('agency')->group(function () {
        Route::get('/dashboard/stats', [AgencyController::class, 'getDashboardStats']);

        Route::get('/placements', [PlacementController::class, 'index']);
        Route::get('/placements/{placement}', [PlacementController::class, 'show']);
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
