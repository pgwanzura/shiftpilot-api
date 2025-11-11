<?php

namespace Database\Seeders;

use App\Models\EmployeeAvailability;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ShiftPilotSeeder extends Seeder
{
    private array $industries = [
        'Healthcare',
        'Retail',
        'Logistics',
        'Technology',
        'Hospitality',
        'Manufacturing',
        'Education',
        'Construction',
        'Finance',
        'Government'
    ];

    private array $positions = [
        'Registered Nurse',
        'Senior Care Assistant',
        'Healthcare Assistant',
        'Head Chef',
        'Sous Chef',
        'Line Cook',
        'Kitchen Porter',
        'HGV Driver',
        'Delivery Driver',
        'Van Driver',
        'Forklift Operator',
        'Warehouse Operative',
        'Stock Controller',
        'Order Picker',
        'Retail Supervisor',
        'Sales Assistant',
        'Customer Service Advisor',
        'Commercial Cleaner',
        'Office Cleaner',
        'Industrial Cleaner',
        'Security Officer',
        'Security Supervisor',
        'Event Security',
        'Administrative Assistant',
        'Receptionist',
        'Data Entry Clerk'
    ];

    private array $qualifications = [
        ['name' => 'First Aid at Work', 'level' => 'Level 3'],
        ['name' => 'Food Hygiene Certificate', 'level' => 'Level 2'],
        ['name' => 'Manual Handling', 'level' => 'Certified'],
        ['name' => 'SIA License', 'level' => 'Security'],
        ['name' => 'CSCS Card', 'level' => 'Construction'],
        ['name' => 'Patient Care Certificate', 'level' => 'Healthcare'],
        ['name' => 'HGV Class 1 License', 'level' => 'Category C+E'],
        ['name' => 'Forklift License', 'level' => 'Counterbalance'],
        ['name' => 'Fire Safety', 'level' => 'Level 2'],
        ['name' => 'Safeguarding Adults', 'level' => 'Level 2']
    ];

    private array $locations = [
        'London' => ['Central London', 'East London', 'West London', 'North London', 'South London'],
        'Manchester' => ['City Centre', 'Salford Quays', 'Trafford', 'Stockport'],
        'Birmingham' => ['City Centre', 'Jewellery Quarter', 'Digbeth', 'Edgbaston'],
        'Leeds' => ['City Centre', 'Headingley', 'Roundhay', 'Horsforth'],
        'Glasgow' => ['City Centre', 'West End', 'Merchant City', 'Finnieston']
    ];

    public function run()
    {
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

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function createUsers()
    {
        $users = [];
        $now = Carbon::now();

        $users[] = [
            'name' => 'System Administrator',
            'email' => 'admin@shiftpilot.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super_admin',
            'phone' => '+441632960001',
            'status' => 'active',
            'email_verified_at' => $now,
            'last_login_at' => $now->copy()->subHours(12),
            'created_at' => $now->copy()->subYear(),
            'updated_at' => $now,
        ];

        $agencyNames = ['Elite Staffing', 'Prime Workforce', 'Talent Connect', 'Professional Recruiters', 'Skilled Labor Partners'];
        foreach ($agencyNames as $index => $name) {
            $users[] = [
                'name' => $name . ' Manager',
                'email' => strtolower(str_replace(' ', '', $name)) . '@example.com',
                'password' => Hash::make('Password123!'),
                'role' => 'agency_admin',
                'phone' => '+4416329600' . (10 + $index),
                'status' => 'active',
                'email_verified_at' => $now,
                'last_login_at' => $now->copy()->subDays(rand(1, 7)),
                'created_at' => $now->copy()->subMonths(rand(6, 12)),
                'updated_at' => $now,
            ];
        }

        for ($i = 1; $i <= 15; $i++) {
            $agencyId = ceil($i / 3);
            $users[] = [
                'name' => 'Agent ' . $i,
                'email' => 'agent' . $i . '@agency' . $agencyId . '.com',
                'password' => Hash::make('Password123!'),
                'role' => 'agent',
                'phone' => '+441632970' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'status' => 'active',
                'email_verified_at' => $now,
                'last_login_at' => $now->copy()->subDays(rand(1, 14)),
                'created_at' => $now->copy()->subMonths(rand(3, 9)),
                'updated_at' => $now,
            ];
        }

        $employerNames = [
            'St. Mary\'s Hospital NHS Trust',
            'Metro Retail Group PLC',
            'City Logistics Ltd',
            'Tech Solutions International',
            'Hospitality First Group',
            'Manufacturing Partners Co',
            'Education First Academy Trust',
            'Construction Masters Ltd'
        ];

        foreach ($employerNames as $index => $name) {
            $users[] = [
                'name' => $name . ' Admin',
                'email' => 'admin@' . strtolower(str_replace([' ', '\'', 'PLC', 'Ltd', 'Trust'], '', $name)) . '.com',
                'password' => Hash::make('Password123!'),
                'role' => 'employer_admin',
                'phone' => '+441632980' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'status' => 'active',
                'email_verified_at' => $now,
                'last_login_at' => $now->copy()->subDays(rand(1, 10)),
                'created_at' => $now->copy()->subMonths(rand(4, 10)),
                'updated_at' => $now,
            ];
        }

        $contactCount = 1;
        for ($employerId = 1; $employerId <= 8; $employerId++) {
            $numContacts = rand(2, 4);
            for ($j = 1; $j <= $numContacts; $j++) {
                $users[] = [
                    'name' => 'Contact ' . $contactCount,
                    'email' => 'contact' . $contactCount . '@employer' . $employerId . '.com',
                    'password' => Hash::make('Password123!'),
                    'role' => 'contact',
                    'phone' => '+441632990' . str_pad($contactCount, 3, '0', STR_PAD_LEFT),
                    'status' => 'active',
                    'email_verified_at' => $now,
                    'last_login_at' => $now->copy()->subDays(rand(5, 30)),
                    'created_at' => $now->copy()->subMonths(rand(2, 8)),
                    'updated_at' => $now,
                ];
                $contactCount++;
            }
        }

        for ($i = 1; $i <= 100; $i++) {
            $firstName = $this->generateFirstName();
            $lastName = $this->generateLastName();
            $users[] = [
                'name' => $firstName . ' ' . $lastName,
                'email' => strtolower($firstName . '.' . $lastName . $i) . '@example.com',
                'password' => Hash::make('Password123!'),
                'role' => 'employee',
                'phone' => '+447' . rand(500000000, 799999999),
                'status' => rand(0, 10) > 1 ? 'active' : 'inactive',
                'email_verified_at' => $now,
                'last_login_at' => $now->copy()->subDays(rand(1, 60)),
                'created_at' => $now->copy()->subMonths(rand(1, 18)),
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

        $agencyData = [
            ['Elite Staffing Solutions Ltd', 'COMP100001', 12.5],
            ['Prime Workforce Agency PLC', 'COMP100002', 11.8],
            ['Talent Connect UK Limited', 'COMP100003', 13.2],
            ['Professional Recruiters Group', 'COMP100004', 10.9],
            ['Skilled Labor Partners Ltd', 'COMP100005', 14.0]
        ];

        foreach ($agencyData as $index => $data) {
            $agencies[] = [
                'user_id' => $index + 2,
                'name' => $data[0],
                'legal_name' => $data[0],
                'registration_number' => $data[1],
                'billing_email' => 'accounts@' . strtolower(str_replace([' ', 'Ltd', 'PLC', 'Limited'], '', $data[0])) . '.com',
                'address' => ($index + 1) . ' Business Park, London E1 6AN',
                'city' => 'London',
                'country' => 'GB',
                'commission_rate' => $data[2],
                'subscription_status' => 'active',
                'meta' => json_encode([
                    'business_hours' => ['start' => '08:30', 'end' => '17:30'],
                    'specializations' => $this->getAgencySpecializations($index)
                ]),
                'created_at' => $now->copy()->subMonths(rand(12, 24)),
                'updated_at' => $now,
            ];
        }

        DB::table('agencies')->insert($agencies);
        $this->command->info('Created ' . count($agencies) . ' agencies');
    }

    private function getAgencySpecializations($index)
    {
        $specializations = [
            ['Healthcare', 'Social Care'],
            ['Logistics', 'Warehousing'],
            ['Technology', 'Professional Services'],
            ['Hospitality', 'Retail'],
            ['Construction', 'Industrial']
        ];
        return $specializations[$index] ?? ['General Staffing'];
    }

    private function createEmployers()
    {
        $employers = [];
        $now = Carbon::now();

        $employerData = [
            ['St. Mary\'s Hospital NHS Trust', 'Healthcare', 'London'],
            ['Metro Retail Group PLC', 'Retail', 'Manchester'],
            ['City Logistics Ltd', 'Logistics', 'Birmingham'],
            ['Tech Solutions International', 'Technology', 'London'],
            ['Hospitality First Group', 'Hospitality', 'Glasgow'],
            ['Manufacturing Partners Co', 'Manufacturing', 'Leeds'],
            ['Education First Academy Trust', 'Education', 'Manchester'],
            ['Construction Masters Ltd', 'Construction', 'Birmingham']
        ];

        foreach ($employerData as $index => $data) {
            $employers[] = [
                'user_id' => 21 + $index,
                'name' => $data[0],
                'billing_email' => 'finance@' . strtolower(str_replace([' ', '\'', 'PLC', 'Ltd', 'Trust', 'Group', 'Co'], '', $data[0])) . '.com',
                'address' => rand(1, 100) . ' ' . $data[2] . ' Road, ' . $data[2],
                'city' => $data[2],
                'country' => 'GB',
                'subscription_status' => 'active',
                'meta' => json_encode([
                    'industry' => $data[1],
                    'company_size' => rand(50, 5000),
                    'established' => rand(1990, 2015)
                ]),
                'created_at' => $now->copy()->subMonths(rand(12, 36)),
                'updated_at' => $now,
            ];
        }

        DB::table('employers')->insert($employers);
        $this->command->info('Created ' . count($employers) . ' employers');
    }

    private function createEmployerAgencyLinks()
    {
        $links = [];
        $now = Carbon::now();

        $relationships = [
            [1, 1],
            [1, 2],
            [2, 1],
            [2, 3],
            [3, 2],
            [3, 4],
            [4, 3],
            [4, 5],
            [5, 1],
            [5, 4],
            [6, 2],
            [6, 5],
            [7, 3],
            [7, 1],
            [8, 4],
            [8, 2],
            [1, 3],
            [2, 4]
        ];

        foreach ($relationships as $rel) {
            $startDate = $now->copy()->subMonths(rand(6, 18));
            $links[] = [
                'employer_id' => $rel[0],
                'agency_id' => $rel[1],
                'status' => 'approved',
                'contract_start' => $startDate->format('Y-m-d'),
                'contract_end' => $startDate->copy()->addYears(2)->format('Y-m-d'),
                'terms' => 'Master Services Agreement for temporary staffing provision',
                'created_at' => $startDate,
                'updated_at' => $now,
            ];
        }

        DB::table('employer_agency_links')->insert($links);
        $this->command->info('Created ' . count($links) . ' employer-agency relationships');
    }

    private function createAgents()
    {
        $agents = [];
        $now = Carbon::now();

        for ($i = 1; $i <= 15; $i++) {
            $agencyId = ceil($i / 3);
            $agents[] = [
                'user_id' => 7 + $i,
                'agency_id' => $agencyId,
                'name' => 'Agent ' . $i,
                'email' => 'agent' . $i . '@agency' . $agencyId . '.com',
                'phone' => '+441632970' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'permissions' => json_encode(['shift_management', 'employee_management', 'timesheet_approval']),
                'created_at' => $now->copy()->subMonths(rand(3, 12)),
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

        $contactUserId = 29;
        for ($employerId = 1; $employerId <= 8; $employerId++) {
            $numContacts = rand(2, 4);
            $roles = ['Operations Manager', 'HR Manager', 'Department Supervisor', 'Site Manager'];

            for ($j = 0; $j < $numContacts; $j++) {
                $contacts[] = [
                    'employer_id' => $employerId,
                    'user_id' => $contactUserId++,
                    'name' => 'Contact ' . $employerId . '-' . ($j + 1),
                    'email' => strtolower(str_replace(' ', '.', $roles[$j])) . $employerId . '@employer' . $employerId . '.com',
                    'phone' => '+44163299' . str_pad($employerId * 10 + $j, 3, '0', STR_PAD_LEFT),
                    'role' => $j === 0 ? 'manager' : ($j === 1 ? 'approver' : 'supervisor'),
                    'can_sign_timesheets' => $j !== 3,
                    'meta' => json_encode(['department' => $roles[$j]]),
                    'created_at' => $now->copy()->subMonths(rand(4, 12)),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('contacts')->insert($contacts);
        $this->command->info('Created ' . count($contacts) . ' contacts');
    }

    private function createLocations()
    {
        $locations = [];
        $now = Carbon::now();

        $employerLocations = [
            1 => ['Main Hospital', 'Outpatient Clinic', 'Community Health Centre'],
            2 => ['City Centre Store', 'Retail Park', 'Shopping Centre Unit'],
            3 => ['Central Depot', 'Distribution Centre', 'Logistics Hub'],
            4 => ['Head Office', 'Tech Campus', 'Development Centre'],
            5 => ['City Hotel', 'Conference Centre', 'Restaurant'],
            6 => ['Manufacturing Plant', 'Production Facility', 'Warehouse'],
            7 => ['Main Campus', 'Secondary Site', 'Primary School'],
            8 => ['Construction Site A', 'Construction Site B', 'Head Office']
        ];

        foreach ($employerLocations as $employerId => $locationNames) {
            $city = $this->getEmployerCity($employerId);
            foreach ($locationNames as $index => $name) {
                $locations[] = [
                    'employer_id' => $employerId,
                    'name' => $name,
                    'address' => ($index + 1) . ' ' . $city . ' Road, ' . $city,
                    'latitude' => 51.5074 + (rand(-500, 500) / 10000),
                    'longitude' => -0.1278 + (rand(-500, 500) / 10000),
                    'meta' => json_encode([
                        'facilities' => ['parking', 'canteen', 'changing_rooms'],
                        'site_manager' => 'Manager ' . $employerId . '-' . ($index + 1)
                    ]),
                    'created_at' => $now->copy()->subMonths(rand(6, 24)),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('locations')->insert($locations);
        $this->command->info('Created ' . count($locations) . ' locations');
    }

    private function getEmployerCity($employerId)
    {
        $cities = ['London', 'Manchester', 'Birmingham', 'London', 'Glasgow', 'Leeds', 'Manchester', 'Birmingham'];
        return $cities[$employerId - 1] ?? 'London';
    }

    private function createEmployees()
    {
        $employees = [];
        $now = Carbon::now();

        $positionRates = [
            'Registered Nurse' => [25.50, 32.00],
            'Senior Care Assistant' => [12.50, 16.00],
            'Healthcare Assistant' => [10.50, 13.50],
            'Head Chef' => [28.00, 35.00],
            'Sous Chef' => [18.00, 24.00],
            'Line Cook' => [11.00, 14.50],
            'HGV Driver' => [15.00, 20.00],
            'Delivery Driver' => [10.50, 13.50],
            'Warehouse Operative' => [9.50, 12.50],
            'Retail Supervisor' => [11.00, 14.00],
            'Security Officer' => [10.00, 13.00],
            'Commercial Cleaner' => [9.00, 11.50]
        ];

        for ($i = 1; $i <= 100; $i++) {
            $position = $this->positions[array_rand($this->positions)];
            $rateRange = $positionRates[$position] ?? [10.00, 15.00];
            $payRate = round(rand($rateRange[0] * 100, $rateRange[1] * 100) / 100, 2);

            $agencyId = rand(1, 5);
            $employmentType = $this->getEmploymentType($position);

            $employees[] = [
                'user_id' => 61 + $i,
                'agency_id' => $agencyId,
                'employer_id' => rand(0, 10) > 7 ? $this->getCompatibleEmployer($position) : null,
                'position' => $position,
                'pay_rate' => $payRate,
                'availability' => json_encode([
                    'preferred_hours' => rand(20, 40),
                    'notice_period' => rand(1, 14) . ' days',
                    'travel_distance' => rand(10, 50)
                ]),
                'qualifications' => json_encode($this->getRelevantQualifications($position)),
                'employment_type' => $employmentType,
                'status' => rand(0, 10) > 1 ? 'active' : 'inactive',
                'meta' => json_encode([
                    'emergency_contact' => $this->generateFirstName() . ' ' . $this->generateLastName(),
                    'national_insurance' => 'AB' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99)
                ]),
                'created_at' => $now->copy()->subMonths(rand(1, 24)),
                'updated_at' => $now,
            ];
        }

        DB::table('employees')->insert($employees);
        $this->command->info('Created ' . count($employees) . ' employees');
    }

    private function getEmploymentType($position)
    {
        if (str_contains($position, 'Nurse') || str_contains($position, 'Chef')) {
            return rand(0, 10) > 6 ? 'perm' : 'temp';
        }
        return ['temp', 'temp', 'temp', 'part_time'][array_rand([0, 1, 2, 3])];
    }

    private function getCompatibleEmployer($position)
    {
        if (str_contains($position, 'Nurse') || str_contains($position, 'Care')) return 1;
        if (str_contains($position, 'Chef') || str_contains($position, 'Cook')) return 5;
        if (str_contains($position, 'Driver')) return 3;
        if (str_contains($position, 'Warehouse')) return 6;
        if (str_contains($position, 'Retail')) return 2;
        if (str_contains($position, 'Security')) return rand(1, 8);
        return rand(1, 8);
    }

    private function getRelevantQualifications($position)
    {
        $qualifications = [];

        if (str_contains($position, 'Nurse') || str_contains($position, 'Care')) {
            $qualifications[] = $this->qualifications[0]; // First Aid
            $qualifications[] = $this->qualifications[5]; // Patient Care
        }

        if (str_contains($position, 'Chef') || str_contains($position, 'Cook')) {
            $qualifications[] = $this->qualifications[1]; // Food Hygiene
        }

        if (str_contains($position, 'Driver')) {
            $qualifications[] = $this->qualifications[6]; // HGV License
        }

        if (str_contains($position, 'Warehouse')) {
            $qualifications[] = $this->qualifications[7]; // Forklift License
        }

        if (str_contains($position, 'Security')) {
            $qualifications[] = $this->qualifications[3]; // SIA License
        }

        // Add 1-2 random additional qualifications
        $additional = array_rand($this->qualifications, rand(1, 2));
        if (!is_array($additional)) $additional = [$additional];

        foreach ($additional as $index) {
            if (!in_array($this->qualifications[$index], $qualifications)) {
                $qualifications[] = $this->qualifications[$index];
            }
        }

        return array_slice($qualifications, 0, rand(2, 4));
    }

    private function createPlacements()
    {
        $placements = [];
        $now = Carbon::now();

        $placementTemplates = [
            [
                'title' => 'Registered Nurse - Acute Ward',
                'description' => 'Experienced registered nurse required for acute medical ward. Must have recent NHS experience and valid NMC pin.',
                'budget_range' => [28.00, 35.00],
                'industry' => 'Healthcare'
            ],
            [
                'title' => 'HGV Class 1 Driver',
                'description' => 'Class 1 HGV driver for trunking operations. Night shifts, must have valid CPC and digital tachograph card.',
                'budget_range' => [16.00, 22.00],
                'industry' => 'Logistics'
            ],
            [
                'title' => 'Sous Chef - Fine Dining',
                'description' => 'Experienced sous chef for high-end restaurant. Must have fine dining experience and creative menu development skills.',
                'budget_range' => [20.00, 26.00],
                'industry' => 'Hospitality'
            ],
            [
                'title' => 'Warehouse Team Leader',
                'description' => 'Team leader for busy distribution centre. Supervisory experience required, must be forklift certified.',
                'budget_range' => [13.00, 17.00],
                'industry' => 'Logistics'
            ],
            [
                'title' => 'Retail Supervisor',
                'description' => 'Supervisor for fashion retail store. Customer service focused with team management experience.',
                'budget_range' => [11.00, 14.50],
                'industry' => 'Retail'
            ]
        ];

        for ($i = 1; $i <= 100; $i++) {
            $template = $placementTemplates[array_rand($placementTemplates)];
            $employerId = $this->getEmployerForIndustry($template['industry']);
            $locationId = DB::table('locations')
                ->where('employer_id', $employerId)
                ->inRandomOrder()
                ->value('id');

            $startDate = $now->copy()->addDays(rand(7, 60));
            $duration = rand(30, 180);
            $endDate = $startDate->copy()->addDays($duration);

            $budget = round(rand($template['budget_range'][0] * 100, $template['budget_range'][1] * 100) / 100, 2);

            $placements[] = [
                'employer_id' => $employerId,
                'title' => $template['title'] . ' #' . $i,
                'description' => $template['description'],
                'role_requirements' => json_encode(['experience' => '2+ years', 'availability' => 'Immediate']),
                'required_qualifications' => json_encode($this->getRelevantQualifications($template['title'])),
                'experience_level' => ['entry', 'intermediate', 'senior'][array_rand([0, 1, 2])],
                'background_check_required' => rand(0, 10) > 3,
                'location_id' => $locationId,
                'location_instructions' => 'Report to main reception and ask for the hiring manager',
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'shift_pattern' => ['one_time', 'recurring', 'ongoing'][array_rand([0, 1, 2])],
                'recurrence_rules' => json_encode(['type' => 'weekly', 'days' => ['mon', 'tue', 'wed', 'thu', 'fri']]),
                'budget_type' => 'hourly',
                'budget_amount' => $budget,
                'currency' => 'GBP',
                'overtime_rules' => json_encode(['rate' => '1.5x after 40 hours', 'double_time' => 'Sundays']),
                'target_agencies' => 'all',
                'specific_agency_ids' => null,
                'response_deadline' => $now->copy()->addDays(rand(7, 21)),
                'status' => ['draft', 'active', 'active', 'filled', 'cancelled'][array_rand([0, 1, 1, 2, 3])],
                'selected_agency_id' => rand(0, 10) > 3 ? rand(1, 5) : null,
                'selected_employee_id' => rand(0, 10) > 5 ? rand(1, 100) : null,
                'agreed_rate' => rand(0, 10) > 3 ? $budget * 0.85 : null,
                'created_by_id' => 21 + ($employerId - 1),
                'created_at' => $now->copy()->subDays(rand(1, 30)),
                'updated_at' => $now,
            ];
        }

        DB::table('placements')->insert($placements);
        $this->command->info('Created ' . count($placements) . ' placements');
    }

    private function getEmployerForIndustry($industry)
    {
        $industryMap = [
            'Healthcare' => 1,
            'Retail' => 2,
            'Logistics' => 3,
            'Technology' => 4,
            'Hospitality' => 5,
            'Manufacturing' => 6,
            'Education' => 7,
            'Construction' => 8
        ];
        return $industryMap[$industry] ?? rand(1, 8);
    }

    private function createEmployeeAvailabilities()
    {
        $availabilities = [];
        $now = Carbon::now();

        $patterns = [
            ['days' => EmployeeAvailability::WEEKDAYS, 'start' => '09:00', 'end' => '17:00', 'type' => 'preferred'],
            ['days' => EmployeeAvailability::WEEKDAYS, 'start' => '17:00', 'end' => '22:00', 'type' => 'available'],
            ['days' => EmployeeAvailability::WEEKENDS, 'start' => '08:00', 'end' => '16:00', 'type' => 'available'],
            ['days' => EmployeeAvailability::ALL_WEEK, 'start' => '06:00', 'end' => '18:00', 'type' => 'available'],
            ['days' => EmployeeAvailability::MONDAY + EmployeeAvailability::WEDNESDAY + EmployeeAvailability::FRIDAY, 'start' => '10:00', 'end' => '15:00', 'type' => 'preferred']
        ];

        for ($employeeId = 1; $employeeId <= 100; $employeeId++) {
            $numPatterns = rand(1, 3);
            $selectedPatterns = array_rand($patterns, $numPatterns);
            if (!is_array($selectedPatterns)) $selectedPatterns = [$selectedPatterns];

            foreach ($selectedPatterns as $patternIndex) {
                $pattern = $patterns[$patternIndex];

                $availabilities[] = [
                    'employee_id' => $employeeId,
                    'start_date' => $now->copy()->subMonths(1)->format('Y-m-d'),
                    'end_date' => $now->copy()->addMonths(6)->format('Y-m-d'),
                    'days_mask' => $pattern['days'],
                    'start_time' => $pattern['start'],
                    'end_time' => $pattern['end'],
                    'type' => $pattern['type'],
                    'priority' => $pattern['type'] === 'preferred' ? 8 : 5,
                    'max_hours' => rand(6, 10),
                    'flexible' => rand(0, 1),
                    'constraints' => json_encode(['max_commute' => rand(10, 50)]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($availabilities, 100) as $chunk) {
            DB::table('employee_availabilities')->insert($chunk);
        }
    }

    private function createTimeOffRequests()
    {
        $requests = [];
        $now = Carbon::now();

        for ($i = 1; $i <= 50; $i++) {
            $employeeId = rand(1, 100);
            $type = ['vacation', 'sick', 'personal', 'bereavement'][array_rand([0, 1, 2, 3])];
            $startDate = $now->copy()->addDays(rand(10, 180));
            $duration = $type === 'sick' ? rand(1, 5) : rand(3, 14);
            $endDate = $startDate->copy()->addDays($duration);

            $requests[] = [
                'employee_id' => $employeeId,
                'type' => $type,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'status' => $this->getTimeOffStatus($type),
                'reason' => $this->getTimeOffReason($type),
                'approved_by_id' => $type !== 'sick' && rand(0, 10) > 3 ? rand(2, 6) : null,
                'approved_at' => $type !== 'sick' && rand(0, 10) > 3 ? $now->copy()->subDays(rand(1, 10)) : null,
                'created_at' => $now->copy()->subDays(rand(5, 30)),
                'updated_at' => $now,
            ];
        }

        DB::table('time_off_requests')->insert($requests);
        $this->command->info('Created ' . count($requests) . ' time off requests');
    }

    private function getTimeOffStatus($type)
    {
        if ($type === 'sick') return 'approved';
        return ['pending', 'approved', 'approved', 'rejected'][array_rand([0, 1, 1, 2])];
    }

    private function getTimeOffReason($type)
    {
        $reasons = [
            'vacation' => ['Family holiday', 'Travel', 'Personal time'],
            'sick' => ['Illness', 'Medical appointment', 'Recovery'],
            'personal' => ['Family emergency', 'Personal matters', 'Appointments'],
            'bereavement' => ['Bereavement leave', 'Family funeral']
        ];
        $typeReasons = $reasons[$type] ?? ['Time off request'];
        return $typeReasons[array_rand($typeReasons)];
    }

    private function createShiftTemplates()
    {
        $templates = [];
        $now = Carbon::now();
        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

        $templateData = [
            ['Early Nursing Shift', '06:00', '14:00', 25.50],
            ['Late Nursing Shift', '14:00', '22:00', 27.50],
            ['Night Nursing Shift', '22:00', '06:00', 30.00],
            ['Warehouse Morning', '08:00', '16:00', 11.50],
            ['Warehouse Afternoon', '16:00', '00:00', 12.50],
            ['Retail Day', '09:00', '17:00', 10.50],
            ['Retail Evening', '13:00', '21:00', 11.00],
            ['Security Day', '08:00', '20:00', 12.00],
            ['Security Night', '20:00', '08:00', 14.00]
        ];

        foreach ($templateData as $template) {
            $employerId = $this->getEmployerForShift($template[0]);
            $locationId = DB::table('locations')
                ->where('employer_id', $employerId)
                ->inRandomOrder()
                ->value('id');

            $templates[] = [
                'employer_id' => $employerId,
                'location_id' => $locationId,
                'title' => $template[0],
                'description' => 'Regular scheduled shift',
                'day_of_week' => $days[array_rand($days)],
                'start_time' => $template[1],
                'end_time' => $template[2],
                'role_requirement' => $this->getRoleFromShift($template[0]),
                'required_qualifications' => json_encode($this->getRelevantQualifications($template[0])),
                'hourly_rate' => $template[3],
                'recurrence_type' => 'weekly',
                'status' => 'active',
                'start_date' => $now->copy()->subMonths(3)->format('Y-m-d'),
                'end_date' => $now->copy()->addMonths(6)->format('Y-m-d'),
                'created_by_type' => 'employer',
                'created_by_id' => 21 + ($employerId - 1),
                'meta' => json_encode(['auto_fill' => true]),
                'created_at' => $now->copy()->subMonths(6),
                'updated_at' => $now,
            ];
        }

        DB::table('shift_templates')->insert($templates);
        $this->command->info('Created ' . count($templates) . ' shift templates');
    }

    private function getEmployerForShift($shiftTitle)
    {
        if (str_contains($shiftTitle, 'Nursing')) return 1;
        if (str_contains($shiftTitle, 'Warehouse')) return 6;
        if (str_contains($shiftTitle, 'Retail')) return 2;
        if (str_contains($shiftTitle, 'Security')) return rand(1, 8);
        return rand(1, 8);
    }

    private function getRoleFromShift($shiftTitle)
    {
        if (str_contains($shiftTitle, 'Nursing')) return 'Registered Nurse';
        if (str_contains($shiftTitle, 'Warehouse')) return 'Warehouse Operative';
        if (str_contains($shiftTitle, 'Retail')) return 'Retail Assistant';
        if (str_contains($shiftTitle, 'Security')) return 'Security Officer';
        return 'General Worker';
    }

    private function createShifts()
    {
        $shifts = [];
        $now = Carbon::now();

        for ($i = 1; $i <= 200; $i++) {
            $employerId = rand(1, 8);
            $agencyId = rand(1, 5);
            $locationId = DB::table('locations')
                ->where('employer_id', $employerId)
                ->inRandomOrder()
                ->value('id');

            $isPast = rand(0, 10) > 3;
            $baseDate = $isPast ?
                $now->copy()->subDays(rand(1, 90)) :
                $now->copy()->addDays(rand(1, 60));

            $startTime = $baseDate->copy()
                ->setHour(rand(6, 10))
                ->setMinute(0);
            $endTime = $startTime->copy()->addHours(rand(4, 12));

            $employeeId = $isPast && rand(0, 10) > 2 ? rand(1, 100) : null;
            $placementId = $employeeId ? DB::table('placements')
                ->where('selected_employee_id', $employeeId)
                ->value('id') : null;

            $shifts[] = [
                'employer_id' => $employerId,
                'agency_id' => $agencyId,
                'placement_id' => $placementId,
                'employee_id' => $employeeId,
                'agent_id' => rand(1, 15),
                'location_id' => $locationId,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'hourly_rate' => rand(1000, 3000) / 100,
                'status' => $this->getShiftStatus($startTime, $employeeId),
                'created_by_type' => rand(0, 1) ? 'employer' : 'agency',
                'created_by_id' => rand(0, 1) ? 21 + ($employerId - 1) : rand(8, 22),
                'meta' => json_encode(['notes' => 'Scheduled shift']),
                'notes' => $this->getShiftNotes(),
                'created_at' => $startTime->copy()->subDays(rand(1, 14)),
                'updated_at' => $now,
            ];
        }

        DB::table('shifts')->insert($shifts);
        $this->command->info('Created ' . count($shifts) . ' shifts');
    }

    private function getShiftStatus($startTime, $employeeId)
    {
        $now = Carbon::now();
        if ($startTime->gt($now)) {
            return $employeeId ? 'assigned' : (rand(0, 10) > 6 ? 'offered' : 'open');
        } else {
            return $employeeId ?
                ['completed', 'agency_approved', 'employer_approved', 'billed'][array_rand([0, 1, 2, 3])] :
                'cancelled';
        }
    }

    private function getShiftNotes()
    {
        $notes = [
            'Standard shift assignment',
            'Cover for sick leave',
            'Additional support required',
            'Project-based work',
            'Seasonal demand',
            'Special event coverage'
        ];
        return $notes[array_rand($notes)];
    }

    private function createShiftOffers()
    {
        $offers = [];
        $now = Carbon::now();

        $openShifts = DB::table('shifts')
            ->whereIn('status', ['open', 'offered'])
            ->where('start_time', '>', $now)
            ->limit(30)
            ->get();

        foreach ($openShifts as $shift) {
            $numOffers = rand(1, 4);
            $offeredEmployees = [];

            for ($i = 1; $i <= $numOffers; $i++) {
                do {
                    $employeeId = rand(1, 100);
                } while (in_array($employeeId, $offeredEmployees));

                $offeredEmployees[] = $employeeId;

                $offers[] = [
                    'shift_id' => $shift->id,
                    'employee_id' => $employeeId,
                    'offered_by_id' => rand(8, 22),
                    'status' => $this->getOfferStatus(),
                    'expires_at' => $now->copy()->addHours(rand(24, 72)),
                    'responded_at' => rand(0, 10) > 4 ? $now->copy()->subHours(rand(1, 12)) : null,
                    'response_notes' => $this->getOfferResponse(),
                    'created_at' => $now->copy()->subHours(rand(1, 48)),
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

    private function getOfferResponse()
    {
        $responses = [
            'Accepted shift',
            'Unavailable due to other commitment',
            'Accepted alternative shift',
            'Rate not acceptable',
            'Location too far'
        ];
        return rand(0, 10) > 6 ? $responses[array_rand($responses)] : null;
    }

    private function createTimesheets()
    {
        $timesheets = [];
        $now = Carbon::now();

        $completedShifts = DB::table('shifts')
            ->whereIn('status', ['completed', 'agency_approved', 'employer_approved', 'billed'])
            ->whereNotNull('employee_id')
            ->get();

        foreach ($completedShifts as $shift) {
            $clockIn = Carbon::parse($shift->start_time);
            $clockOut = Carbon::parse($shift->end_time);

            // Add some realistic variance to clock in/out times
            $actualClockIn = $clockIn->copy()->addMinutes(rand(-15, 30));
            $actualClockOut = $clockOut->copy()->addMinutes(rand(-10, 45));

            $breakMinutes = rand(0, 1) ? 30 : 45;
            $hoursWorked = $actualClockOut->diffInMinutes($actualClockIn) / 60 - ($breakMinutes / 60);

            $timesheets[] = [
                'shift_id' => $shift->id,
                'employee_id' => $shift->employee_id,
                'clock_in' => $actualClockIn,
                'clock_out' => $actualClockOut,
                'break_minutes' => $breakMinutes,
                'hours_worked' => round($hoursWorked, 2),
                'status' => $this->getTimesheetStatus($shift->status),
                'agency_approved_by' => in_array($shift->status, ['agency_approved', 'employer_approved', 'billed']) ? rand(2, 6) : null,
                'agency_approved_at' => in_array($shift->status, ['agency_approved', 'employer_approved', 'billed']) ? $clockOut->copy()->addHours(rand(24, 48)) : null,
                'approved_by_contact_id' => in_array($shift->status, ['employer_approved', 'billed']) ? $this->getContactForEmployer($shift->employer_id) : null,
                'approved_at' => in_array($shift->status, ['employer_approved', 'billed']) ? $clockOut->copy()->addHours(rand(72, 168)) : null,
                'notes' => 'Completed shift as scheduled',
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

    private function getContactForEmployer($employerId)
    {
        $contact = DB::table('contacts')
            ->where('employer_id', $employerId)
            ->where('can_sign_timesheets', true)
            ->inRandomOrder()
            ->first();
        return $contact ? $contact->id : null;
    }

    private function createShiftApprovals()
    {
        $approvals = [];
        $now = Carbon::now();

        $approvedShifts = DB::table('shifts')
            ->whereIn('status', ['employer_approved', 'billed'])
            ->get();

        foreach ($approvedShifts as $shift) {
            $contactId = $this->getContactForEmployer($shift->employer_id);
            if (!$contactId) continue;

            $approvals[] = [
                'shift_id' => $shift->id,
                'contact_id' => $contactId,
                'status' => 'approved',
                'signed_at' => Carbon::parse($shift->start_time)->copy()->addHours(rand(48, 168)),
                'notes' => 'Shift completed satisfactorily',
                'created_at' => Carbon::parse($shift->start_time)->copy()->addHours(rand(24, 72)),
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

        // Generate invoices for the last 3 months
        for ($month = 1; $month <= 3; $month++) {
            $invoiceDate = $now->copy()->subMonths($month)->startOfMonth();
            $numInvoices = rand(15, 25);

            for ($i = 1; $i <= $numInvoices; $i++) {
                $type = ['employer_to_agency', 'agency_to_shiftpilot'][array_rand([0, 1])];
                list($fromType, $fromId, $toType, $toId) = $this->getInvoiceParties($type);

                $subtotal = rand(1500, 25000) + (rand(0, 99) / 100);
                $taxAmount = round($subtotal * 0.2, 2);
                $totalAmount = $subtotal + $taxAmount;

                $invoices[] = [
                    'type' => $type,
                    'from_type' => $fromType,
                    'from_id' => $fromId,
                    'to_type' => $toType,
                    'to_id' => $toId,
                    'reference' => 'INV-' . $invoiceDate->format('Ym') . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'line_items' => json_encode($this->generateInvoiceLineItems($subtotal, $month)),
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'status' => $this->getInvoiceStatus($invoiceDate),
                    'due_date' => $invoiceDate->copy()->addDays(30)->format('Y-m-d'),
                    'paid_at' => $this->getPaidAtDate($invoiceDate),
                    'payment_reference' => $this->getPaymentReference(),
                    'metadata' => json_encode(['billing_period' => $invoiceDate->format('F Y')]),
                    'created_at' => $invoiceDate,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('invoices')->insert($invoices);
        $this->command->info('Created ' . count($invoices) . ' invoices');
    }

    private function getInvoiceParties($type)
    {
        if ($type === 'employer_to_agency') {
            $employerId = rand(1, 8);
            // Get an agency that has a relationship with this employer
            $agencyId = DB::table('employer_agency_links')
                ->where('employer_id', $employerId)
                ->inRandomOrder()
                ->value('agency_id');
            return ['employer', $employerId, 'agency', $agencyId ?? 1];
        } else {
            $agencyId = rand(1, 5);
            return ['agency', $agencyId, 'shiftpilot', 1];
        }
    }

    private function generateInvoiceLineItems($subtotal, $month)
    {
        $items = [];
        $baseDescription = 'Temporary staffing services for ' . $this->getMonthName($month);

        $items[] = [
            'description' => $baseDescription,
            'quantity' => 1,
            'unit_price' => $subtotal,
            'tax_rate' => 20.00,
            'total' => $subtotal
        ];

        // Add platform fee for agency_to_shiftpilot invoices
        if (rand(0, 10) > 7) {
            $feeAmount = $subtotal * 0.02; // 2% platform fee
            $items[] = [
                'description' => 'Platform service fee',
                'quantity' => 1,
                'unit_price' => $feeAmount,
                'tax_rate' => 20.00,
                'total' => $feeAmount
            ];
        }

        return $items;
    }

    private function getMonthName($monthOffset)
    {
        $months = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];
        $currentMonth = (int)date('n') - 1;
        $targetMonth = ($currentMonth - $monthOffset + 12) % 12;
        return $months[$targetMonth] . ' ' . date('Y');
    }

    private function getInvoiceStatus($invoiceDate)
    {
        $daysSince = Carbon::now()->diffInDays($invoiceDate);
        if ($daysSince > 60) return 'overdue';
        if ($daysSince > 30) return rand(0, 10) > 6 ? 'overdue' : 'paid';
        return ['paid', 'paid', 'pending'][array_rand([0, 0, 1])];
    }

    private function getPaidAtDate($invoiceDate)
    {
        return rand(0, 10) > 3 ? $invoiceDate->copy()->addDays(rand(5, 25)) : null;
    }

    private function getPaymentReference()
    {
        return rand(0, 10) > 4 ? 'PMT' . strtoupper(\Illuminate\Support\Str::random(10)) : null;
    }

    private function createPayments()
    {
        $payments = [];
        $now = Carbon::now();

        $paidInvoices = DB::table('invoices')
            ->whereNotNull('paid_at')
            ->get();

        foreach ($paidInvoices as $invoice) {
            $method = ['stripe', 'bacs', 'direct_debit'][array_rand([0, 1, 2])];
            $feeAmount = $invoice->total_amount * 0.029 + 0.30;
            $netAmount = $invoice->total_amount - $feeAmount;

            $payments[] = [
                'invoice_id' => $invoice->id,
                'payer_type' => $invoice->from_type,
                'payer_id' => $invoice->from_id,
                'amount' => $invoice->total_amount,
                'method' => $method,
                'processor_id' => 'pay_' . strtoupper(\Illuminate\Support\Str::random(14)),
                'status' => 'completed',
                'fee_amount' => $feeAmount,
                'net_amount' => $netAmount,
                'metadata' => json_encode([
                    'payment_method' => $method,
                    'processor_fee' => $feeAmount
                ]),
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

        // Create payroll for the last 2 months
        for ($month = 1; $month <= 2; $month++) {
            $periodStart = $now->copy()->subMonths($month)->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth();

            // Create payroll for 70% of active employees
            $activeEmployees = DB::table('employees')
                ->where('status', 'active')
                ->inRandomOrder()
                ->limit(70)
                ->get();

            foreach ($activeEmployees as $employee) {
                $totalHours = rand(80, 180);
                $grossPay = round($totalHours * $employee->pay_rate, 2);
                $taxes = round($grossPay * 0.2, 2);
                $netPay = $grossPay - $taxes;

                $payrolls[] = [
                    'agency_id' => $employee->agency_id,
                    'employee_id' => $employee->id,
                    'period_start' => $periodStart->format('Y-m-d'),
                    'period_end' => $periodEnd->format('Y-m-d'),
                    'total_hours' => $totalHours,
                    'gross_pay' => $grossPay,
                    'taxes' => $taxes,
                    'net_pay' => $netPay,
                    'status' => 'paid',
                    'paid_at' => $periodEnd->copy()->addDays(rand(5, 10)),
                    'payout_id' => null,
                    'created_at' => $periodEnd,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('payrolls')->insert($payrolls);
        $this->command->info('Created ' . count($payrolls) . ' payroll records');
    }

    private function createPayouts()
    {
        $payouts = [];
        $now = Carbon::now();

        for ($agencyId = 1; $agencyId <= 5; $agencyId++) {
            for ($month = 1; $month <= 2; $month++) {
                $periodStart = $now->copy()->subMonths($month)->startOfMonth();
                $periodEnd = $periodStart->copy()->endOfMonth();

                $totalAmount = DB::table('payrolls')
                    ->where('agency_id', $agencyId)
                    ->where('period_start', $periodStart->format('Y-m-d'))
                    ->sum('gross_pay');

                if ($totalAmount > 0) {
                    $payouts[] = [
                        'agency_id' => $agencyId,
                        'period_start' => $periodStart->format('Y-m-d'),
                        'period_end' => $periodEnd->format('Y-m-d'),
                        'total_amount' => $totalAmount,
                        'status' => 'paid',
                        'provider_payout_id' => 'po_' . strtoupper(\Illuminate\Support\Str::random(14)),
                        'metadata' => json_encode([
                            'period' => $periodStart->format('F Y'),
                            'employee_count' => DB::table('payrolls')
                                ->where('agency_id', $agencyId)
                                ->where('period_start', $periodStart->format('Y-m-d'))
                                ->count()
                        ]),
                        'created_at' => $periodEnd->copy()->addDays(7),
                        'updated_at' => $now,
                    ];
                }
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
            'agency_pro' => ['name' => 'Agency Pro Plan', 'amount' => 199.00],
            'employer_basic' => ['name' => 'Employer Basic Plan', 'amount' => 99.00],
            'employer_premium' => ['name' => 'Employer Premium Plan', 'amount' => 199.00]
        ];

        // Agency subscriptions
        for ($agencyId = 1; $agencyId <= 5; $agencyId++) {
            $plan = 'agency_pro';
            $startedAt = $now->copy()->subMonths(rand(6, 18));

            $subscriptions[] = [
                'entity_type' => 'agency',
                'entity_id' => $agencyId,
                'plan_key' => $plan,
                'plan_name' => $plans[$plan]['name'],
                'amount' => $plans[$plan]['amount'],
                'interval' => 'monthly',
                'status' => 'active',
                'started_at' => $startedAt,
                'current_period_start' => $now->copy()->startOfMonth(),
                'current_period_end' => $now->copy()->addMonth()->startOfMonth(),
                'meta' => json_encode([
                    'billing_cycle' => 'monthly',
                    'payment_method' => 'direct_debit',
                    'auto_renew' => true
                ]),
                'created_at' => $startedAt,
                'updated_at' => $now,
            ];
        }

        // Employer subscriptions
        for ($employerId = 1; $employerId <= 8; $employerId++) {
            $plan = rand(0, 10) > 6 ? 'employer_premium' : 'employer_basic';
            $startedAt = $now->copy()->subMonths(rand(3, 12));

            $subscriptions[] = [
                'entity_type' => 'employer',
                'entity_id' => $employerId,
                'plan_key' => $plan,
                'plan_name' => $plans[$plan]['name'],
                'amount' => $plans[$plan]['amount'],
                'interval' => 'monthly',
                'status' => 'active',
                'started_at' => $startedAt,
                'current_period_start' => $now->copy()->startOfMonth(),
                'current_period_end' => $now->copy()->addMonth()->startOfMonth(),
                'meta' => json_encode([
                    'billing_cycle' => 'monthly',
                    'users_included' => $plan === 'employer_premium' ? 10 : 5,
                    'features' => $plan === 'employer_premium' ? ['advanced_analytics', 'priority_support'] : ['basic_support']
                ]),
                'created_at' => $startedAt,
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
            'created_at' => Carbon::now()->subYear(),
            'updated_at' => Carbon::now(),
        ]);

        $this->command->info('Created platform billing settings');
    }

    private function createRateCards()
    {
        $rateCards = [];
        $now = Carbon::now();

        $roleRates = [
            'Registered Nurse' => [28.00, 35.00],
            'Senior Care Assistant' => [13.00, 17.00],
            'Healthcare Assistant' => [11.00, 14.50],
            'HGV Driver' => [16.00, 22.00],
            'Warehouse Operative' => [10.00, 13.50],
            'Retail Supervisor' => [11.50, 15.00],
            'Security Officer' => [10.50, 14.00],
            'Commercial Cleaner' => [9.50, 12.00]
        ];

        foreach ($roleRates as $role => $range) {
            for ($i = 1; $i <= 3; $i++) {
                $employerId = $this->getEmployerForRole($role);
                $locationId = DB::table('locations')
                    ->where('employer_id', $employerId)
                    ->inRandomOrder()
                    ->value('id');

                $rateCards[] = [
                    'employer_id' => $employerId,
                    'agency_id' => null,
                    'role_key' => strtolower(str_replace(' ', '_', $role)),
                    'location_id' => $locationId,
                    'day_of_week' => null,
                    'start_time' => null,
                    'end_time' => null,
                    'rate' => round(rand($range[0] * 100, $range[1] * 100) / 100, 2),
                    'currency' => 'GBP',
                    'effective_from' => $now->copy()->subMonths(3)->format('Y-m-d'),
                    'effective_to' => $now->copy()->addYears(1)->format('Y-m-d'),
                    'created_at' => $now->copy()->subMonths(4),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('rate_cards')->insert($rateCards);
        $this->command->info('Created ' . count($rateCards) . ' rate cards');
    }

    private function getEmployerForRole($role)
    {
        if (str_contains($role, 'Nurse') || str_contains($role, 'Care')) return 1;
        if (str_contains($role, 'Driver')) return 3;
        if (str_contains($role, 'Warehouse')) return 6;
        if (str_contains($role, 'Retail')) return 2;
        if (str_contains($role, 'Security')) return rand(1, 8);
        if (str_contains($role, 'Cleaner')) return rand(1, 8);
        return rand(1, 8);
    }

    private function createNotifications()
    {
        $notifications = [];
        $now = Carbon::now();

        $templates = [
            'shift.assigned:employee' => 'You have been assigned to a shift',
            'shift_offer.sent:employee' => 'New shift offer available',
            'timesheet.submitted:agency' => 'Timesheet submitted for approval',
            'invoice.generated:employer' => 'New invoice available',
            'shift.completed:agency' => 'Shift completed awaiting approval',
            'time_off.approved:employee' => 'Your time off request has been approved'
        ];

        for ($i = 1; $i <= 150; $i++) {
            $templateKey = array_rand($templates);
            $recipientType = ['user', 'agency', 'employer'][array_rand([0, 1, 2])];
            $recipientId = $recipientType === 'user' ? rand(2, 161) : ($recipientType === 'agency' ? rand(1, 5) : rand(1, 8));

            $notifications[] = [
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'channel' => ['email', 'in_app'][array_rand([0, 1])],
                'template_key' => $templateKey,
                'payload' => json_encode([
                    'message' => $templates[$templateKey],
                    'action_url' => '/notifications/' . $i
                ]),
                'is_read' => rand(0, 10) > 4,
                'sent_at' => $now->copy()->subHours(rand(1, 168)),
                'created_at' => $now->copy()->subHours(rand(1, 168)),
                'updated_at' => $now,
            ];
        }

        DB::table('notifications')->insert($notifications);
        $this->command->info('Created ' . count($notifications) . ' notifications');
    }

    private function generateFirstName()
    {
        $names = [
            'James',
            'Mary',
            'John',
            'Patricia',
            'Robert',
            'Jennifer',
            'Michael',
            'Linda',
            'William',
            'Elizabeth',
            'David',
            'Barbara',
            'Richard',
            'Susan',
            'Joseph',
            'Jessica',
            'Thomas',
            'Sarah',
            'Charles',
            'Karen',
            'Christopher',
            'Nancy',
            'Daniel',
            'Lisa',
            'Matthew',
            'Betty',
            'Anthony',
            'Margaret',
            'Mark',
            'Sandra'
        ];
        return $names[array_rand($names)];
    }

    private function generateLastName()
    {
        $names = [
            'Smith',
            'Johnson',
            'Williams',
            'Brown',
            'Jones',
            'Garcia',
            'Miller',
            'Davis',
            'Rodriguez',
            'Martinez',
            'Hernandez',
            'Lopez',
            'Gonzalez',
            'Wilson',
            'Anderson',
            'Thomas',
            'Taylor',
            'Moore',
            'Jackson',
            'Martin'
        ];
        return $names[array_rand($names)];
    }

    private function generateTime($startHour, $endHour)
    {
        $hour = rand($startHour, $endHour);
        $minute = rand(0, 1) ? '00' : '30';
        return sprintf('%02d:%02d', $hour, $minute);
    }
}
