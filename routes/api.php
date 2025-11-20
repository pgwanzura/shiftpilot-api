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
    CalendarEventsController,
    AgencySubscriptionController,
    PricePlanController,
    ConversationController,
    ConversationParticipantController,
    AgencyAssignmentResponseController
};
use Illuminate\Support\Facades\Route;



Route::prefix('auth')->middleware('api')->group(function (): void {
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
    Route::post('/reset-password', [NewPasswordController::class, 'store']);
    Route::get('email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])->name('verification.verify');
});

Route::get('/price-plans', [PricePlanController::class, 'index']);

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/user', [UserController::class, 'show']);
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->name('verification.send');

    Route::prefix('subscriptions')->group(function (): void {
        Route::get('/', [SubscriptionController::class, 'index'])->middleware('can:viewAny,App\Models\Subscription');
        Route::get('{subscription}', [SubscriptionController::class, 'show'])->middleware('can:view,subscription');
        Route::put('{subscription}', [SubscriptionController::class, 'update'])->middleware('can:update,subscription');
        Route::patch('{subscription}/cancel', [SubscriptionController::class, 'cancel'])->middleware('can:cancel,subscription');
        Route::patch('{subscription}/renew', [SubscriptionController::class, 'renew'])->middleware('can:renew,subscription');
    });

    Route::prefix('agency-subscriptions')->group(function (): void {
        Route::get('{agency}', [AgencySubscriptionController::class, 'show'])->middleware('can:view,agency');
        Route::post('{agency}', [AgencySubscriptionController::class, 'store'])->middleware('can:create,App\Models\Subscription');
    });

    Route::prefix('price-plans')->group(function (): void {
        Route::post('/', [PricePlanController::class, 'store'])->middleware('can:create,App\Models\PricePlan');
        Route::get('{pricePlan}', [PricePlanController::class, 'show']);
        Route::put('{pricePlan}', [PricePlanController::class, 'update'])->middleware('can:update,pricePlan');
        Route::delete('{pricePlan}', [PricePlanController::class, 'destroy'])->middleware('can:delete,pricePlan');
        Route::patch('{pricePlan}/activate', [PricePlanController::class, 'activate'])->middleware('can:update,pricePlan');
        Route::patch('{pricePlan}/deactivate', [PricePlanController::class, 'deactivate'])->middleware('can:update,pricePlan');
    });

    Route::prefix('conversations')->middleware('throttle:30,1')->group(function (): void {
        Route::get('/', [ConversationController::class, 'index']);
        Route::post('/', [ConversationController::class, 'store']);
        Route::get('unread-count', [ConversationController::class, 'getUnreadCount']);

        Route::prefix('{conversationId}')->group(function (): void {
            Route::get('/', [ConversationController::class, 'show']);
            Route::get('/messages', [ConversationController::class, 'getMessages']);
            Route::post('/messages', [ConversationController::class, 'sendMessage'])->middleware('throttle:10,1');
            Route::post('/mark-read', [ConversationController::class, 'markAsRead']);
            Route::post('/participants', [ConversationController::class, 'addParticipant']);
            Route::delete('/participants/{userId}', [ConversationController::class, 'removeParticipant']);
            Route::post('/leave', [ConversationController::class, 'leaveConversation']);
        });
    });

    Route::prefix('conversation-participants')->group(function (): void {
        Route::prefix('{participant}')->group(function (): void {
            Route::put('/', [ConversationParticipantController::class, 'update']);
            Route::post('/mute', [ConversationParticipantController::class, 'mute']);
            Route::post('/unmute', [ConversationParticipantController::class, 'unmute']);
            Route::delete('/', [ConversationParticipantController::class, 'destroy']);
        });
    });

    Route::prefix('agencies/{agency}')->group(function (): void {
        Route::get('dashboard', [AgencyController::class, 'dashboard']);
        Route::get('employees', [AgencyController::class, 'employees']);
        Route::post('employees', [AgencyController::class, 'registerEmployee'])->middleware('subscription.limit:employees');
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

    Route::prefix('agency-branches')->group(function (): void {
        Route::get('/', [AgencyBranchController::class, 'index']);
        Route::post('/', [AgencyBranchController::class, 'store']);
        Route::get('{branch}', [AgencyBranchController::class, 'show']);
        Route::put('{branch}', [AgencyBranchController::class, 'update']);
        Route::delete('{branch}', [AgencyBranchController::class, 'destroy']);
        Route::post('{branch}/head-office', [AgencyBranchController::class, 'setHeadOffice']);
        Route::get('{branch}/stats', [AgencyBranchController::class, 'stats']);
        Route::get('{branch}/nearby', [AgencyBranchController::class, 'nearby']);
    });

    Route::prefix('assignments')->group(function (): void {
        Route::get('/', [AssignmentController::class, 'index']);
        Route::post('/', [AssignmentController::class, 'store']);
        Route::get('{assignment}', [AssignmentController::class, 'show']);
        Route::put('{assignment}', [AssignmentController::class, 'update']);
        Route::delete('{assignment}', [AssignmentController::class, 'destroy']);
        Route::patch('{assignment}/status', [AssignmentController::class, 'changeStatus']);
        Route::post('{assignment}/complete', [AssignmentController::class, 'complete']);
        Route::post('{assignment}/suspend', [AssignmentController::class, 'suspend']);
        Route::post('{assignment}/reactivate', [AssignmentController::class, 'reactivate']);
        Route::post('{assignment}/cancel', [AssignmentController::class, 'cancel']);
        Route::post('{assignment}/extend', [AssignmentController::class, 'extend']);
        Route::get('statistics', [AssignmentController::class, 'statistics']);
        Route::get('my-assignments', [AssignmentController::class, 'myAssignments']);
    });

    Route::prefix('agency-assignment-responses')->group(function (): void {
        Route::post('/{response}/accept', [AgencyAssignmentResponseController::class, 'accept']);
        Route::post('/{response}/reject', [AgencyAssignmentResponseController::class, 'reject']);
        Route::get('/stats', [AgencyAssignmentResponseController::class, 'stats']);
        Route::get('/assignments/{assignmentId}', [AgencyAssignmentResponseController::class, 'forAssignment']);
        Route::get('/agency/my-responses', [AgencyAssignmentResponseController::class, 'forAgency']);
    });

    Route::prefix('employee-preferences')->group(function (): void {
        Route::get('/{employee}/preferences', [EmployeePreferencesController::class, 'getByEmployee']);
        Route::put('/{employee}/preferences', [EmployeePreferencesController::class, 'updateByEmployee']);
        Route::get('/{preference}/matching-shifts', [EmployeePreferencesController::class, 'getMatchingShifts']);
    });

    Route::prefix('calendar-events')->group(function (): void {
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

    Route::middleware('subscription.limit:employees')->group(function (): void {
        Route::post('/employees', [EmployeeController::class, 'store']);
        Route::put('/employees/{employee}', [EmployeeController::class, 'update']);
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy']);
    });

    Route::middleware('subscription.limit:shifts_per_month')->group(function (): void {
        Route::post('/shifts', [ShiftController::class, 'store']);
        Route::put('/shifts/{shift}', [ShiftController::class, 'update']);
        Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy']);
        Route::post('/shift-requests', [ShiftRequestController::class, 'store']);
        Route::put('/shift-requests/{shiftRequest}', [ShiftRequestController::class, 'update']);
        Route::delete('/shift-requests/{shiftRequest}', [ShiftRequestController::class, 'destroy']);
    });
    Route::apiResource('agencies', AgencyController::class);
    Route::apiResource('agency-assignment-responses', AgencyAssignmentResponseController::class);
    Route::apiResource('agents', AgentController::class);
    Route::apiResource('agents', AgentController::class);
    Route::apiResource('employers', EmployerController::class);
    Route::apiResource('employees', EmployeeController::class)->only(['index', 'show']);
    Route::apiResource('employee-preferences', EmployeePreferencesController::class);
    Route::apiResource('contacts', ContactController::class);
    Route::apiResource('locations', LocationController::class);
    Route::apiResource('employer-agency-contracts', EmployerAgencyContractController::class);
    Route::apiResource('shift-requests', ShiftRequestController::class)->only(['index', 'show']);
    Route::apiResource('agency-responses', AgencyResponseController::class);
    Route::apiResource('shifts', ShiftController::class)->only(['index', 'show']);
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
    Route::apiResource('audit-logs', AuditLogController::class);
    Route::apiResource('system-notifications', SystemNotificationController::class);
    Route::apiResource('webhook-subscriptions', WebhookSubscriptionController::class);
    Route::apiResource('profiles', ProfileController::class);
});

Route::get('/healthcheck', function (): array {
    return [
        'status' => 'ok',
        'service' => 'ShiftPilot API',
        'timestamp' => now()->toDateTimeString(),
        'version' => '1.0.0'
    ];
});
