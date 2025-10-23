<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employer\CreateContactRequest;
use App\Http\Requests\Employer\CreateLocationRequest;
use App\Http\Requests\Employer\CreateShiftRequest;
use App\Http\Requests\Employer\CreateShiftTemplateRequest;
use App\Http\Requests\Employer\PayInvoiceRequest;
use App\Http\Requests\Employer\RequestAgencyLinkRequest;
use App\Http\Requests\Employer\UpdateLocationRequest;
use App\Http\Resources\ContactResource;
use App\Http\Resources\EmployerAgencyLinkResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\LocationResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\ShiftApprovalResource;
use App\Http\Resources\ShiftOfferResource;
use App\Http\Resources\ShiftResource;
use App\Http\Resources\ShiftTemplateResource;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\TimesheetResource;
use App\Models\Contact;
use App\Models\EmployerAgencyLink;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payment;
use App\Models\Shift;
use App\Models\ShiftApproval;
use App\Models\ShiftOffer;
use App\Models\ShiftTemplate;
use App\Models\Subscription;
use App\Models\Timesheet;
use App\Services\EmployerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployerController extends Controller
{
    public function __construct(
        private EmployerService $employerService
    ) {
    }

    public function approveShiftApproval(ShiftApproval $approval): JsonResponse
    {
        $this->authorize('approve', $approval);
        $approval = $this->employerService->approveShiftApproval($approval);

        return response()->json([
            'success' => true,
            'data' => new ShiftApprovalResource($approval),
            'message' => 'Shift approval approved successfully'
        ]);
    }

    public function approveTimesheet(Timesheet $timesheet): JsonResponse
    {
        $this->authorize('approve', $timesheet);
        $timesheet = $this->employerService->approveTimesheet($timesheet);

        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet),
            'message' => 'Timesheet approved successfully'
        ]);
    }

    public function createContact(CreateContactRequest $request): JsonResponse
    {
        $employer = auth()->user()->employer;
        $contact = $this->employerService->createContact($employer, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new ContactResource($contact),
            'message' => 'Contact created successfully'
        ]);
    }

    public function createLocation(CreateLocationRequest $request): JsonResponse
    {
        $employer = auth()->user()->employer;
        $location = $this->employerService->createLocation($employer, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new LocationResource($location),
            'message' => 'Location created successfully'
        ]);
    }

    public function createShift(CreateShiftRequest $request): JsonResponse
    {
        $employer = auth()->user()->employer;
        $shift = $this->employerService->createShift($employer, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new ShiftResource($shift),
            'message' => 'Shift created successfully'
        ]);
    }

    public function createShiftTemplate(CreateShiftTemplateRequest $request): JsonResponse
    {
        $employer = auth()->user()->employer;
        $template = $this->employerService->createShiftTemplate($employer, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new ShiftTemplateResource($template),
            'message' => 'Shift template created successfully'
        ]);
    }

    public function deleteShift(Shift $shift): JsonResponse
    {
        $this->authorize('delete', $shift);
        $this->employerService->deleteShift($shift);

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Shift deleted successfully'
        ]);
    }

    public function getAgencyLinks(): JsonResponse
    {
        $employer = auth()->user()->employer;
        $links = $this->employerService->getAgencyLinks($employer);

        return response()->json([
            'success' => true,
            'data' => $links,
            'message' => 'Agency links retrieved successfully'
        ]);
    }

    public function getContacts(): JsonResponse
    {
        $employer = auth()->user()->employer;
        $contacts = $this->employerService->getContacts($employer);

        return response()->json([
            'success' => true,
            'data' => $contacts,
            'message' => 'Contacts retrieved successfully'
        ]);
    }

    public function getInvoices(): JsonResponse
    {
        $employer = auth()->user()->employer;
        $invoices = $this->employerService->getInvoices($employer);

        return response()->json([
            'success' => true,
            'data' => $invoices,
            'message' => 'Invoices retrieved successfully'
        ]);
    }

    public function getShift(Shift $shift): JsonResponse
    {
        $this->authorize('view', $shift);
        $shift->load(['employer', 'location', 'employee.user']);

        return response()->json([
            'success' => true,
            'data' => new ShiftResource($shift),
            'message' => 'Shift retrieved successfully'
        ]);
    }

    public function getLocations(): JsonResponse
    {
        $employer = auth()->user()->employer;
        $locations = $this->employerService->getLocations($employer);

        return response()->json([
            'success' => true,
            'data' => $locations,
            'message' => 'Locations retrieved successfully'
        ]);
    }

    public function getPayments(): JsonResponse
    {
        $employer = auth()->user()->employer;
        $payments = $this->employerService->getPayments($employer);

        return response()->json([
            'success' => true,
            'data' => $payments,
            'message' => 'Payments retrieved successfully'
        ]);
    }

    public function getShiftApprovals(): JsonResponse
    {
        $employer = auth()->user()->employer;
        $approvals = $this->employerService->getShiftApprovals($employer);

        return response()->json([
            'success' => true,
            'data' => $approvals,
            'message' => 'Shift approvals retrieved successfully'
        ]);
    }

    public function getShiftOffers(): JsonResponse
    {
        $employer = auth()->user()->employer;
        $offers = $this->employerService->getShiftOffers($employer);

        return response()->json([
            'success' => true,
            'data' => $offers,
            'message' => 'Shift offers retrieved successfully'
        ]);
    }

    public function getShiftTemplates(): JsonResponse
    {
        $employer = auth()->user()->employer;
        $templates = $this->employerService->getShiftTemplates($employer);

        return response()->json([
            'success' => true,
            'data' => $templates,
            'message' => 'Shift templates retrieved successfully'
        ]);
    }

    public function getShifts(Request $request): JsonResponse
    {
        $employer = auth()->user()->employer;
        $shifts = $this->employerService->getShifts($employer, $request->all());

        return response()->json([
            'success' => true,
            'data' => $shifts,
            'message' => 'Shifts retrieved successfully'
        ]);
    }

    public function getSubscriptions(): JsonResponse
    {
        $employer = auth()->user()->employer;
        $subscriptions = $this->employerService->getSubscriptions($employer);

        return response()->json([
            'success' => true,
            'data' => $subscriptions,
            'message' => 'Subscriptions retrieved successfully'
        ]);
    }

    public function getTimesheets(Request $request): JsonResponse
    {
        $employer = auth()->user()->employer;
        $timesheets = $this->employerService->getTimesheets($employer, $request->all());

        return response()->json([
            'success' => true,
            'data' => $timesheets,
            'message' => 'Timesheets retrieved successfully'
        ]);
    }

    public function payInvoice(Invoice $invoice, PayInvoiceRequest $request): JsonResponse
    {
        $this->authorize('pay', $invoice);
        $payment = $this->employerService->payInvoice($invoice, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
            'message' => 'Invoice paid successfully'
        ]);
    }

    public function requestAgencyLink(RequestAgencyLinkRequest $request): JsonResponse
    {
        $employer = auth()->user()->employer;
        $link = $this->employerService->requestAgencyLink($employer, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new EmployerAgencyLinkResource($link),
            'message' => 'Agency link requested successfully'
        ]);
    }

    public function updateLocation(Location $location, UpdateLocationRequest $request): JsonResponse
    {
        $this->authorize('update', $location);
        $location = $this->employerService->updateLocation($location, $request->validated());

        return response()->json([
            'success' => true,
            'data' => new LocationResource($location),
            'message' => 'Location updated successfully'
        ]);
    }
}
