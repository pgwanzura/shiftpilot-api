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
    AgencyController,
    AssignmentController,
    CalendarEventsController
};
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->middleware('api')->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
    Route::post('/reset-password', [NewPasswordController::class, 'store']);
    Route::get('email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])->name('verification.verify');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'show']);
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->name('verification.send');

    Route::apiResource('assignments', AssignmentController::class);

    Route::prefix('assignments')->group(function () {
        Route::patch('{assignment}/status', [AssignmentController::class, 'changeStatus']);
        Route::post('{assignment}/complete', [AssignmentController::class, 'complete']);
        Route::post('{assignment}/suspend', [AssignmentController::class, 'suspend']);
        Route::post('{assignment}/reactivate', [AssignmentController::class, 'reactivate']);
        Route::post('{assignment}/cancel', [AssignmentController::class, 'cancel']);
        Route::post('{assignment}/extend', [AssignmentController::class, 'extend']);
        Route::get('statistics', [AssignmentController::class, 'statistics']);
        Route::get('my-assignments', [AssignmentController::class, 'myAssignments']);
    });




    Route::prefix('calendar')->group(function () {
        // Main calendar events with enhanced filtering
        Route::get('/events', [CalendarEventsController::class, 'index']);

        // Quick access endpoints
        Route::get('/upcoming-shifts', [CalendarEventsController::class, 'upcomingShifts']);
        Route::get('/pending-actions', [CalendarEventsController::class, 'pendingActions']);
        Route::get('/urgent-shifts', [CalendarEventsController::class, 'urgentShifts']);

        // Stats and analytics
        Route::get('/stats', [CalendarEventsController::class, 'eventStats']);
        Route::get('/workload-overview', [CalendarEventsController::class, 'workloadOverview']);

        // Configuration and metadata
        Route::get('/config', [CalendarEventsController::class, 'getConfig']);
        Route::get('/filters/options', [CalendarEventsController::class, 'getFilterOptions']);

        // Event actions
        Route::post('/events/{event}/action', [CalendarEventsController::class, 'executeAction']);
        Route::post('/shifts/{shift}/offer', [CalendarEventsController::class, 'offerShift']);
        Route::post('/shifts/{shift}/assign', [CalendarEventsController::class, 'assignShift']);
        Route::post('/shifts/{shift}/complete', [CalendarEventsController::class, 'completeShift']);
        Route::post('/shifts/{shift}/approve', [CalendarEventsController::class, 'approveShift']);
        Route::post('/shifts/{shift}/clock-in', [CalendarEventsController::class, 'clockIn']);
        Route::post('/shifts/{shift}/clock-out', [CalendarEventsController::class, 'clockOut']);

        // Availability management
        Route::get('/availability', [CalendarEventsController::class, 'getAvailability']);
        Route::post('/availability', [CalendarEventsController::class, 'updateAvailability']);
        Route::post('/time-off', [CalendarEventsController::class, 'requestTimeOff']);

        // Bulk operations
        Route::post('/shifts/bulk-offer', [CalendarEventsController::class, 'bulkOfferShifts']);
        Route::post('/shifts/bulk-assign', [CalendarEventsController::class, 'bulkAssignShifts']);

        // Export and reporting
        Route::get('/export', [CalendarEventsController::class, 'exportEvents']);
        Route::get('/schedule-print', [CalendarEventsController::class, 'printSchedule']);
    });
});

Route::get('/healthcheck', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'ShiftPilot API',
        'timestamp' => now()->toDateTimeString(),
        'version' => '1.0.0'
    ]);
});
