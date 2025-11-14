<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ShiftPilotSeeder extends Seeder
{

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
        $this->createAgencyEmployees();
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

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function createUsers()
    {
        $users = [];
        $now = Carbon::now();

        // Super Admin
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

        // Agency Admins
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

        // Agents
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

        // Employer Admins
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

        // Contacts
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

        // Employees
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
                'name' => $data[0],
                'legal_name' => $data[0],
                'registration_number' => $data[1],
                'billing_email' => 'accounts@' . strtolower(str_replace([' ', 'Ltd', 'PLC', 'Limited'], '', $data[0])) . '.com',
                'phone' => '+4416329600' . (10 + $index),
                'address_line1' => ($index + 1) . ' Business Park',
                'city' => 'London',
                'postcode' => 'E1 6AN',
                'country' => 'GB',
                'default_markup_percent' => $data[2],
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
                'name' => $data[0],
                'legal_name' => $data[0],
                'billing_email' => 'finance@' . strtolower(str_replace([' ', '\'', 'PLC', 'Ltd', 'Trust', 'Group', 'Co'], '', $data[0])) . '.com',
                'phone' => '+441632980' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'address_line1' => rand(1, 100) . ' ' . $data[2] . ' Road',
                'city' => $data[2],
                'postcode' => $this->generatePostcode($data[2]),
                'country' => 'GB',
                'industry' => $data[1],
                'company_size' => ['1-50', '51-200', '201-500', '501-1000', '1000+'][array_rand([0, 1, 2, 3, 4])],
                'status' => 'active',
                'meta' => json_encode([
                    'established' => rand(1990, 2015),
                    'website' => 'https://' . strtolower(str_replace([' ', '\'', 'PLC', 'Ltd', 'Trust'], '', $data[0])) . '.com'
                ]),
                'created_at' => $now->copy()->subMonths(rand(12, 36)),
                'updated_at' => $now,
            ];
        }

        DB::table('employers')->insert($employers);
        $this->command->info('Created ' . count($employers) . ' employers');
    }

    private function generatePostcode($city)
    {
        $prefixes = [
            'London' => ['E1', 'W1', 'SW1', 'NW1', 'SE1'],
            'Manchester' => ['M1', 'M2', 'M3', 'M4'],
            'Birmingham' => ['B1', 'B2', 'B3', 'B4'],
            'Glasgow' => ['G1', 'G2', 'G3', 'G4'],
            'Leeds' => ['LS1', 'LS2', 'LS3', 'LS4']
        ];

        $prefix = $prefixes[$city][array_rand($prefixes[$city])] ?? 'AB1';
        return $prefix . ' ' . rand(1, 9) . 'AB';
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
                    'role' => $j === 0 ? 'manager' : ($j === 1 ? 'approver' : 'supervisor'),
                    'can_approve_timesheets' => $j !== 3,
                    'can_approve_assignments' => $j === 0,
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
                    'address_line1' => ($index + 1) . ' ' . $city . ' Road',
                    'city' => $city,
                    'postcode' => $this->generatePostcode($city),
                    'country' => 'GB',
                    'latitude' => 51.5074 + (rand(-500, 500) / 10000),
                    'longitude' => -0.1278 + (rand(-500, 500) / 10000),
                    'location_type' => $this->getLocationType($name),
                    'contact_name' => 'Manager ' . $employerId . '-' . ($index + 1),
                    'contact_phone' => '+44163299' . str_pad($employerId * 10 + $index, 3, '0', STR_PAD_LEFT),
                    'instructions' => 'Report to main reception',
                    'meta' => json_encode([
                        'facilities' => ['parking', 'canteen', 'changing_rooms'],
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

    private function getLocationType($name)
    {
        if (str_contains($name, 'Hospital') || str_contains($name, 'Clinic')) return 'healthcare';
        if (str_contains($name, 'Store') || str_contains($name, 'Retail')) return 'retail';
        if (str_contains($name, 'Depot') || str_contains($name, 'Warehouse')) return 'warehouse';
        if (str_contains($name, 'Office') || str_contains($name, 'Campus')) return 'office';
        if (str_contains($name, 'Hotel') || str_contains($name, 'Restaurant')) return 'hospitality';
        if (str_contains($name, 'Plant') || str_contains($name, 'Facility')) return 'manufacturing';
        if (str_contains($name, 'School') || str_contains($name, 'Campus')) return 'education';
        if (str_contains($name, 'Construction')) return 'construction';
        return 'other';
    }

    private function createEmployees()
    {
        $employees = [];
        $now = Carbon::now();

        for ($i = 1; $i <= 100; $i++) {
            $employees[] = [
                'user_id' => 61 + $i,
                'national_insurance_number' => 'AB' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                'date_of_birth' => $now->copy()->subYears(rand(18, 65))->subMonths(rand(0, 11))->format('Y-m-d'),
                'address_line1' => rand(1, 100) . ' Main Street',
                'city' => ['London', 'Manchester', 'Birmingham', 'Leeds', 'Glasgow'][array_rand([0, 1, 2, 3, 4])],
                'postcode' => $this->generatePostcode('London'),
                'country' => 'GB',
                'emergency_contact_name' => $this->generateFirstName() . ' ' . $this->generateLastName(),
                'emergency_contact_phone' => '+447' . rand(500000000, 799999999),
                'qualifications' => json_encode($this->getRandomQualifications()),
                'certifications' => json_encode($this->getRandomCertifications()),
                'status' => rand(0, 10) > 1 ? 'active' : 'inactive',
                'meta' => json_encode([
                    'preferred_shift_types' => ['day', 'evening', 'night'][array_rand([0, 1, 2])],
                    'max_travel_distance' => rand(10, 50)
                ]),
                'created_at' => $now->copy()->subMonths(rand(1, 24)),
                'updated_at' => $now,
            ];
        }

        DB::table('employees')->insert($employees);
        $this->command->info('Created ' . count($employees) . ' employees');
    }

    private function createAgencyEmployees()
    {
        $agencyEmployees = [];
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

        for ($employeeId = 1; $employeeId <= 100; $employeeId++) {
            $numAgencies = rand(1, 3);
            $agencyIds = array_rand(range(1, 5), $numAgencies);
            if (!is_array($agencyIds)) $agencyIds = [$agencyIds];

            foreach ($agencyIds as $agencyId) {
                $position = $this->positions[array_rand($this->positions)];
                $rateRange = $positionRates[$position] ?? [10.00, 15.00];
                $payRate = round(rand($rateRange[0] * 100, $rateRange[1] * 100) / 100, 2);

                $agencyEmployees[] = [
                    'agency_id' => $agencyId + 1,
                    'employee_id' => $employeeId,
                    'position' => $position,
                    'pay_rate' => $payRate,
                    'employment_type' => $this->getEmploymentType($position),
                    'status' => 'active',
                    'contract_start_date' => $now->copy()->subMonths(rand(1, 12))->format('Y-m-d'),
                    'specializations' => json_encode($this->getRelevantSpecializations($position)),
                    'max_weekly_hours' => rand(20, 48),
                    'meta' => json_encode([
                        'preferred_locations' => $this->getPreferredLocations(),
                        'skills' => $this->getRelevantSkills($position)
                    ]),
                    'created_at' => $now->copy()->subMonths(rand(1, 12)),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('agency_employees')->insert($agencyEmployees);
        $this->command->info('Created ' . count($agencyEmployees) . ' agency-employee relationships');
    }

    private function getEmploymentType($position)
    {
        if (str_contains($position, 'Nurse') || str_contains($position, 'Chef')) {
            return rand(0, 10) > 6 ? 'perm' : 'temp';
        }
        return ['temp', 'temp', 'temp', 'part_time'][array_rand([0, 1, 2, 3])];
    }

    private function getRelevantSpecializations($position)
    {
        if (str_contains($position, 'Nurse')) return ['Acute Care', 'Medical Ward', 'Surgical'];
        if (str_contains($position, 'Care')) return ['Elderly Care', 'Dementia', 'Personal Care'];
        if (str_contains($position, 'Chef')) return ['Fine Dining', 'Banquets', 'A La Carte'];
        if (str_contains($position, 'Driver')) return ['Long Haul', 'Local Delivery', 'Refrigerated'];
        if (str_contains($position, 'Warehouse')) return ['Picking', 'Packing', 'Inventory'];
        return ['General'];
    }

    private function getPreferredLocations()
    {
        $locations = ['Central London', 'East London', 'West London', 'Manchester City Centre', 'Birmingham'];
        return array_rand($locations, rand(1, 3));
    }

    private function getRelevantSkills($position)
    {
        $skills = [];
        if (str_contains($position, 'Nurse') || str_contains($position, 'Care')) {
            $skills = ['Patient Care', 'Medication Administration', 'Wound Care'];
        } elseif (str_contains($position, 'Chef') || str_contains($position, 'Cook')) {
            $skills = ['Food Preparation', 'Menu Planning', 'Kitchen Management'];
        } elseif (str_contains($position, 'Driver')) {
            $skills = ['Route Planning', 'Vehicle Maintenance', 'Customer Service'];
        } else {
            $skills = ['Teamwork', 'Communication', 'Problem Solving'];
        }
        return array_slice($skills, 0, rand(2, 4));
    }

    private function getRandomQualifications()
    {
        $count = rand(2, 5);
        $selected = array_rand($this->qualifications, $count);
        if (!is_array($selected)) $selected = [$selected];

        $result = [];
        foreach ($selected as $index) {
            $result[] = $this->qualifications[$index];
        }
        return $result;
    }

    private function getRandomCertifications()
    {
        $certifications = [
            ['name' => 'Health and Safety Certificate', 'issued' => '2023-01-15'],
            ['name' => 'Manual Handling Training', 'issued' => '2023-03-20'],
            ['name' => 'Fire Safety Training', 'issued' => '2023-05-10'],
            ['name' => 'Customer Service Excellence', 'issued' => '2023-07-05'],
        ];
        return array_slice($certifications, 0, rand(1, 3));
    }

    private function createEmployeeAvailabilities()
    {
        $availabilities = [];
        $now = Carbon::now();

        $patterns = [
            ['days' => 31, 'start' => '09:00', 'end' => '17:00', 'type' => 'preferred'], // Weekdays
            ['days' => 31, 'start' => '17:00', 'end' => '22:00', 'type' => 'available'], // Weekdays evening
            ['days' => 96, 'start' => '08:00', 'end' => '16:00', 'type' => 'available'], // Weekends
            ['days' => 127, 'start' => '06:00', 'end' => '18:00', 'type' => 'available'], // All week
        ];

        for ($employeeId = 1; $employeeId <= 100; $employeeId++) {
            $numPatterns = rand(1, 2);
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

        DB::table('employee_availabilities')->insert($availabilities);
        $this->command->info('Created ' . count($availabilities) . ' employee availabilities');
    }

    private function createTimeOffRequests()
    {
        $requests = [];
        $now = Carbon::now();

        for ($i = 1; $i <= 50; $i++) {
            $employeeId = rand(1, 100);
            $agencyId = DB::table('agency_employees')
                ->where('employee_id', $employeeId)
                ->inRandomOrder()
                ->value('agency_id');

            $type = ['vacation', 'sick', 'personal', 'bereavement'][array_rand([0, 1, 2, 3])];
            $startDate = $now->copy()->addDays(rand(10, 180));
            $duration = $type === 'sick' ? rand(1, 5) : rand(3, 14);
            $endDate = $startDate->copy()->addDays($duration);

            $requests[] = [
                'employee_id' => $employeeId,
                'agency_id' => $agencyId,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'type' => $type,
                'reason' => $this->getTimeOffReason($type),
                'status' => $this->getTimeOffStatus($type),
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

        // Get some agency employees to assign templates to
        $agencyEmployees = DB::table('agency_employees')
            ->where('status', 'active')
            ->limit(20)
            ->get();

        $templateData = [
            ['Early Nursing Shift', '06:00', '14:00'],
            ['Late Nursing Shift', '14:00', '22:00'],
            ['Night Nursing Shift', '22:00', '06:00'],
            ['Warehouse Morning', '08:00', '16:00'],
            ['Warehouse Afternoon', '16:00', '00:00'],
            ['Retail Day', '09:00', '17:00'],
            ['Retail Evening', '13:00', '21:00'],
            ['Security Day', '08:00', '20:00'],
            ['Security Night', '20:00', '08:00']
        ];

        foreach ($agencyEmployees as $agencyEmployee) {
            $template = $templateData[array_rand($templateData)];

            $templates[] = [
                'assignment_id' => $this->getAssignmentForAgencyEmployee($agencyEmployee->id),
                'name' => $template[0],
                'day_of_week' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'][array_rand([0, 1, 2, 3, 4, 5, 6])],
                'start_time' => $template[1],
                'end_time' => $template[2],
                'break_minutes' => 30,
                'required_employees' => 1,
                'recurrence_pattern' => json_encode(['type' => 'weekly', 'interval' => 1]),
                'effective_start_date' => $now->copy()->subMonths(3)->format('Y-m-d'),
                'effective_end_date' => $now->copy()->addMonths(6)->format('Y-m-d'),
                'last_generated_date' => $now->copy()->subDays(rand(1, 7))->format('Y-m-d'),
                'notes' => 'Regular scheduled shift template',
                'created_at' => $now->copy()->subMonths(6),
                'updated_at' => $now,
            ];
        }

        DB::table('shift_templates')->insert($templates);
        $this->command->info('Created ' . count($templates) . ' shift templates');
    }

    private function getAssignmentForAgencyEmployee($agencyEmployeeId)
    {
        return 1;
    }

    private function createShifts()
    {
        $shifts = [];
        $now = Carbon::now();

        $agencyEmployees = DB::table('agency_employees')
            ->where('status', 'active')
            ->limit(50)
            ->get();

        foreach ($agencyEmployees as $agencyEmployee) {
            $numShifts = rand(1, 4);

            for ($i = 1; $i <= $numShifts; $i++) {
                $isPast = rand(0, 10) > 3;
                $baseDate = $isPast ?
                    $now->copy()->subDays(rand(1, 90)) :
                    $now->copy()->addDays(rand(1, 60));

                $startTime = $baseDate->copy()
                    ->setHour(rand(6, 10))
                    ->setMinute(0);
                $endTime = $startTime->copy()->addHours(rand(4, 12));

                $shifts[] = [
                    'assignment_id' => $this->getAssignmentForAgencyEmployee($agencyEmployee->id),
                    'shift_date' => $baseDate->format('Y-m-d'),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'hourly_rate' => $agencyEmployee->pay_rate,
                    'status' => $this->getShiftStatus($startTime),
                    'notes' => $this->getShiftNotes(),
                    'meta' => json_encode(['created_via' => 'seeder']),
                    'created_at' => $startTime->copy()->subDays(rand(1, 14)),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('shifts')->insert($shifts);
        $this->command->info('Created ' . count($shifts) . ' shifts');
    }

    private function getShiftStatus($startTime)
    {
        $now = Carbon::now();
        if ($startTime->gt($now)) {
            return 'scheduled';
        } else {
            return ['completed', 'completed', 'completed', 'no_show'][array_rand([0, 0, 0, 1])];
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
            ->where('status', 'scheduled')
            ->where('start_time', '>', $now)
            ->limit(20)
            ->get();

        foreach ($openShifts as $shift) {
            $numOffers = rand(1, 3);
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
            ->where('status', 'completed')
            ->limit(30)
            ->get();

        foreach ($completedShifts as $shift) {
            $employeeId = $this->getEmployeeForShift($shift->id);
            if (!$employeeId) continue;

            $clockIn = Carbon::parse($shift->start_time);
            $clockOut = Carbon::parse($shift->end_time);

            $actualClockIn = $clockIn->copy()->addMinutes(rand(-15, 30));
            $actualClockOut = $clockOut->copy()->addMinutes(rand(-10, 45));

            $breakMinutes = rand(0, 1) ? 30 : 45;
            $hoursWorked = $actualClockOut->diffInMinutes($actualClockIn) / 60 - ($breakMinutes / 60);

            $timesheets[] = [
                'shift_id' => $shift->id,
                'employee_id' => $employeeId,
                'clock_in' => $actualClockIn,
                'clock_out' => $actualClockOut,
                'break_minutes' => $breakMinutes,
                'hours_worked' => round($hoursWorked, 2),
                'status' => $this->getTimesheetStatus(),
                'agency_approved_by' => rand(0, 10) > 3 ? rand(2, 6) : null,
                'agency_approved_at' => rand(0, 10) > 3 ? $clockOut->copy()->addHours(rand(24, 48)) : null,
                'approved_by_contact_id' => rand(0, 10) > 5 ? $this->getRandomContact() : null,
                'approved_at' => rand(0, 10) > 5 ? $clockOut->copy()->addHours(rand(72, 168)) : null,
                'notes' => 'Completed shift as scheduled',
                'attachments' => json_encode([]),
                'created_at' => $clockOut,
                'updated_at' => $now,
            ];
        }

        DB::table('timesheets')->insert($timesheets);
        $this->command->info('Created ' . count($timesheets) . ' timesheets');
    }

    private function getEmployeeForShift($shiftId)
    {
        return rand(1, 100);
    }

    private function getTimesheetStatus()
    {
        return ['pending', 'agency_approved', 'employer_approved'][array_rand([0, 1, 2])];
    }

    private function getRandomContact()
    {
        $contact = DB::table('contacts')->inRandomOrder()->first();
        return $contact ? $contact->id : null;
    }

    private function createShiftApprovals()
    {
        $approvals = [];
        $now = Carbon::now();

        $approvedShifts = DB::table('shifts')
            ->where('status', 'completed')
            ->limit(20)
            ->get();

        foreach ($approvedShifts as $shift) {
            $contactId = $this->getRandomContact();
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

        for ($month = 1; $month <= 3; $month++) {
            $invoiceDate = $now->copy()->subMonths($month)->startOfMonth();
            $numInvoices = rand(10, 20);

            for ($i = 1; $i <= $numInvoices; $i++) {
                $type = ['employer_to_agency', 'agency_to_platform'][array_rand([0, 1])];
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
                    'line_items' => json_encode($this->generateInvoiceLineItems($subtotal)),
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
            $agencyId = DB::table('employer_agency_links')
                ->where('employer_id', $employerId)
                ->inRandomOrder()
                ->value('agency_id');
            return ['employer', $employerId, 'agency', $agencyId ?? 1];
        } else {
            $agencyId = rand(1, 5);
            return ['agency', $agencyId, 'platform', 1];
        }
    }

    private function generateInvoiceLineItems($subtotal)
    {
        return [
            [
                'description' => 'Temporary staffing services',
                'quantity' => 1,
                'unit_price' => $subtotal,
                'tax_rate' => 20.00,
                'total' => $subtotal
            ]
        ];
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

        for ($month = 1; $month <= 2; $month++) {
            $periodStart = $now->copy()->subMonths($month)->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth();

            $activeAgencyEmployees = DB::table('agency_employees')
                ->where('status', 'active')
                ->inRandomOrder()
                ->limit(50)
                ->get();

            foreach ($activeAgencyEmployees as $agencyEmployee) {
                $totalHours = rand(80, 180);
                $grossPay = round($totalHours * $agencyEmployee->pay_rate, 2);
                $taxes = round($grossPay * 0.2, 2);
                $netPay = $grossPay - $taxes;

                $payrolls[] = [
                    'agency_employee_id' => $agencyEmployee->id,
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
                    ->join('agency_employees', 'payrolls.agency_employee_id', '=', 'agency_employees.id')
                    ->where('agency_employees.agency_id', $agencyId)
                    ->where('payrolls.period_start', $periodStart->format('Y-m-d'))
                    ->sum('payrolls.gross_pay');

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
                                ->join('agency_employees', 'payrolls.agency_employee_id', '=', 'agency_employees.id')
                                ->where('agency_employees.agency_id', $agencyId)
                                ->where('payrolls.period_start', $periodStart->format('Y-m-d'))
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

    private function generateFirstName()
    {
        $names = ['James', 'Mary', 'John', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda', 'William', 'Elizabeth'];
        return $names[array_rand($names)];
    }

    private function generateLastName()
    {
        $names = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
        return $names[array_rand($names)];
    }
}
