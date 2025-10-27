<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ShiftPilotSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks for performance
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->createUsers();
        $this->createAgencies();
        $this->createEmployers();
        $this->createEmployerAgencyLinks();
        $this->createAgents();
        $this->createContacts();
        $this->createLocations();
        $this->createEmployees();
        $this->createPlacements();
        $this->createEmployeeAvailabilities();
        $this->createTimeOffRequests();
        $this->createShiftTemplates();
        $this->createShifts();
        $this->createShiftOffers();
        $this->createTimesheets();
        $this->createShiftApprovals();
        $this->createInvoices();
        $this->createPayments();
        $this->createPayrolls();
        $this->createPayouts();
        $this->createSubscriptions();
        $this->createPlatformBilling();
        $this->createRateCards();
        $this->createNotifications();
        $this->createWebhookSubscriptions();
        $this->createAuditLogs();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function createUsers()
    {
        $users = [];
        $now = Carbon::now();

        // Super Admin
        $users[] = [
            'name' => 'Super Admin',
            'email' => 'superadmin@shiftpilot.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'phone' => '+441234567890',
            'status' => 'active',
            'email_verified_at' => $now,
            'last_login_at' => $now->subDays(2),
            'created_at' => $now->subMonths(6),
            'updated_at' => $now,
        ];

        // Agency Admins (5 agencies)
        for ($i = 1; $i <= 5; $i++) {
            $users[] = [
                'name' => "Agency Admin $i",
                'email' => "agencyadmin$i@example.com",
                'password' => Hash::make('password'),
                'role' => 'agency_admin',
                'phone' => '+4412345678' . $i,
                'status' => 'active',
                'email_verified_at' => $now,
                'last_login_at' => $now->subDays(rand(1, 7)),
                'created_at' => $now->subMonths(rand(3, 6)),
                'updated_at' => $now,
            ];
        }

        // Agents (15 agents)
        for ($i = 1; $i <= 15; $i++) {
            $users[] = [
                'name' => "Agent $i",
                'email' => "agent$i@example.com",
                'password' => Hash::make('password'),
                'role' => 'agent',
                'phone' => '+4412345678' . (50 + $i),
                'status' => 'active',
                'email_verified_at' => $now,
                'last_login_at' => $now->subDays(rand(1, 14)),
                'created_at' => $now->subMonths(rand(1, 5)),
                'updated_at' => $now,
            ];
        }

        // Employer Admins (8 employers)
        for ($i = 1; $i <= 8; $i++) {
            $users[] = [
                'name' => "Employer Admin $i",
                'email' => "employeradmin$i@example.com",
                'password' => Hash::make('password'),
                'role' => 'employer_admin',
                'phone' => '+4412345678' . (100 + $i),
                'status' => 'active',
                'email_verified_at' => $now,
                'last_login_at' => $now->subDays(rand(1, 10)),
                'created_at' => $now->subMonths(rand(2, 6)),
                'updated_at' => $now,
            ];
        }

        // Contacts (20 contacts)
        for ($i = 1; $i <= 20; $i++) {
            $users[] = [
                'name' => "Contact $i",
                'email' => "contact$i@example.com",
                'password' => Hash::make('password'),
                'role' => 'contact',
                'phone' => '+4412345678' . (200 + $i),
                'status' => 'active',
                'email_verified_at' => $now,
                'last_login_at' => $now->subDays(rand(5, 30)),
                'created_at' => $now->subMonths(rand(1, 4)),
                'updated_at' => $now,
            ];
        }

        // Employees (100 employees)
        for ($i = 1; $i <= 100; $i++) {
            $users[] = [
                'name' => "Employee $i",
                'email' => "employee$i@example.com",
                'password' => Hash::make('password'),
                'role' => 'employee',
                'phone' => '+4412345678' . (300 + $i),
                'status' => rand(0, 10) > 1 ? 'active' : 'inactive', // 90% active
                'email_verified_at' => $now,
                'last_login_at' => $now->subDays(rand(1, 60)),
                'created_at' => $now->subMonths(rand(1, 12)),
                'updated_at' => $now,
            ];
        }

        DB::table('users')->insert($users);
        $this->command->info('Created ' . count($users) . ' users');
    }

    private function createAgencies()
    {
        $agencies = [];
        $now = Carbon::now();

        $agencyNames = [
            'Elite Staffing Solutions',
            'Prime Workforce Agency',
            'Talent Connect UK',
            'Professional Recruiters Ltd',
            'Skilled Labor Partners'
        ];

        for ($i = 1; $i <= 5; $i++) {
            $agencies[] = [
                'user_id' => $i + 1, // Agency admins start from user_id 2
                'name' => $agencyNames[$i - 1],
                'legal_name' => $agencyNames[$i - 1] . ' Limited',
                'registration_number' => 'COMP' . (100000 + $i),
                'billing_email' => "billing@agency$i.com",
                'address' => "$i Business Street, London",
                'city' => 'London',
                'country' => 'GB',
                'commission_rate' => rand(8, 15) + (rand(0, 99) / 100),
                'subscription_status' => 'active',
                'meta' => json_encode(['business_hours' => ['start' => '09:00', 'end' => '17:00']]),
                'created_at' => $now->subMonths(rand(6, 12)),
                'updated_at' => $now,
            ];
        }

        DB::table('agencies')->insert($agencies);
        $this->command->info('Created ' . count($agencies) . ' agencies');
    }

    private function createEmployers()
    {
        $employers = [];
        $now = Carbon::now();

        $employerNames = [
            'St. Mary\'s Hospital',
            'Metro Retail Group',
            'City Logistics Ltd',
            'Tech Solutions Inc',
            'Hospitality First Group',
            'Manufacturing Partners Co',
            'Education First Trust',
            'Construction Masters Ltd'
        ];

        for ($i = 1; $i <= 8; $i++) {
            $employers[] = [
                'user_id' => 9 + $i, // Employer admins start from user_id 10
                'name' => $employerNames[$i - 1],
                'billing_email' => "accounts@employer$i.com",
                'address' => "$i Industrial Estate, Manchester",
                'city' => 'Manchester',
                'country' => 'GB',
                'subscription_status' => 'active',
                'meta' => json_encode(['industry' => $this->getIndustry($i)]),
                'created_at' => $now->subMonths(rand(4, 10)),
                'updated_at' => $now,
            ];
        }

        DB::table('employers')->insert($employers);
        $this->command->info('Created ' . count($employers) . ' employers');
    }

    private function getIndustry($index)
    {
        $industries = ['Healthcare', 'Retail', 'Logistics', 'Technology', 'Hospitality', 'Manufacturing', 'Education', 'Construction'];
        return $industries[$index - 1] ?? 'General';
    }

    private function createEmployerAgencyLinks()
    {
        $links = [];
        $now = Carbon::now();

        // Create multiple relationships between employers and agencies
        $relationships = [
            [1, 1, 'approved'],
            [1, 2, 'approved'],
            [2, 1, 'approved'],
            [2, 3, 'approved'],
            [3, 2, 'approved'],
            [3, 4, 'approved'],
            [4, 3, 'approved'],
            [4, 5, 'approved'],
            [5, 1, 'approved'],
            [5, 4, 'approved'],
            [6, 2, 'approved'],
            [6, 5, 'approved'],
            [7, 3, 'approved'],
            [7, 1, 'approved'],
            [8, 4, 'approved'],
            [8, 2, 'approved'],
        ];

        foreach ($relationships as $i => $rel) {
            $links[] = [
                'employer_id' => $rel[0],
                'agency_id' => $rel[1],
                'status' => $rel[2],
                'contract_start' => $now->subMonths(rand(3, 8)),
                'contract_end' => $now->addMonths(rand(6, 24)),
                'terms' => 'Standard service agreement for temporary staffing',
                'created_at' => $now->subMonths(rand(4, 9)),
                'updated_at' => $now,
            ];
        }

        DB::table('employer_agency_links')->insert($links);
        $this->command->info('Created ' . count($links) . ' employer-agency links');
    }

    private function createAgents()
    {
        $agents = [];
        $now = Carbon::now();

        // Assign 15 agents to agencies (3 per agency)
        for ($i = 1; $i <= 15; $i++) {
            $agents[] = [
                'user_id' => 19 + $i, // Agents start from user_id 20
                'agency_id' => ceil($i / 3), // Distribute 3 agents per agency
                'name' => "Agent $i",
                'email' => "agent$i@example.com",
                'phone' => '+4412345678' . (400 + $i),
                'permissions' => json_encode(['shift_management', 'employee_view', 'timesheet_approval']),
                'created_at' => $now->subMonths(rand(1, 5)),
                'updated_at' => $now,
            ];
        }

        DB::table('agents')->insert($agents);
        $this->command->info('Created ' . count($agents) . ' agents');
    }

    private function createContacts()
    {
        $contacts = [];
        $now = Carbon::now();

        // Create 2-3 contacts per employer
        $contactId = 40; // Contacts start from user_id 41
        for ($employerId = 1; $employerId <= 8; $employerId++) {
            $numContacts = rand(2, 3);
            for ($j = 1; $j <= $numContacts; $j++) {
                $contacts[] = [
                    'employer_id' => $employerId,
                    'user_id' => $contactId++,
                    'name' => "Contact $employerId-$j",
                    'email' => "contact$employerId$j@example.com",
                    'phone' => '+4412345678' . (500 + $employerId * 10 + $j),
                    'role' => $j === 1 ? 'manager' : ($j === 2 ? 'approver' : 'supervisor'),
                    'can_sign_timesheets' => $j !== 3, // Supervisor cannot sign
                    'meta' => json_encode(['department' => $this->getDepartment($j)]),
                    'created_at' => $now->subMonths(rand(2, 6)),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('contacts')->insert($contacts);
        $this->command->info('Created ' . count($contacts) . ' contacts');
    }

    private function getDepartment($index)
    {
        $departments = ['HR', 'Operations', 'Finance', 'Logistics', 'Healthcare', 'Retail'];
        return $departments[$index - 1] ?? 'General';
    }

    private function createLocations()
    {
        $locations = [];
        $now = Carbon::now();

        // Create 2-4 locations per employer
        for ($employerId = 1; $employerId <= 8; $employerId++) {
            $numLocations = rand(2, 4);
            for ($j = 1; $j <= $numLocations; $j++) {
                $locations[] = [
                    'employer_id' => $employerId,
                    'name' => "Location $employerId-$j",
                    'address' => "$j Business Park, City $employerId",
                    'latitude' => 51.5074 + (rand(-500, 500) / 10000),
                    'longitude' => -0.1278 + (rand(-500, 500) / 10000),
                    'meta' => json_encode(['facilities' => ['parking', 'canteen']]),
                    'created_at' => $now->subMonths(rand(3, 8)),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('locations')->insert($locations);
        $this->command->info('Created ' . count($locations) . ' locations');
    }

    private function createEmployees()
    {
        $employees = [];
        $now = Carbon::now();

        $positions = ['Nurse', 'Chef', 'Driver', 'Warehouse Operative', 'Retail Assistant', 'Cleaner', 'Security Guard', 'Admin Assistant'];
        $employmentTypes = ['temp', 'perm', 'part_time'];

        // Create 100 employees distributed among agencies
        for ($i = 1; $i <= 100; $i++) {
            $agencyId = rand(1, 5);
            $employerId = rand(0, 10) > 7 ? rand(1, 8) : null; // 30% have direct employer

            $employees[] = [
                'user_id' => 61 + $i, // Employees start from user_id 62
                'agency_id' => $agencyId,
                'employer_id' => $employerId,
                'position' => $positions[array_rand($positions)],
                'pay_rate' => rand(10, 25) + (rand(0, 99) / 100),
                'availability' => json_encode(['preferred_hours' => rand(20, 40)]),
                'qualifications' => json_encode($this->getQualifications($i)),
                'employment_type' => $employmentTypes[array_rand($employmentTypes)],
                'status' => rand(0, 10) > 1 ? 'active' : 'inactive',
                'meta' => json_encode(['emergency_contact' => "Contact $i"]),
                'created_at' => $now->subMonths(rand(1, 12)),
                'updated_at' => $now,
            ];
        }

        DB::table('employees')->insert($employees);
        $this->command->info('Created ' . count($employees) . ' employees');
    }

    private function getQualifications($index)
    {
        $qualifications = [
            ['name' => 'First Aid', 'level' => 'Basic'],
            ['name' => 'Food Hygiene', 'level' => 'Level 2'],
            ['name' => 'Manual Handling', 'level' => 'Certified'],
            ['name' => 'SIA License', 'level' => 'Security'],
            ['name' => 'Driving License', 'level' => 'Category B']
        ];

        return array_slice($qualifications, 0, rand(1, 3));
    }

    private function createPlacements()
    {
        $placements = [];
        $now = Carbon::now();

        // Create placements for employees
        for ($i = 1; $i <= 80; $i++) { // 80% of employees have placements
            $employeeId = $i;
            $employerId = rand(1, 8);
            $agencyId = DB::table('employees')->where('id', $employeeId)->value('agency_id');

            if (!$agencyId) continue;

            $placements[] = [
                'employee_id' => $employeeId,
                'employer_id' => $employerId,
                'agency_id' => $agencyId,
                'start_date' => $now->subMonths(rand(1, 6)),
                'end_date' => rand(0, 10) > 3 ? $now->addMonths(rand(1, 12)) : null, // 70% have end date
                'status' => $this->getPlacementStatus(),
                'employee_rate' => rand(10, 20) + (rand(0, 99) / 100),
                'client_rate' => rand(15, 30) + (rand(0, 99) / 100),
                'notes' => 'Placement agreement for temporary work',
                'created_at' => $now->subMonths(rand(2, 7)),
                'updated_at' => $now,
            ];
        }

        DB::table('placements')->insert($placements);
        $this->command->info('Created ' . count($placements) . ' placements');
    }

    private function getPlacementStatus()
    {
        $statuses = ['active', 'active', 'active', 'completed', 'terminated'];
        return $statuses[array_rand($statuses)];
    }

    private function createEmployeeAvailabilities()
    {
        $availabilities = [];
        $now = Carbon::now();
        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

        // Create availability for each employee
        for ($employeeId = 1; $employeeId <= 100; $employeeId++) {
            $numSlots = rand(3, 7); // 3-7 availability slots per employee

            for ($j = 0; $j < $numSlots; $j++) {
                $type = $j < 5 ? 'recurring' : 'one_time';
                $dayOfWeek = $type === 'recurring' ? $days[array_rand($days)] : null;

                $availabilities[] = [
                    'employee_id' => $employeeId,
                    'type' => $type,
                    'day_of_week' => $dayOfWeek,
                    'start_date' => $type === 'one_time' ? $now->addDays(rand(1, 30)) : null,
                    'end_date' => $type === 'one_time' ? $now->addDays(rand(31, 60)) : null,
                    'start_time' => $this->generateTime(6, 10), // Morning start
                    'end_time' => $this->generateTime(14, 18), // Afternoon/evening end
                    'timezone' => 'Europe/London',
                    'status' => 'available',
                    'priority' => rand(1, 10),
                    'max_shift_length_hours' => rand(6, 12),
                    'min_shift_length_hours' => rand(2, 4),
                    'created_at' => $now->subMonths(rand(1, 3)),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('employee_availabilities')->insert($availabilities);
        $this->command->info('Created ' . count($availabilities) . ' employee availabilities');
    }

    private function generateTime($startHour, $endHour)
    {
        $hour = rand($startHour, $endHour);
        $minute = rand(0, 1) ? '00' : '30';
        return sprintf('%02d:%02d', $hour, $minute);
    }

    private function createTimeOffRequests()
    {
        $requests = [];
        $now = Carbon::now();
        $types = ['vacation', 'sick', 'personal', 'bereavement', 'other'];

        // Create time off requests for 40% of employees
        for ($i = 1; $i <= 40; $i++) {
            $employeeId = rand(1, 100);
            $type = $types[array_rand($types)];
            $startDate = $now->copy()->addDays(rand(10, 60));
            $endDate = $startDate->copy()->addDays(rand(1, 14));

            $requests[] = [
                'employee_id' => $employeeId,
                'type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $this->getTimeOffStatus(),
                'reason' => "Time off request for $type",
                'approved_by_id' => rand(0, 10) > 3 ? rand(2, 6) : null, // 70% approved
                'approved_at' => rand(0, 10) > 3 ? $now->subDays(rand(1, 10)) : null,
                'created_at' => $now->subDays(rand(5, 20)),
                'updated_at' => $now,
            ];
        }

        DB::table('time_off_requests')->insert($requests);
        $this->command->info('Created ' . count($requests) . ' time off requests');
    }

    private function getTimeOffStatus()
    {
        $statuses = ['pending', 'approved', 'approved', 'rejected', 'cancelled'];
        return $statuses[array_rand($statuses)];
    }

    private function createShiftTemplates()
    {
        $templates = [];
        $now = Carbon::now();
        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

        // Create templates for each employer
        for ($employerId = 1; $employerId <= 8; $employerId++) {
            $numTemplates = rand(3, 8);
            for ($j = 1; $j <= $numTemplates; $j++) {
                $locationId = DB::table('locations')
                    ->where('employer_id', $employerId)
                    ->inRandomOrder()
                    ->value('id');

                $templates[] = [
                    'employer_id' => $employerId,
                    'location_id' => $locationId,
                    'title' => "Regular Shift $j",
                    'description' => "Standard shift template for regular operations",
                    'day_of_week' => $days[array_rand($days)],
                    'start_time' => $this->generateTime(6, 10),
                    'end_time' => $this->generateTime(14, 18),
                    'role_requirement' => ['Nurse', 'Chef', 'Driver', 'Operative'][array_rand([0, 1, 2, 3])],
                    'hourly_rate' => rand(12, 25) + (rand(0, 99) / 100),
                    'recurrence_type' => 'weekly',
                    'status' => 'active',
                    'created_by_type' => 'employer',
                    'created_by_id' => $employerId + 9, // Employer admin user_id
                    'created_at' => $now->subMonths(rand(2, 6)),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('shift_templates')->insert($templates);
        $this->command->info('Created ' . count($templates) . ' shift templates');
    }

    private function createShifts()
    {
        $shifts = [];
        $now = Carbon::now();

        // Create 500 shifts over the past 3 months and future
        for ($i = 1; $i <= 500; $i++) {
            $employerId = rand(1, 8);
            $agencyId = rand(1, 5);
            $locationId = DB::table('locations')
                ->where('employer_id', $employerId)
                ->inRandomOrder()
                ->value('id');

            $employeeId = rand(0, 10) > 2 ? rand(1, 100) : null; // 80% assigned
            $placementId = $employeeId ? DB::table('placements')
                ->where('employee_id', $employeeId)
                ->where('employer_id', $employerId)
                ->value('id') : null;

            $startTime = $now->copy()
                ->subDays(rand(0, 90))
                ->setHour(rand(6, 10))
                ->setMinute(0);
            $endTime = $startTime->copy()->addHours(rand(4, 10));

            $shifts[] = [
                'employer_id' => $employerId,
                'agency_id' => $agencyId,
                'placement_id' => $placementId,
                'employee_id' => $employeeId,
                'agent_id' => rand(1, 15),
                'location_id' => $locationId,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'hourly_rate' => rand(12, 30) + (rand(0, 99) / 100),
                'status' => $this->getShiftStatus($startTime),
                'created_by_type' => rand(0, 1) ? 'employer' : 'agency',
                'created_by_id' => rand(0, 1) ? $employerId + 9 : rand(20, 34),
                'meta' => json_encode(['notes' => 'Regular shift']),
                'created_at' => $startTime->copy()->subDays(rand(1, 7)),
                'updated_at' => $now,
            ];
        }

        DB::table('shifts')->insert($shifts);
        $this->command->info('Created ' . count($shifts) . ' shifts');
    }

    private function getShiftStatus($startTime)
    {
        $now = Carbon::now();
        if ($startTime->gt($now)) {
            return rand(0, 10) > 3 ? 'assigned' : 'open';
        } else {
            $statuses = ['completed', 'completed', 'agency_approved', 'employer_approved', 'billed', 'cancelled'];
            return $statuses[array_rand($statuses)];
        }
    }

    private function createShiftOffers()
    {
        $offers = [];
        $now = Carbon::now();

        // Create offers for open shifts
        $openShifts = DB::table('shifts')
            ->whereIn('status', ['open', 'offered'])
            ->get();

        foreach ($openShifts as $shift) {
            $numOffers = rand(1, 3);
            for ($i = 1; $i <= $numOffers; $i++) {
                $employeeId = rand(1, 100);

                $offers[] = [
                    'shift_id' => $shift->id,
                    'employee_id' => $employeeId,
                    'offered_by_id' => rand(20, 34), // Agent user_id
                    'status' => $this->getOfferStatus(),
                    'expires_at' => $now->addHours(rand(24, 72)),
                    'responded_at' => rand(0, 10) > 5 ? $now->subHours(rand(1, 12)) : null,
                    'created_at' => $now->subHours(rand(1, 24)),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('shift_offers')->insert($offers);
        $this->command->info('Created ' . count($offers) . ' shift offers');
    }

    private function getOfferStatus()
    {
        $statuses = ['pending', 'pending', 'accepted', 'rejected', 'expired'];
        return $statuses[array_rand($statuses)];
    }

    private function createTimesheets()
    {
        $timesheets = [];
        $now = Carbon::now();

        // Create timesheets for completed shifts
        $completedShifts = DB::table('shifts')
            ->whereIn('status', ['completed', 'agency_approved', 'employer_approved', 'billed'])
            ->whereNotNull('employee_id')
            ->get();

        foreach ($completedShifts as $shift) {
            $clockIn = Carbon::parse($shift->start_time);
            $clockOut = Carbon::parse($shift->end_time);
            $breakMinutes = rand(0, 60);
            $hoursWorked = $clockOut->diffInHours($clockIn) - ($breakMinutes / 60);

            $timesheets[] = [
                'shift_id' => $shift->id,
                'employee_id' => $shift->employee_id,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'break_minutes' => $breakMinutes,
                'hours_worked' => round($hoursWorked, 2),
                'status' => $this->getTimesheetStatus($shift->status),
                'agency_approved_by' => in_array($shift->status, ['agency_approved', 'employer_approved', 'billed']) ? rand(2, 6) : null,
                'agency_approved_at' => in_array($shift->status, ['agency_approved', 'employer_approved', 'billed']) ? $clockOut->addHours(rand(1, 24)) : null,
                'approved_by_contact_id' => in_array($shift->status, ['employer_approved', 'billed']) ? rand(1, 20) : null,
                'approved_at' => in_array($shift->status, ['employer_approved', 'billed']) ? $clockOut->addHours(rand(25, 48)) : null,
                'created_at' => $clockOut,
                'updated_at' => $now,
            ];
        }

        DB::table('timesheets')->insert($timesheets);
        $this->command->info('Created ' . count($timesheets) . ' timesheets');
    }

    private function getTimesheetStatus($shiftStatus)
    {
        $map = [
            'completed' => 'pending',
            'agency_approved' => 'agency_approved',
            'employer_approved' => 'employer_approved',
            'billed' => 'employer_approved'
        ];
        return $map[$shiftStatus] ?? 'pending';
    }

    private function createShiftApprovals()
    {
        $approvals = [];
        $now = Carbon::now();

        // Create approvals for employer_approved shifts
        $approvedShifts = DB::table('shifts')
            ->whereIn('status', ['employer_approved', 'billed'])
            ->get();

        foreach ($approvedShifts as $shift) {
            $contactId = rand(1, 20);

            $approvals[] = [
                'shift_id' => $shift->id,
                'contact_id' => $contactId,
                'status' => 'approved',
                'signed_at' => Carbon::parse($shift->start_time)->addHours(rand(24, 72)),
                'notes' => 'Shift completed satisfactorily',
                'created_at' => Carbon::parse($shift->start_time)->addHours(rand(24, 48)),
                'updated_at' => $now,
            ];
        }

        DB::table('shift_approvals')->insert($approvals);
        $this->command->info('Created ' . count($approvals) . ' shift approvals');
    }

    private function createInvoices()
    {
        $invoices = [];
        $now = Carbon::now();

        // Create 200 invoices of various types
        for ($i = 1; $i <= 200; $i++) {
            $type = ['employer_to_agency', 'agency_to_shiftpilot', 'employer_to_shiftpilot'][array_rand([0, 1, 2])];

            list($fromType, $fromId, $toType, $toId) = $this->getInvoiceParties($type);

            $subtotal = rand(500, 5000) + (rand(0, 99) / 100);
            $taxAmount = $subtotal * 0.2; // 20% VAT
            $totalAmount = $subtotal + $taxAmount;

            $invoices[] = [
                'type' => $type,
                'from_type' => $fromType,
                'from_id' => $fromId,
                'to_type' => $toType,
                'to_id' => $toId,
                'reference' => 'INV-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'line_items' => json_encode($this->generateLineItems($subtotal)),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => $this->getInvoiceStatus(),
                'due_date' => $now->copy()->addDays(rand(7, 30)),
                'paid_at' => rand(0, 10) > 4 ? $now->subDays(rand(1, 14)) : null,
                'created_at' => $now->subDays(rand(15, 60)),
                'updated_at' => $now,
            ];
        }

        DB::table('invoices')->insert($invoices);
        $this->command->info('Created ' . count($invoices) . ' invoices');
    }

    private function getInvoiceParties($type)
    {
        switch ($type) {
            case 'employer_to_agency':
                return ['employer', rand(1, 8), 'agency', rand(1, 5)];
            case 'agency_to_shiftpilot':
                return ['agency', rand(1, 5), 'shiftpilot', 1];
            case 'employer_to_shiftpilot':
                return ['employer', rand(1, 8), 'shiftpilot', 1];
            default:
                return ['employer', 1, 'agency', 1];
        }
    }

    private function generateLineItems($subtotal)
    {
        return [
            [
                'description' => 'Temporary staffing services for October 2024',
                'quantity' => 1,
                'unit_price' => $subtotal,
                'tax_rate' => 20.00,
                'total' => $subtotal
            ]
        ];
    }

    private function getInvoiceStatus()
    {
        $statuses = ['paid', 'paid', 'paid', 'pending', 'overdue', 'partial'];
        return $statuses[array_rand($statuses)];
    }

    private function createPayments()
    {
        $payments = [];
        $now = Carbon::now();

        // Get paid invoices
        $paidInvoices = DB::table('invoices')
            ->whereIn('status', ['paid', 'partial'])
            ->whereNotNull('paid_at')
            ->get();

        foreach ($paidInvoices as $invoice) {
            $payments[] = [
                'invoice_id' => $invoice->id,
                'payer_type' => $invoice->from_type,
                'payer_id' => $invoice->from_id,
                'amount' => $invoice->total_amount,
                'method' => ['stripe', 'bacs', 'sepa'][array_rand([0, 1, 2])],
                'processor_id' => 'pay_' . str_random(14),
                'status' => 'completed',
                'fee_amount' => $invoice->total_amount * 0.029 + 0.30, // 2.9% + £0.30
                'net_amount' => $invoice->total_amount - ($invoice->total_amount * 0.029 + 0.30),
                'metadata' => json_encode(['payment_method' => 'card']),
                'created_at' => $invoice->paid_at,
                'updated_at' => $now,
            ];
        }

        DB::table('payments')->insert($payments);
        $this->command->info('Created ' . count($payments) . ' payments');
    }

    private function createPayrolls()
    {
        $payrolls = [];
        $now = Carbon::now();

        // Create payroll records for employees
        for ($i = 1; $i <= 300; $i++) {
            $employeeId = rand(1, 100);
            $agencyId = DB::table('employees')->where('id', $employeeId)->value('agency_id');

            if (!$agencyId) continue;

            $periodStart = $now->copy()->subMonths(2)->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth();
            $totalHours = rand(80, 160);
            $grossPay = $totalHours * rand(10, 20);
            $taxes = $grossPay * 0.2;
            $netPay = $grossPay - $taxes;

            $payrolls[] = [
                'agency_id' => $agencyId,
                'employee_id' => $employeeId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'total_hours' => $totalHours,
                'gross_pay' => $grossPay,
                'taxes' => $taxes,
                'net_pay' => $netPay,
                'status' => rand(0, 10) > 2 ? 'paid' : 'unpaid',
                'paid_at' => rand(0, 10) > 2 ? $periodEnd->addDays(rand(5, 10)) : null,
                'created_at' => $periodEnd,
                'updated_at' => $now,
            ];
        }

        DB::table('payrolls')->insert($payrolls);
        $this->command->info('Created ' . count($payrolls) . ' payroll records');
    }

    private function createPayouts()
    {
        $payouts = [];
        $now = Carbon::now();

        // Create payouts for agencies
        for ($agencyId = 1; $agencyId <= 5; $agencyId++) {
            for ($month = 1; $month <= 3; $month++) {
                $periodStart = $now->copy()->subMonths($month)->startOfMonth();
                $periodEnd = $periodStart->copy()->endOfMonth();

                $payouts[] = [
                    'agency_id' => $agencyId,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'total_amount' => rand(5000, 50000) + (rand(0, 99) / 100),
                    'status' => 'paid',
                    'provider_payout_id' => 'po_' . str_random(14),
                    'created_at' => $periodEnd->addDays(rand(3, 7)),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('payouts')->insert($payouts);
        $this->command->info('Created ' . count($payouts) . ' payouts');
    }

    private function createSubscriptions()
    {
        $subscriptions = [];
        $now = Carbon::now();

        $plans = [
            'agency_pro' => ['name' => 'Agency Pro', 'amount' => 199.00],
            'employer_basic' => ['name' => 'Employer Basic', 'amount' => 99.00],
            'employer_premium' => ['name' => 'Employer Premium', 'amount' => 199.00]
        ];

        // Agency subscriptions
        for ($agencyId = 1; $agencyId <= 5; $agencyId++) {
            $plan = 'agency_pro';
            $subscriptions[] = [
                'entity_type' => 'agency',
                'entity_id' => $agencyId,
                'plan_key' => $plan,
                'plan_name' => $plans[$plan]['name'],
                'amount' => $plans[$plan]['amount'],
                'interval' => 'monthly',
                'status' => 'active',
                'started_at' => $now->subMonths(rand(3, 12)),
                'current_period_start' => $now->copy()->startOfMonth(),
                'current_period_end' => $now->copy()->addMonth()->startOfMonth(),
                'created_at' => $now->subMonths(rand(3, 12)),
                'updated_at' => $now,
            ];
        }

        // Employer subscriptions
        for ($employerId = 1; $employerId <= 8; $employerId++) {
            $plan = rand(0, 1) ? 'employer_basic' : 'employer_premium';
            $subscriptions[] = [
                'entity_type' => 'employer',
                'entity_id' => $employerId,
                'plan_key' => $plan,
                'plan_name' => $plans[$plan]['name'],
                'amount' => $plans[$plan]['amount'],
                'interval' => 'monthly',
                'status' => 'active',
                'started_at' => $now->subMonths(rand(2, 8)),
                'current_period_start' => $now->copy()->startOfMonth(),
                'current_period_end' => $now->copy()->addMonth()->startOfMonth(),
                'created_at' => $now->subMonths(rand(2, 8)),
                'updated_at' => $now,
            ];
        }

        DB::table('subscriptions')->insert($subscriptions);
        $this->command->info('Created ' . count($subscriptions) . ' subscriptions');
    }

    private function createPlatformBilling()
    {
        DB::table('platform_billing')->insert([
            'commission_rate' => 2.00,
            'transaction_fee_flat' => 0.30,
            'transaction_fee_percent' => 2.9,
            'payout_schedule_days' => 7,
            'tax_vat_rate_percent' => 20.00,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->command->info('Created platform billing settings');
    }

    private function createRateCards()
    {
        $rateCards = [];
        $now = Carbon::now();
        $roles = ['nurse', 'chef', 'driver', 'warehouse_operative', 'retail_assistant', 'cleaner', 'security_guard'];
        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

        // Create rate cards for employers and agencies
        for ($i = 1; $i <= 50; $i++) {
            $employerId = rand(0, 10) > 3 ? rand(1, 8) : null;
            $agencyId = !$employerId ? rand(1, 5) : null;
            $locationId = $employerId ? DB::table('locations')
                ->where('employer_id', $employerId)
                ->inRandomOrder()
                ->value('id') : null;

            $rateCards[] = [
                'employer_id' => $employerId,
                'agency_id' => $agencyId,
                'role_key' => $roles[array_rand($roles)],
                'location_id' => $locationId,
                'day_of_week' => rand(0, 10) > 2 ? $days[array_rand($days)] : null,
                'start_time' => rand(0, 10) > 5 ? $this->generateTime(6, 10) : null,
                'end_time' => rand(0, 10) > 5 ? $this->generateTime(14, 18) : null,
                'rate' => rand(12, 35) + (rand(0, 99) / 100),
                'currency' => 'GBP',
                'effective_from' => $now->subMonths(rand(1, 6)),
                'effective_to' => $now->addMonths(rand(6, 24)),
                'created_at' => $now->subMonths(rand(2, 7)),
                'updated_at' => $now,
            ];
        }

        DB::table('rate_cards')->insert($rateCards);
        $this->command->info('Created ' . count($rateCards) . ' rate cards');
    }

    private function createNotifications()
    {
        $notifications = [];
        $now = Carbon::now();
        $templates = [
            'shift.requested:agency',
            'shift.offered:employer',
            'shift.assigned:employee',
            'timesheet.submitted:agency',
            'invoice.generated:employer',
            'shift_offer.sent:employee'
        ];

        // Create 1000 notifications
        for ($i = 1; $i <= 1000; $i++) {
            $recipientType = ['user', 'agency', 'employer'][array_rand([0, 1, 2])];
            $recipientId = $recipientType === 'user' ? rand(2, 161) : ($recipientType === 'agency' ? rand(1, 5) : rand(1, 8));

            $notifications[] = [
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'channel' => ['email', 'sms', 'in_app'][array_rand([0, 1, 2])],
                'template_key' => $templates[array_rand($templates)],
                'payload' => json_encode(['message' => 'Notification message']),
                'is_read' => rand(0, 10) > 3,
                'sent_at' => $now->subHours(rand(1, 720)),
                'created_at' => $now->subHours(rand(1, 720)),
                'updated_at' => $now,
            ];
        }

        DB::table('notifications')->insert($notifications);
        $this->command->info('Created ' . count($notifications) . ' notifications');
    }

    private function createWebhookSubscriptions()
    {
        $webhooks = [];
        $now = Carbon::now();

        // Create webhooks for agencies and employers
        for ($i = 1; $i <= 10; $i++) {
            $ownerType = rand(0, 1) ? 'agency' : 'employer';
            $ownerId = $ownerType === 'agency' ? rand(1, 5) : rand(1, 8);

            $webhooks[] = [
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'url' => "https://webhook.example.com/$ownerType/$ownerId",
                'events' => json_encode(['shift.assigned', 'timesheet.approved', 'invoice.paid']),
                'secret' => str_random(32),
                'status' => 'active',
                'last_delivery_at' => $now->subHours(rand(1, 168)),
                'created_at' => $now->subMonths(rand(1, 4)),
                'updated_at' => $now,
            ];
        }

        DB::table('webhook_subscriptions')->insert($webhooks);
        $this->command->info('Created ' . count($webhooks) . ' webhook subscriptions');
    }

    private function createAuditLogs()
    {
        $auditLogs = [];
        $now = Carbon::now();
        $actions = ['created', 'updated', 'deleted', 'viewed', 'approved', 'rejected'];

        // Create 2000 audit log entries
        for ($i = 1; $i <= 2000; $i++) {
            $actorType = 'user';
            $actorId = rand(2, 161);
            $targetType = ['shift', 'timesheet', 'invoice', 'employee', 'employer'][array_rand([0, 1, 2, 3, 4])];
            $targetId = rand(1, 100);

            $auditLogs[] = [
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'action' => $actions[array_rand($actions)],
                'target_type' => $targetType,
                'target_id' => $targetId,
                'payload' => json_encode(['ip' => '192.168.1.' . rand(1, 255)]),
                'ip_address' => '192.168.1.' . rand(1, 255),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => $now->subHours(rand(1, 720)),
            ];

            // Insert in batches to avoid memory issues
            if ($i % 500 === 0) {
                DB::table('audit_logs')->insert($auditLogs);
                $auditLogs = [];
            }
        }

        // Insert remaining logs
        if (!empty($auditLogs)) {
            DB::table('audit_logs')->insert($auditLogs);
        }

        $this->command->info('Created 2000 audit log entries');
    }
}
