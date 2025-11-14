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
    AgencyBranchController,
    AgentController,
    EmployerController,
    ContactController,
    EmployeeController,
    EmployeePreferencesController,
    LocationController,
    EmployerAgencyContractController,
    ShiftRequestController,
    AgencyResponseController,
    ShiftController,
    ShiftOfferController,
    ShiftApprovalController,
    TimesheetController,
    EmployeeAvailabilityController,
    TimeOffRequestController,
    InvoiceController,
    PaymentController,
    PaymentLogController,
    PayoutController,
    PayrollController,
    RateCardController,
    SubscriptionController,
    AuditLogController,
    SystemNotificationController,
    WebhookSubscriptionController,
    ProfileController,
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

    Route::prefix('agencies/{agency}')->group(function () {
        Route::get('dashboard', [AgencyController::class, 'dashboard']);
        Route::get('employees', [AgencyController::class, 'employees']);
        Route::post('employees', [AgencyController::class, 'registerEmployee']);
        Route::put('employees/{employeeId}', [AgencyController::class, 'updateEmployee']);
        Route::get('assignments', [AgencyController::class, 'assignments']);
        Route::post('assignments', [AgencyController::class, 'createAssignment']);
        Route::put('assignments/{assignmentId}', [AgencyController::class, 'updateAssignment']);
        Route::get('contracts', [AgencyController::class, 'contracts']);
        Route::post('contracts/{employerId}', [AgencyController::class, 'syncEmployerContract']);
        Route::get('available-employees', [AgencyController::class, 'availableEmployees']);
        Route::post('timesheets/{timesheetId}/approve', [AgencyController::class, 'approveTimesheet']);
        Route::get('timesheets', [AgencyController::class, 'getTimesheets']);
        Route::post('payroll/process', [AgencyController::class, 'processPayroll']);
        Route::get('payroll', [AgencyController::class, 'getPayroll']);
        Route::get('payouts', [AgencyController::class, 'getPayouts']);
        Route::post('assignments/{assignmentId}/invoice', [AgencyController::class, 'generateInvoice']);
        Route::get('invoices', [AgencyController::class, 'getInvoices']);
        Route::post('shift-requests/{shiftRequestId}/response', [AgencyController::class, 'submitShiftResponse']);
        Route::post('responses/{responseId}/assignment', [AgencyController::class, 'createAssignmentFromResponse']);
        Route::post('shifts/{shiftId}/offer', [AgencyController::class, 'offerShift']);
        Route::get('shifts', [AgencyController::class, 'getShifts']);
        Route::post('templates/{templateId}/shift', [AgencyController::class, 'createShiftFromTemplate']);
        Route::get('response-stats', [AgencyController::class, 'getAgencyResponseStats']);
    });

    Route::prefix('agency-branches')->group(function () {
        Route::get('/', [AgencyBranchController::class, 'index']);
        Route::post('/', [AgencyBranchController::class, 'store']);
        Route::get('{branch}', [AgencyBranchController::class, 'show']);
        Route::put('{branch}', [AgencyBranchController::class, 'update']);
        Route::delete('{branch}', [AgencyBranchController::class, 'destroy']);
        Route::post('{branch}/head-office', [AgencyBranchController::class, 'setHeadOffice']);
        Route::get('{branch}/stats', [AgencyBranchController::class, 'stats']);
        Route::get('{branch}/nearby', [AgencyBranchController::class, 'nearby']);
    });

    // Existing Assignment Routes
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

    Route::prefix('employee-preferences')->group(function () {
        Route::get('/{employee}/preferences', [EmployeePreferencesController::class, 'getByEmployee']);
        Route::put('/{employee}/preferences', [EmployeePreferencesController::class, 'updateByEmployee']);
        Route::get('/{preference}/matching-shifts', [EmployeePreferencesController::class, 'getMatchingShifts']);
    });


    Route::prefix('calendar-events')->group(function () {
        Route::get('/', [CalendarEventsController::class, 'index']);
        Route::get('/config', [CalendarEventsController::class, 'getCalendarConfig']);
        Route::get('/filter-options', [CalendarEventsController::class, 'getFilterOptions']);
        Route::get('/upcoming-shifts', [CalendarEventsController::class, 'upcomingShifts']);
        Route::get('/pending-actions', [CalendarEventsController::class, 'pendingActions']);
        Route::get('/availability-conflicts', [CalendarEventsController::class, 'availabilityConflicts']);
        Route::get('/event-stats', [CalendarEventsController::class, 'eventStats']);
        Route::get('/workload-overview', [CalendarEventsController::class, 'workloadOverview']);

        Route::post('/check-shift-conflicts', [CalendarEventsController::class, 'checkShiftConflicts']);
        Route::post('/{eventType}/{eventId}/action', [CalendarEventsController::class, 'executeAction']);
        Route::post('/shifts/{shiftId}/offer', [CalendarEventsController::class, 'offerShift']);
        Route::post('/shift-offers/{offerId}/respond', [CalendarEventsController::class, 'respondToShiftOffer']);
        Route::post('/shifts/{shiftId}/clock-in', [CalendarEventsController::class, 'clockIn']);
        Route::post('/shifts/{shiftId}/clock-out', [CalendarEventsController::class, 'clockOut']);
        Route::post('/timesheets/{timesheetId}/approve', [CalendarEventsController::class, 'approveTimesheet']);
        Route::post('/time-off', [CalendarEventsController::class, 'requestTimeOff']);
        Route::post('/time-off/{timeOffId}/approve', [CalendarEventsController::class, 'approveTimeOff']);

        Route::put('/availability', [CalendarEventsController::class, 'updateAvailability']);
    });


    Route::apiResource('agencies', AgencyController::class);
    Route::apiResource('agents', AgentController::class);
    Route::apiResource('employers', EmployerController::class);
    Route::apiResource('employees', EmployeeController::class);
    Route::apiResource('employee-preferences', EmployeePreferencesController::class);
    Route::apiResource('contacts', ContactController::class);
    Route::apiResource('locations', LocationController::class);
    Route::apiResource('employer-agency-contracts', EmployerAgencyContractController::class);
    Route::apiResource('shift-requests', ShiftRequestController::class);
    Route::apiResource('agency-responses', AgencyResponseController::class);
    Route::apiResource('shifts', ShiftController::class);
    Route::apiResource('shift-offers', ShiftOfferController::class);
    Route::apiResource('shift-approvals', ShiftApprovalController::class);
    Route::apiResource('timesheets', TimesheetController::class);
    Route::apiResource('employee-availabilities', EmployeeAvailabilityController::class);
    Route::apiResource('time-off-requests', TimeOffRequestController::class);
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('payment-logs', PaymentLogController::class);
    Route::apiResource('payouts', PayoutController::class);
    Route::apiResource('payrolls', PayrollController::class);
    Route::apiResource('rate-cards', RateCardController::class);
    Route::apiResource('subscriptions', SubscriptionController::class);
    Route::apiResource('audit-logs', AuditLogController::class);
    Route::apiResource('system-notifications', SystemNotificationController::class);
    Route::apiResource('webhook-subscriptions', WebhookSubscriptionController::class);
    Route::apiResource('profiles', ProfileController::class);
});

Route::get('/healthcheck', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'ShiftPilot API',
        'timestamp' => now()->toDateTimeString(),
        'version' => '1.0.0'
    ]);
});
