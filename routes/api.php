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
    RefreshTokenController
};
use App\Http\Controllers\Api\{
    AdminController,
    AgencyController,
    CandidateController,
    ContactController,
    DashboardController,
    EmployeeController,
    EmployerController,
    InvoiceController,
    LocationController,
    PaymentController,
    PayrollController,
    PayoutController,
    PlacementController,
    ShiftController,
    ShiftApprovalController,
    ShiftOfferController,
    ShiftTemplateController,
    SubscriptionController,
    TimeOffController,
    TimesheetController,
    UserManagementController
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
| Authenticated API Routes
|------------------------------------------------------------------------*/
Route::middleware(['auth:sanctum'])->group(function () {

    // =============================================
    // ADMIN ROUTES
    // =============================================
    Route::prefix('admin')->middleware(['role:super_admin'])->group(function () {
        Route::get('/dashboard/stats', [DashboardController::class, 'adminStats']);
        Route::get('/invoices', [InvoiceController::class, 'indexAdmin']);
        Route::get('/payments', [PaymentController::class, 'indexAdmin']);
        Route::get('/payouts', [PayoutController::class, 'indexAdmin']);
        Route::get('/subscriptions', [SubscriptionController::class, 'indexAdmin']);
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::patch('/users/{user}/status', [UserManagementController::class, 'updateStatus']);
    });

    // =============================================
    // AGENCY ROUTES
    // =============================================
    Route::prefix('agency')->middleware(['role:agency_admin,agent'])->group(function () {
        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'agencyStats']);

        // Agents
        Route::post('/agents', [AgencyController::class, 'createAgent']);

        // Candidates
        Route::get('/candidates', [CandidateController::class, 'index']);
        Route::post('/jobs/{job}/candidates', [CandidateController::class, 'store']);

        // Contacts
        Route::get('/contacts', [ContactController::class, 'indexAgency']);

        // Employees
        Route::get('/employees', [EmployeeController::class, 'indexAgency']);
        Route::put('/employees/{employee}', [EmployeeController::class, 'updateAgency']);

        // Employer Links
        Route::get('/employer-links', [AgencyController::class, 'getEmployerLinks']);

        // Invoices
        Route::get('/invoices', [InvoiceController::class, 'indexAgency']);

        // Payroll
        Route::get('/payroll', [PayrollController::class, 'indexAgency']);
        Route::post('/payroll/process', [PayrollController::class, 'process']);

        // Payouts
        Route::get('/payouts', [PayoutController::class, 'indexAgency']);

        // Placements
        Route::get('/placements', [PlacementController::class, 'indexAgency']);
        Route::post('/placements', [PlacementController::class, 'store']);
        Route::put('/placements/{placement}', [PlacementController::class, 'update']);

        // Shifts
        Route::get('/shifts', [ShiftController::class, 'indexAgency']);
        Route::post('/shift-templates/{template}/generate', [ShiftController::class, 'generateFromTemplate']);

        // Subscriptions
        Route::get('/subscriptions', [SubscriptionController::class, 'indexAgency']);

        // Timesheets
        Route::get('/timesheets', [TimesheetController::class, 'indexAgency']);
        Route::patch('/timesheets/{timesheet}/approve', [TimesheetController::class, 'agencyApprove']);
    });

    // =============================================
    // EMPLOYER ROUTES
    // =============================================
    Route::prefix('employer')->middleware(['role:employer_admin,contact'])->group(function () {
        // Agency Links
        Route::get('/agency-links', [EmployerController::class, 'getAgencyLinks']);
        Route::post('/agency-links/request', [EmployerController::class, 'requestAgencyLink']);

        // Contacts
        Route::get('/contacts', [ContactController::class, 'indexEmployer']);
        Route::post('/contacts', [ContactController::class, 'store']);

        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'employerStats']);

        // Invoices
        Route::get('/invoices', [InvoiceController::class, 'indexEmployer']);
        Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'pay']);

        // Jobs
        Route::get('/jobs', [EmployerController::class, 'getJobs']);
        Route::post('/jobs', [EmployerController::class, 'createJob']);
        Route::get('/jobs/{job}', [EmployerController::class, 'getJob']);
        Route::put('/jobs/{job}', [EmployerController::class, 'updateJob']);
        Route::delete('/jobs/{job}', [EmployerController::class, 'deleteJob']);

        // Locations
        Route::get('/locations', [LocationController::class, 'index']);
        Route::post('/locations', [LocationController::class, 'store']);
        Route::put('/locations/{location}', [LocationController::class, 'update']);

        // Payments
        Route::get('/payments', [PaymentController::class, 'indexEmployer']);

        // Shift Approvals
        Route::get('/shift-approvals', [ShiftApprovalController::class, 'index']);
        Route::patch('/shift-approvals/{approval}/approve', [ShiftApprovalController::class, 'approve']);

        // Shift Offers
        Route::get('/shift-offers', [ShiftOfferController::class, 'indexEmployer']);

        // Shift Templates
        Route::get('/shift-templates', [ShiftTemplateController::class, 'index']);
        Route::post('/shift-templates', [ShiftTemplateController::class, 'store']);

        // Shifts
        Route::get('/shifts', [ShiftController::class, 'indexEmployer']);
        Route::post('/shifts', [ShiftController::class, 'store']);

        // Subscriptions
        Route::get('/subscriptions', [SubscriptionController::class, 'indexEmployer']);

        // Timesheets
        Route::get('/timesheets', [TimesheetController::class, 'indexEmployer']);
        Route::patch('/timesheets/{timesheet}/approve', [TimesheetController::class, 'employerApprove']);
    });

    // =============================================
    // EMPLOYEE ROUTES
    // =============================================
    Route::prefix('employee')->middleware(['role:employee'])->group(function () {
        // Availability
        Route::get('/availability', [EmployeeController::class, 'getAvailability']);
        Route::post('/availability', [EmployeeController::class, 'setAvailability']);
        Route::put('/availability/{availability}', [EmployeeController::class, 'updateAvailability']);

        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'employeeStats']);

        // Payroll
        Route::get('/payroll', [PayrollController::class, 'indexEmployee']);

        // Shift Offers
        Route::get('/shift-offers', [ShiftOfferController::class, 'indexEmployee']);
        Route::patch('/shift-offers/{offer}/respond', [ShiftOfferController::class, 'respond']);

        // Shifts
        Route::get('/shifts', [ShiftController::class, 'indexEmployee']);

        // Time Off
        Route::post('/time-off', [TimeOffController::class, 'store']);

        // Timesheets
        Route::get('/timesheets', [TimesheetController::class, 'indexEmployee']);
        Route::post('/timesheets/{shift}/clock-in', [TimesheetController::class, 'clockIn']);
        Route::post('/timesheets/{shift}/clock-out', [TimesheetController::class, 'clockOut']);
    });

    // =============================================
    // SHARED ROUTES
    // =============================================

    // Shifts (shared across roles with proper authorization in controllers)
    Route::prefix('shifts')->group(function () {
        Route::get('/{shift}', [ShiftController::class, 'show']);
        Route::put('/{shift}', [ShiftController::class, 'update']);
        Route::patch('/{shift}/cancel', [ShiftController::class, 'cancel']);
        Route::patch('/{shift}/approve', [ShiftController::class, 'approve']);
        Route::patch('/{shift}/reject', [ShiftController::class, 'reject']);
        Route::post('/{shift}/offers', [ShiftOfferController::class, 'store']);
        Route::get('/{shift}/offers', [ShiftOfferController::class, 'getShiftOffers']);
    });

    // Timesheets (shared across roles with proper authorization in controllers)
    Route::prefix('timesheets')->group(function () {
        Route::put('/{timesheet}', [TimesheetController::class, 'update']);
        Route::patch('/{timesheet}/submit', [TimesheetController::class, 'submit']);
        Route::patch('/{timesheet}/reject', [TimesheetController::class, 'reject']);
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
Route::get('/public/availability', [EmployeeController::class, 'getPublicAvailability']);
