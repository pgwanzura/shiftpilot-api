<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\Contact;
use App\Models\Location;
use App\Models\Shift;
use App\Models\ShiftTemplate;
use App\Models\EmployerAgencyLink;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ShiftApproval;
use App\Models\ShiftOffer;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EmployerService
{
    public function approveShiftApproval(ShiftApproval $approval): ShiftApproval
    {
        return DB::transaction(function () use ($approval) {
            $approval->update([
                'status' => 'approved',
                'signed_at' => now(),
            ]);

            $approval->shift->update(['status' => 'employer_approved']);

            return $approval->fresh();
        });
    }

    public function approveTimesheet(Timesheet $timesheet): Timesheet
    {
        return DB::transaction(function () use ($timesheet) {
            $timesheet->update([
                'status' => 'employer_approved',
                'approved_by_contact_id' => auth()->user()->contact->id,
                'approved_at' => now(),
            ]);

            return $timesheet->fresh();
        });
    }

    public function createContact(Employer $employer, array $data): Contact
    {
        return DB::transaction(function () use ($employer, $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'role' => 'contact',
                    'status' => 'active',
                    'password' => bcrypt(Str::random(16)),
                ]
            );

            return Contact::create([
                'employer_id' => $employer->id,
                'user_id' => $user->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'],
                'can_sign_timesheets' => $data['can_sign_timesheets'] ?? false,
            ]);
        });
    }

    public function createLocation(Employer $employer, array $data): Location
    {
        return Location::create(array_merge($data, [
            'employer_id' => $employer->id,
        ]));
    }

    public function createShift(Employer $employer, array $data): Shift
    {
        return DB::transaction(function () use ($employer, $data) {
            return Shift::create([
                'employer_id' => $employer->id,
                'location_id' => $data['location_id'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'hourly_rate' => $data['hourly_rate'] ?? null,
                'role_requirement' => $data['role_requirement'] ?? null,
                'status' => 'open',
                'created_by_type' => 'employer',
                'created_by_id' => auth()->id(),
            ]);
        });
    }

    public function createShiftTemplate(Employer $employer, array $data): ShiftTemplate
    {
        return DB::transaction(function () use ($employer, $data) {
            return ShiftTemplate::create([
                'employer_id' => $employer->id,
                'location_id' => $data['location_id'],
                'title' => $data['title'],
                'day_of_week' => $data['day_of_week'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'recurrence_type' => $data['recurrence_type'],
                'role_requirement' => $data['role_requirement'] ?? null,
                'hourly_rate' => $data['hourly_rate'] ?? null,
                'created_by_type' => 'employer',
                'created_by_id' => auth()->id(),
            ]);
        });
    }

    public function deleteShift(Shift $shift): void
    {
        $shift->delete();
    }

    public function getAgencyLinks(Employer $employer): LengthAwarePaginator
    {
        return $employer->agencyLinks()->with(['agency'])->paginate(15);
    }

    public function getContacts(Employer $employer): LengthAwarePaginator
    {
        return $employer->contacts()->with(['user'])->paginate(15);
    }

    public function getInvoices(Employer $employer): LengthAwarePaginator
    {
        return $employer->invoices()->with(['from', 'to'])->paginate(15);
    }

    public function getLocations(Employer $employer)
    {
        return $employer->locations()->get();
    }

    public function getPayments(Employer $employer): LengthAwarePaginator
    {
        return Payment::whereHas('invoice', function ($query) use ($employer) {
            $query->where('to_id', $employer->id)->where('to_type', 'employer');
        })->with(['invoice'])->paginate(15);
    }

    public function getShiftApprovals(Employer $employer): LengthAwarePaginator
    {
        return ShiftApproval::whereHas('shift', function ($query) use ($employer) {
            $query->where('employer_id', $employer->id);
        })->with(['shift', 'contact'])->paginate(15);
    }

    public function getShiftOffers(Employer $employer): LengthAwarePaginator
    {
        return ShiftOffer::whereHas('shift', function ($query) use ($employer) {
            $query->where('employer_id', $employer->id);
        })->with(['shift', 'employee.user'])->paginate(15);
    }

    public function getShiftTemplates(Employer $employer)
    {
        return $employer->shiftTemplates()->with(['location'])->get();
    }

    public function getShifts(Employer $employer, array $filters = []): LengthAwarePaginator
    {
        $query = $employer->shifts()->with(['location', 'employee.user', 'agency']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date'])) {
            $query->where('start_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('end_time', '<=', $filters['end_date']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function getSubscriptions(Employer $employer)
    {
        return $employer->subscriptions;
    }

    public function getTimesheets(Employer $employer, array $filters = []): LengthAwarePaginator
    {
        $query = Timesheet::whereHas('shift', function ($query) use ($employer) {
            $query->where('employer_id', $employer->id);
        })->with(['shift', 'employee.user']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function payInvoice(Invoice $invoice, array $data): Payment
    {
        return DB::transaction(function () use ($invoice, $data) {
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'payer_type' => 'employer',
                'payer_id' => auth()->user()->employer->id,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'status' => 'completed',
                'net_amount' => $data['amount'],
            ]);

            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_reference' => $payment->id,
            ]);

            return $payment;
        });
    }

    public function requestAgencyLink(Employer $employer, array $data): EmployerAgencyLink
    {
        return EmployerAgencyLink::create([
            'employer_id' => $employer->id,
            'agency_id' => $data['agency_id'],
            'status' => 'pending',
        ]);
    }

    public function updateLocation(Location $location, array $data): Location
    {
        $location->update($data);
        return $location->fresh();
    }
}
