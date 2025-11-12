<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ShiftPilot - Refined Platform Configuration
    |--------------------------------------------------------------------------
    |
    | CORE PRINCIPLE: Employees are ONLY linked to Employers through Agencies.
    | All assignments flow through the Agency-Employee relationship.
    |
    */

    'meta' => [
        'name' => 'ShiftPilot',
        'version' => '2.1.1', // Updated version
        'currency' => 'GBP',
        'default_timezone' => 'UTC+01:00',
        'audit_retention_days' => 365,
        'invoice_due_days' => 14,
    ],

    /*
    |--------------------------------------------------------------------------
    | ENTITIES - REFINED STRUCTURE
    |--------------------------------------------------------------------------
    */
    'entities' => [

        /*
        |--------------------------------------------------------------------------
        | User (base authentication)
        |--------------------------------------------------------------------------
        */
        'user' => [
            'table' => 'users',
            'fields' => [
                'id' => ['type' => 'increments'],
                'name' => ['type' => 'string', 'nullable' => false],
                'email' => ['type' => 'string', 'nullable' => false, 'unique' => true],
                'password' => ['type' => 'string', 'nullable' => false],
                'role' => ['type' => 'string', 'nullable' => false, 'default' => 'employee'],
                'phone' => ['type' => 'string', 'nullable' => true],
                'address_line1' => ['type' => 'string', 'nullable' => true],
                'address_line2' => ['type' => 'string', 'nullable' => true],
                'city' => ['type' => 'string', 'nullable' => true],
                'county' => ['type' => 'string', 'nullable' => true],
                'postcode' => ['type' => 'string', 'nullable' => true],
                'country' => ['type' => 'string', 'nullable' => true, 'default' => 'GB'],
                'latitude' => ['type' => 'decimal', 'precision' => 10, 'scale' => 8, 'nullable' => true],
                'longitude' => ['type' => 'decimal', 'precision' => 11, 'scale' => 8, 'nullable' => true],

                'status' => ['type' => 'string', 'nullable' => false, 'default' => 'active'],
                'meta' => ['type' => 'json', 'nullable' => true],
                'email_verified_at' => ['type' => 'timestamp', 'nullable' => true],
                'last_login_at' => ['type' => 'timestamp', 'nullable' => true],
                'created_at' => ['type' => 'timestamp', 'nullable' => true],
                'updated_at' => ['type' => 'timestamp', 'nullable' => true],
            ],
            'validation' => [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8',
                'role' => 'required|in:super_admin,agency_admin,agent,employer_admin,contact,employee',
                'status' => 'required|in:active,inactive,suspended',
                'address_line1' => 'nullable|string|max:255',
                'address_line2' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'county' => 'nullable|string|max:100',
                'postcode' => 'nullable|string|max:20',
                'country' => 'nullable|string|size:2',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
            ],
            'relationships' => [
                ['type' => 'morphOne', 'related' => 'Profile', 'name' => 'profile'],
            ],
            'indexes' => [
                ['fields' => ['postcode']],
                ['fields' => ['city']],
                ['fields' => ['country']],
                ['fields' => ['latitude', 'longitude']],
            ],
            'business_rules' => [
                'address_validation' => 'If any address field is provided, address_line1 and postcode are required',
                'geocoding' => 'Latitude/longitude should be automatically geocoded from address when possible',
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Agency (staffing company)
        |--------------------------------------------------------------------------
        */
        'agency' => [
            'table' => 'agencies',
            'fields' => [
                'id' => ['type' => 'increments'],
                'user_id' => ['type' => 'foreign', 'references' => 'users,id', 'nullable' => false],
                'name' => ['type' => 'string'],
                'legal_name' => ['type' => 'string', 'nullable' => true],
                'registration_number' => ['type' => 'string', 'nullable' => true],
                'billing_email' => ['type' => 'string', 'nullable' => true],
                'address' => ['type' => 'string', 'nullable' => true],
                'city' => ['type' => 'string', 'nullable' => true],
                'country' => ['type' => 'string', 'nullable' => true],
                'default_markup_percent' => ['type' => 'decimal', 'precision' => 5, 'scale' => 2, 'default' => 15.00],
                'subscription_status' => ['type' => 'string', 'default' => 'active'],
                'meta' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'user_id' => 'required|exists:users,id',
                'name' => 'required|string|max:255',
                'billing_email' => 'nullable|email',
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'User'],
                ['type' => 'hasMany', 'related' => 'Agent'],
                ['type' => 'hasMany', 'related' => 'AgencyEmployee'],
                ['type' => 'hasMany', 'related' => 'EmployerAgencyContract'],
                ['type' => 'hasMany', 'related' => 'Invoice'],
                ['type' => 'hasMany', 'related' => 'Payroll']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Agent (agency staff member)
        |--------------------------------------------------------------------------
        */
        'agent' => [
            'table' => 'agents',
            'fields' => [
                'id' => ['type' => 'increments'],
                'user_id' => ['type' => 'foreign', 'references' => 'users,id'],
                'agency_id' => ['type' => 'foreign', 'references' => 'agencies,id'],
                'name' => ['type' => 'string'],
                'email' => ['type' => 'string'],
                'phone' => ['type' => 'string', 'nullable' => true],
                'permissions' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'user_id' => 'required|exists:users,id',
                'agency_id' => 'required|exists:agencies,id',
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Agency'],
                ['type' => 'belongsTo', 'related' => 'User']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Employer (client company)
        |--------------------------------------------------------------------------
        */
        'employer' => [
            'table' => 'employers',
            'fields' => [
                'id' => ['type' => 'increments'],
                'name' => ['type' => 'string'],
                'legal_name' => ['type' => 'string', 'nullable' => true],
                'registration_number' => ['type' => 'string', 'nullable' => true],
                'billing_email' => ['type' => 'string', 'nullable' => true],
                'phone' => ['type' => 'string', 'nullable' => true],
                'website' => ['type' => 'string', 'nullable' => true],
                'address_line1' => ['type' => 'string', 'nullable' => true],
                'address_line2' => ['type' => 'string', 'nullable' => true],
                'city' => ['type' => 'string', 'nullable' => true],
                'county' => ['type' => 'string', 'nullable' => true],
                'postcode' => ['type' => 'string', 'nullable' => true],
                'country' => ['type' => 'string', 'nullable' => true, 'default' => 'GB'],
                'industry' => ['type' => 'string', 'nullable' => true],
                'company_size' => ['type' => 'string', 'nullable' => true],
                'status' => ['type' => 'string', 'default' => 'active'],
                'meta' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'name' => 'required|string|max:255',
                'billing_email' => 'nullable|email',
                'phone' => 'nullable|string|max:20',
                'website' => 'nullable|url',
                'status' => 'required|in:active,inactive,suspended'
            ],
            'relationships' => [
                ['type' => 'hasMany', 'related' => 'EmployerUser'],
                ['type' => 'hasMany', 'related' => 'Contact'],
                ['type' => 'hasMany', 'related' => 'Location'],
                ['type' => 'hasMany', 'related' => 'ShiftRequest'],
                ['type' => 'hasMany', 'related' => 'EmployerAgencyContract'],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Employer-Agency Contract
        | Establishes the business relationship and terms between employer and agency
        |--------------------------------------------------------------------------
        */
        'employer_agency_contract' => [
            'table' => 'employer_agency_contracts',
            'fields' => [
                'id' => ['type' => 'increments'],
                'employer_id' => ['type' => 'foreign', 'references' => 'employers,id'],
                'agency_id' => ['type' => 'foreign', 'references' => 'agencies,id'],
                'status' => ['type' => 'string', 'default' => 'pending'],
                'contract_document_url' => ['type' => 'string', 'nullable' => true],
                'contract_start' => ['type' => 'date', 'nullable' => true],
                'contract_end' => ['type' => 'date', 'nullable' => true],
                'terms' => ['type' => 'text', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'employer_id' => 'required|exists:employers,id',
                'agency_id' => 'required|exists:agencies,id',
                'status' => 'required|in:pending,active,suspended,terminated'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Employer'],
                ['type' => 'belongsTo', 'related' => 'Agency'],
                ['type' => 'hasMany', 'related' => 'Assignment']
            ],
            'indexes' => [
                ['fields' => ['employer_id', 'agency_id'], 'unique' => true]
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Contact (employer-side manager/approver)
        |--------------------------------------------------------------------------
        */
        'contact' => [
            'table' => 'contacts',
            'fields' => [
                'id' => ['type' => 'increments'],
                'employer_id' => ['type' => 'foreign', 'references' => 'employers,id'],
                'user_id' => ['type' => 'foreign', 'references' => 'users,id', 'nullable' => true],
                'name' => ['type' => 'string'],
                'email' => ['type' => 'string'],
                'phone' => ['type' => 'string', 'nullable' => true],
                'role' => ['type' => 'string', 'default' => 'manager'],
                'can_approve_timesheets' => ['type' => 'boolean', 'default' => true],
                'can_approve_assignments' => ['type' => 'boolean', 'default' => true],
                'meta' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'employer_id' => 'required|exists:employers,id',
                'user_id' => 'nullable|exists:users,id',
                'email' => 'required|email',
                'name' => 'required|string',
                'role' => 'sometimes|in:admin,manager,approver,viewer',
                'phone' => 'nullable|string|max:20'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Employer'],
                ['type' => 'belongsTo', 'related' => 'User', 'foreign_key' => 'user_id']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Location (employer work site)
        |--------------------------------------------------------------------------
        */
        'location' => [
            'table' => 'locations',
            'fields' => [
                'id' => ['type' => 'increments'],
                'employer_id' => ['type' => 'foreign', 'references' => 'employers,id'],
                'name' => ['type' => 'string'],
                'address_line1' => ['type' => 'string', 'nullable' => true],
                'address_line2' => ['type' => 'string', 'nullable' => true],
                'city' => ['type' => 'string', 'nullable' => true],
                'county' => ['type' => 'string', 'nullable' => true],
                'postcode' => ['type' => 'string', 'nullable' => true],
                'country' => ['type' => 'string', 'nullable' => true, 'default' => 'GB'],
                'latitude' => ['type' => 'decimal', 'precision' => 10, 'scale' => 8, 'nullable' => true],
                'longitude' => ['type' => 'decimal', 'precision' => 11, 'scale' => 8, 'nullable' => true],
                'location_type' => ['type' => 'string', 'nullable' => true],
                'contact_name' => ['type' => 'string', 'nullable' => true],
                'contact_phone' => ['type' => 'string', 'nullable' => true],
                'instructions' => ['type' => 'text', 'nullable' => true],

                'meta' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'employer_id' => 'required|exists:employers,id',
                'name' => 'required|string|max:255',
                'address_line1' => 'nullable|string|max:255',
                'address_line2' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'county' => 'nullable|string|max:100',
                'postcode' => 'nullable|string|max:20',
                'country' => 'nullable|string|size:2',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'location_type' => 'nullable|in:office,warehouse,retail,construction,manufacturing,healthcare,education,other',
                'contact_phone' => 'nullable|string|max:20'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Employer'],
                ['type' => 'hasMany', 'related' => 'ShiftRequest'],
                ['type' => 'hasMany', 'related' => 'Shift']
            ],
            'indexes' => [
                ['fields' => ['employer_id', 'name']],
                ['fields' => ['postcode']],
                ['fields' => ['city']],
                ['fields' => ['country']],
                ['fields' => ['latitude', 'longitude']],
                ['fields' => ['location_type']]
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Employee (worker - NO direct employer_id)
        |--------------------------------------------------------------------------
        */
        'employee' => [
            'table' => 'employees',
            'fields' => [
                'id' => ['type' => 'increments'],
                'user_id' => ['type' => 'foreign', 'references' => 'users,id'],
                'national_insurance_number' => ['type' => 'string', 'nullable' => true],
                'date_of_birth' => ['type' => 'date', 'nullable' => true],
                'emergency_contact_name' => ['type' => 'string', 'nullable' => true],
                'emergency_contact_phone' => ['type' => 'string', 'nullable' => true],
                'qualifications' => ['type' => 'json', 'nullable' => true],
                'certifications' => ['type' => 'json', 'nullable' => true],
                'status' => ['type' => 'string', 'default' => 'active'],
                'meta' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'user_id' => 'required|exists:users,id',
                'status' => 'required|in:active,inactive,suspended'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'User'],
                ['type' => 'hasMany', 'related' => 'AgencyEmployee'],
                ['type' => 'hasMany', 'related' => 'EmployeeAvailability'],
                ['type' => 'hasMany', 'related' => 'TimeOffRequest'],
                ['type' => 'hasMany', 'related' => 'Assignment'],
                ['type' => 'hasMany', 'related' => 'Shift'],
                ['type' => 'hasMany', 'related' => 'Timesheet']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Agency-Employee Registration
        | Links employees to agencies with agency-specific terms
        | An employee can be registered with multiple agencies
        |--------------------------------------------------------------------------
        */
        'agency_employee' => [
            'table' => 'agency_employees',
            'fields' => [
                'id' => ['type' => 'increments'],
                'agency_id' => ['type' => 'foreign', 'references' => 'agencies,id'],
                'employee_id' => ['type' => 'foreign', 'references' => 'employees,id'],
                'position' => ['type' => 'string', 'nullable' => true],
                'pay_rate' => ['type' => 'decimal', 'precision' => 8, 'scale' => 2],
                'employment_type' => ['type' => 'string', 'default' => 'temp'],
                'status' => ['type' => 'string', 'default' => 'active'],
                'contract_start_date' => ['type' => 'date', 'nullable' => true],
                'contract_end_date' => ['type' => 'date', 'nullable' => true],
                'specializations' => ['type' => 'json', 'nullable' => true],
                'preferred_locations' => ['type' => 'json', 'nullable' => true],
                'max_weekly_hours' => ['type' => 'integer', 'nullable' => true],
                'notes' => ['type' => 'text', 'nullable' => true],
                'meta' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'agency_id' => 'required|exists:agencies,id',
                'employee_id' => 'required|exists:employees,id',
                'pay_rate' => 'required|numeric|min:0',
                'employment_type' => 'required|in:temp,contract,temp_to_perm',
                'status' => 'required|in:active,inactive,suspended,terminated'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Agency'],
                ['type' => 'belongsTo', 'related' => 'Employee'],
                ['type' => 'hasMany', 'related' => 'Assignment']
            ],
            'indexes' => [
                ['fields' => ['agency_id', 'employee_id'], 'unique' => true],
                ['fields' => ['employee_id', 'status']],
                ['fields' => ['agency_id', 'status']]
            ],
            'business_rules' => [
                'unique_active_registration' => 'Only one active registration per agency-employee pair',
                'status_flow' => 'Can only move from active→inactive or active→suspended→terminated'
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Employee Availability
        |--------------------------------------------------------------------------
        */
        'employee_availability' => [
            'table' => 'employee_availabilities',
            'fields' => [
                'id' => ['type' => 'increments'],
                'employee_id' => ['type' => 'foreign', 'references' => 'employees,id'],
                'start_date' => ['type' => 'date'],
                'end_date' => ['type' => 'date', 'nullable' => true],
                'days_mask' => ['type' => 'integer'],
                'start_time' => ['type' => 'time'],
                'end_time' => ['type' => 'time'],
                'type' => ['type' => 'string', 'default' => 'preferred'],
                'priority' => ['type' => 'integer', 'default' => 1],
                'max_hours' => ['type' => 'integer', 'nullable' => true],
                'flexible' => ['type' => 'boolean', 'default' => false],
                'constraints' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'employee_id' => 'required|exists:employees,id',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'days_mask' => 'required|integer|min:1|max:127',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'type' => 'required|in:preferred,available,unavailable'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Employee']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Time Off Request
        |--------------------------------------------------------------------------
        */
        'time_off_request' => [
            'table' => 'time_off_requests',
            'fields' => [
                'id' => ['type' => 'increments'],
                'employee_id' => ['type' => 'foreign', 'references' => 'employees,id'],
                'agency_id' => ['type' => 'foreign', 'references' => 'agencies,id'],
                'start_date' => ['type' => 'date'],
                'end_date' => ['type' => 'date'],
                'type' => ['type' => 'string', 'default' => 'vacation'],
                'reason' => ['type' => 'text', 'nullable' => true],
                'status' => ['type' => 'string', 'default' => 'pending'],
                'approved_by_id' => ['type' => 'foreign', 'references' => 'users,id', 'nullable' => true],
                'approved_at' => ['type' => 'timestamp', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'employee_id' => 'required|exists:employees,id',
                'agency_id' => 'required|exists:agencies,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'type' => 'required|in:vacation,sick,personal,bereavement,other',
                'status' => 'required|in:pending,approved,rejected'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Employee'],
                ['type' => 'belongsTo', 'related' => 'Agency'],
                ['type' => 'belongsTo', 'related' => 'User', 'foreign_key' => 'approved_by_id']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Shift Request (from Employer)
        | Represents employer's staffing need
        | Agencies respond to these requests
        |--------------------------------------------------------------------------
        */
        'shift_request' => [
            'table' => 'shift_requests',
            'fields' => [
                'id' => ['type' => 'increments'],
                'employer_id' => ['type' => 'foreign', 'references' => 'employers,id'],
                'location_id' => ['type' => 'foreign', 'references' => 'locations,id'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'text', 'nullable' => true],
                'role' => ['type' => 'string'],
                'required_qualifications' => ['type' => 'json', 'nullable' => true],
                'experience_level' => ['type' => 'string', 'default' => 'entry'],
                'background_check_required' => ['type' => 'boolean', 'default' => false],
                'start_date' => ['type' => 'date'],
                'end_date' => ['type' => 'date', 'nullable' => true],
                'shift_pattern' => ['type' => 'string', 'default' => 'one_time'],
                'recurrence_rules' => ['type' => 'json', 'nullable' => true],
                'max_hourly_rate' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2],
                'currency' => ['type' => 'string', 'default' => 'GBP'],
                'number_of_workers' => ['type' => 'integer', 'default' => 1],
                'target_agencies' => ['type' => 'string', 'default' => 'all'],
                'specific_agency_ids' => ['type' => 'json', 'nullable' => true],
                'response_deadline' => ['type' => 'timestamp', 'nullable' => true],
                'status' => ['type' => 'string', 'default' => 'draft'],
                'created_by_id' => ['type' => 'foreign', 'references' => 'users,id'],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'employer_id' => 'required|exists:employers,id',
                'title' => 'required|string|max:255',
                'location_id' => 'required|exists:locations,id',
                'start_date' => 'required|date',
                'max_hourly_rate' => 'required|numeric|min:0',
                'status' => 'required|in:draft,published,in_progress,filled,cancelled,completed'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Employer'],
                ['type' => 'belongsTo', 'related' => 'Location'],
                ['type' => 'belongsTo', 'related' => 'User', 'foreign_key' => 'created_by_id'],
                ['type' => 'hasMany', 'related' => 'AgencyResponse'],
                ['type' => 'hasMany', 'related' => 'Assignment']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Agency Response (to Shift Request) - UPDATED
        | Agencies submit proposals for shift requests
        |--------------------------------------------------------------------------
        */
        'agency_response' => [
            'table' => 'agency_responses',
            'fields' => [
                'id' => ['type' => 'increments'],
                'shift_request_id' => ['type' => 'foreign', 'references' => 'shift_requests,id'],
                'agency_id' => ['type' => 'foreign', 'references' => 'agencies,id'],
                'proposed_employee_id' => ['type' => 'foreign', 'references' => 'employees,id', 'nullable' => true],

                'proposed_rate' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2],
                'proposed_start_date' => ['type' => 'date'],
                'proposed_end_date' => ['type' => 'date', 'nullable' => true],
                'terms' => ['type' => 'text', 'nullable' => true],
                'estimated_total_hours' => ['type' => 'integer', 'nullable' => true],

                'status' => ['type' => 'string', 'default' => 'pending'],
                'notes' => ['type' => 'text', 'nullable' => true],
                'submitted_by_id' => ['type' => 'foreign', 'references' => 'users,id'],
                'responded_at' => ['type' => 'timestamp', 'nullable' => true],

                'employer_decision_by_id' => ['type' => 'foreign', 'references' => 'users,id', 'nullable' => true],
                'employer_decision_at' => ['type' => 'timestamp', 'nullable' => true],

                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'shift_request_id' => 'required|exists:shift_requests,id',
                'agency_id' => 'required|exists:agencies,id',
                'proposed_rate' => 'required|numeric|min:0|lte:shift_request.max_hourly_rate',
                'proposed_start_date' => 'required|date|after_or_equal:today',
                'status' => 'required|in:pending,accepted,rejected,withdrawn,counter_offered'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'ShiftRequest'],
                ['type' => 'belongsTo', 'related' => 'Agency'],
                ['type' => 'belongsTo', 'related' => 'Employee', 'foreign_key' => 'proposed_employee_id'],
                ['type' => 'belongsTo', 'related' => 'User', 'foreign_key' => 'submitted_by_id'],
                ['type' => 'belongsTo', 'related' => 'User', 'foreign_key' => 'employer_decision_by_id'],
                ['type' => 'hasOne', 'related' => 'Assignment'] // ADDED RELATIONSHIP
            ],
            'indexes' => [
                ['fields' => ['shift_request_id', 'agency_id'], 'unique' => true],
                ['fields' => ['proposed_employee_id', 'status']] // ADDED INDEX
            ],
            'business_rules' => [
                'rate_validation' => 'proposed_rate cannot exceed shift_request.max_hourly_rate',
                'employee_availability' => 'proposed_employee must be available for proposed dates',
                'single_active_response' => 'Only one active response per agency per shift_request'
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Assignment (Active Employee-Employer Placement via Agency) - UPDATED
        | Tracks the actual assignment of an agency employee to an employer
        | This is created when an employer accepts an agency response
        |--------------------------------------------------------------------------
        */
        'assignment' => [
            'table' => 'assignments',
            'fields' => [
                'id' => ['type' => 'increments'],
                'contract_id' => ['type' => 'foreign', 'references' => 'employer_agency_contracts,id'],
                'agency_employee_id' => ['type' => 'foreign', 'references' => 'agency_employees,id'],

                // CHANGED FROM NULLABLE TO REQUIRED:
                'shift_request_id' => ['type' => 'foreign', 'references' => 'shift_requests,id', 'nullable' => false],
                'agency_response_id' => ['type' => 'foreign', 'references' => 'agency_responses,id', 'nullable' => false],

                'location_id' => ['type' => 'foreign', 'references' => 'locations,id'],
                'role' => ['type' => 'string'],
                'start_date' => ['type' => 'date'],
                'end_date' => ['type' => 'date', 'nullable' => true],
                'expected_hours_per_week' => ['type' => 'integer', 'nullable' => true],
                'agreed_rate' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2],
                'pay_rate' => ['type' => 'decimal', 'precision' => 8, 'scale' => 2],
                'markup_amount' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2],
                'markup_percent' => ['type' => 'decimal', 'precision' => 5, 'scale' => 2],
                'status' => ['type' => 'string', 'default' => 'active'],
                'assignment_type' => ['type' => 'string', 'default' => 'ongoing'],
                'shift_pattern' => ['type' => 'json', 'nullable' => true],
                'notes' => ['type' => 'text', 'nullable' => true],
                'created_by_id' => ['type' => 'foreign', 'references' => 'users,id'],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'contract_id' => 'required|exists:employer_agency_contracts,id',
                'agency_employee_id' => 'required|exists:agency_employees,id',
                'shift_request_id' => 'required|exists:shift_requests,id', // CHANGED TO REQUIRED
                'agency_response_id' => 'required|exists:agency_responses,id', // CHANGED TO REQUIRED
                'location_id' => 'required|exists:locations,id',
                'start_date' => 'required|date',
                'agreed_rate' => 'required|numeric|min:0|gte:pay_rate',
                'pay_rate' => 'required|numeric|min:0',
                'status' => 'required|in:active,pending,completed,cancelled,suspended'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'EmployerAgencyContract', 'foreign_key' => 'contract_id'],
                ['type' => 'belongsTo', 'related' => 'AgencyEmployee'],
                ['type' => 'belongsTo', 'related' => 'ShiftRequest'],
                ['type' => 'belongsTo', 'related' => 'AgencyResponse'],
                ['type' => 'belongsTo', 'related' => 'Location'],
                ['type' => 'belongsTo', 'related' => 'User', 'foreign_key' => 'created_by_id'],
                ['type' => 'hasMany', 'related' => 'Shift']
            ],
            'indexes' => [
                ['fields' => ['agency_employee_id', 'start_date', 'end_date']],
                ['fields' => ['contract_id', 'status']],
                ['fields' => ['agency_employee_id', 'status']],
                ['fields' => ['agency_response_id'], 'unique' => true] // ADDED UNIQUE CONSTRAINT
            ],
            'business_rules' => [
                'agency_response_validation' => 'Assignment requires accepted agency_response', // ADDED
                'rate_validation' => 'agreed_rate must be >= pay_rate',
                'markup_calculation' => 'markup_amount = agreed_rate - pay_rate, markup_percent = (markup_amount / pay_rate) * 100',
                'contract_active' => 'Assignment requires active employer_agency_contract',
                'rate_consistency' => 'agreed_rate should match agency_response.proposed_rate' // ADDED
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Shift (Actual work period within an assignment)
        | Always linked to an assignment
        | No direct employer/agency creation - must go through assignment
        |--------------------------------------------------------------------------
        */
        'shift' => [
            'table' => 'shifts',
            'fields' => [
                'id' => ['type' => 'increments'],
                'assignment_id' => ['type' => 'foreign', 'references' => 'assignments,id'],
                'location_id' => ['type' => 'foreign', 'references' => 'locations,id'],
                'shift_date' => ['type' => 'date'],
                'start_time' => ['type' => 'timestamp'],
                'end_time' => ['type' => 'timestamp'],
                'hourly_rate' => ['type' => 'decimal', 'precision' => 8, 'scale' => 2],
                'status' => ['type' => 'string', 'default' => 'scheduled'],
                'notes' => ['type' => 'text', 'nullable' => true],
                'meta' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'assignment_id' => 'required|exists:assignments,id',
                'location_id' => 'required|exists:locations,id',
                'start_time' => 'required|date',
                'end_time' => 'required|date|after:start_time',
                'status' => 'required|in:scheduled,in_progress,completed,cancelled,no_show'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Assignment'],
                ['type' => 'belongsTo', 'related' => 'Location'],
                ['type' => 'hasOne', 'related' => 'Timesheet'],
                ['type' => 'hasMany', 'related' => 'ShiftApproval']
            ],
            'indexes' => [
                ['fields' => ['assignment_id', 'start_time', 'end_time']],
                ['fields' => ['start_time', 'end_time']]
            ],
            'business_rules' => [
                'overlap_detection' => 'Must check for employee shift overlaps before creation',
                'availability_check' => 'Must validate against employee availability',
                'assignment_validation' => 'Must verify assignment is active'
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Shift Template (for recurring shift generation)
        |--------------------------------------------------------------------------
        */
        'shift_template' => [
            'table' => 'shift_templates',
            'fields' => [
                'id' => ['type' => 'increments'],
                'assignment_id' => ['type' => 'foreign', 'references' => 'assignments,id', 'on_delete' => 'cascade'],
                'title' => ['type' => 'string', 'nullable' => false],
                'description' => ['type' => 'text', 'nullable' => true],
                'day_of_week' => ['type' => 'string', 'nullable' => false],
                'start_time' => ['type' => 'time', 'nullable' => false],
                'end_time' => ['type' => 'time', 'nullable' => false],
                'recurrence_type' => ['type' => 'string', 'default' => 'weekly'],
                'timezone' => ['type' => 'string', 'default' => 'UTC'],
                'status' => ['type' => 'string', 'default' => 'active'],
                'effective_start_date' => ['type' => 'date', 'nullable' => true],
                'effective_end_date' => ['type' => 'date', 'nullable' => true],
                'last_generated_date' => ['type' => 'date', 'nullable' => true],
                'max_occurrences' => ['type' => 'integer', 'nullable' => true],
                'auto_publish' => ['type' => 'boolean', 'default' => false],
                'generation_count' => ['type' => 'integer', 'default' => 0],
                'meta' => ['type' => 'json', 'nullable' => true],
                'created_by_id' => ['type' => 'foreign', 'references' => 'users,id'],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'assignment_id' => 'required|exists:assignments,id',
                'title' => 'required|string|max:255',
                'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'recurrence_type' => 'required|in:weekly,biweekly,monthly',
                'timezone' => 'required|timezone',
                'status' => 'required|in:active,inactive,paused',
                'effective_start_date' => 'nullable|date|after_or_equal:today',
                'effective_end_date' => 'nullable|date|after_or_equal:effective_start_date',
                'max_occurrences' => 'nullable|integer|min:1|max:1000',
                'auto_publish' => 'boolean',
                'created_by_id' => 'required|exists:users,id'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Assignment'],
                ['type' => 'belongsTo', 'related' => 'User', 'foreign_key' => 'created_by_id'],
                ['type' => 'hasMany', 'related' => 'Shift']
            ],
            'indexes' => [
                ['fields' => ['assignment_id', 'status']],
                ['fields' => ['day_of_week']],
                ['fields' => ['effective_start_date', 'effective_end_date']],
                ['fields' => ['last_generated_date']],
                ['fields' => ['created_by_id']]
            ],
            'business_rules' => [
                'assignment_active' => 'Assignment must be active and within contract dates',
                'no_overlap' => 'Generated shifts must not overlap with existing shifts for the employee',
                'within_availability' => 'Generated shifts should respect employee availability patterns',
                'contract_valid' => 'Assignment contract must be valid during template effective dates',
                'generation_limit' => 'Stop generation when max_occurrences reached',
                'timezone_consistency' => 'All times must be handled in specified timezone'
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Shift Offer (agency offering shift to employee)
        | Links to assignment instead of individual shift
        |--------------------------------------------------------------------------
        */
        'shift_offer' => [
            'table' => 'shift_offers',
            'fields' => [
                'id' => ['type' => 'increments'],
                'shift_id' => ['type' => 'foreign', 'references' => 'shifts,id'],
                'agency_employee_id' => ['type' => 'foreign', 'references' => 'agency_employees,id'],
                'offered_by_id' => ['type' => 'foreign', 'references' => 'users,id'],
                'status' => ['type' => 'string', 'default' => 'pending'],
                'expires_at' => ['type' => 'timestamp'],
                'responded_at' => ['type' => 'timestamp', 'nullable' => true],
                'response_notes' => ['type' => 'text', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'shift_id' => 'required|exists:shifts,id',
                'agency_employee_id' => 'required|exists:agency_employees,id',
                'offered_by_id' => 'required|exists:users,id',
                'status' => 'required|in:pending,accepted,rejected,expired'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Shift'],
                ['type' => 'belongsTo', 'related' => 'AgencyEmployee'],
                ['type' => 'belongsTo', 'related' => 'User', 'foreign_key' => 'offered_by_id']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Shift Approval (employer contact sign-off)
        |--------------------------------------------------------------------------
        */
        'shift_approval' => [
            'table' => 'shift_approvals',
            'fields' => [
                'id' => ['type' => 'increments'],
                'shift_id' => ['type' => 'foreign', 'references' => 'shifts,id'],
                'contact_id' => ['type' => 'foreign', 'references' => 'contacts,id'],
                'status' => ['type' => 'string', 'default' => 'pending'],
                'signed_at' => ['type' => 'timestamp', 'nullable' => true],
                'signature_blob_url' => ['type' => 'string', 'nullable' => true],
                'notes' => ['type' => 'text', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'shift_id' => 'required|exists:shifts,id',
                'contact_id' => 'required|exists:contacts,id',
                'status' => 'required|in:pending,approved,rejected'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Shift'],
                ['type' => 'belongsTo', 'related' => 'Contact']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Timesheet (hours worked)
        |--------------------------------------------------------------------------
        */
        'timesheet' => [
            'table' => 'timesheets',
            'fields' => [
                'id' => ['type' => 'increments'],
                'shift_id' => ['type' => 'foreign', 'references' => 'shifts,id'],
                'clock_in' => ['type' => 'timestamp', 'nullable' => true],
                'clock_out' => ['type' => 'timestamp', 'nullable' => true],
                'break_minutes' => ['type' => 'integer', 'default' => 0],
                'hours_worked' => ['type' => 'decimal', 'precision' => 8, 'scale' => 2, 'nullable' => true],
                'status' => ['type' => 'string', 'default' => 'pending'],
                'agency_approved_by_id' => ['type' => 'foreign', 'references' => 'users,id', 'nullable' => true],
                'agency_approved_at' => ['type' => 'timestamp', 'nullable' => true],
                'employer_approved_by_id' => ['type' => 'foreign', 'references' => 'contacts,id', 'nullable' => true],
                'employer_approved_at' => ['type' => 'timestamp', 'nullable' => true],
                'notes' => ['type' => 'text', 'nullable' => true],
                'attachments' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'shift_id' => 'required|exists:shifts,id',
                'status' => 'required|in:pending,agency_approved,employer_approved,disputed,rejected'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Shift'],
                ['type' => 'belongsTo', 'related' => 'User', 'foreign_key' => 'agency_approved_by_id'],
                ['type' => 'belongsTo', 'related' => 'Contact', 'foreign_key' => 'employer_approved_by_id']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Payroll (agency to employee payment)
        |--------------------------------------------------------------------------
        */
        'payroll' => [
            'table' => 'payrolls',
            'fields' => [
                'id' => ['type' => 'increments'],
                'agency_employee_id' => ['type' => 'foreign', 'references' => 'agency_employees,id'],
                'period_start' => ['type' => 'date'],
                'period_end' => ['type' => 'date'],
                'total_hours' => ['type' => 'decimal', 'precision' => 8, 'scale' => 2],
                'gross_pay' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2],
                'taxes' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2, 'default' => 0.00],
                'deductions' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2, 'default' => 0.00],
                'net_pay' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2],
                'status' => ['type' => 'string', 'default' => 'pending'],
                'paid_at' => ['type' => 'timestamp', 'nullable' => true],
                'payout_id' => ['type' => 'foreign', 'references' => 'payouts,id', 'nullable' => true],
                'payment_reference' => ['type' => 'string', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'agency_employee_id' => 'required|exists:agency_employees,id',
                'period_start' => 'required|date',
                'period_end' => 'required|date|after:period_start',
                'status' => 'required|in:pending,processing,paid,failed'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'AgencyEmployee'],
                ['type' => 'belongsTo', 'related' => 'Payout', 'foreign_key' => 'payout_id']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Invoice (financial transactions)
        | Types: employer_to_agency, agency_to_platform, employer_to_platform
        |--------------------------------------------------------------------------
        */
        'invoice' => [
            'table' => 'invoices',
            'fields' => [
                'id' => ['type' => 'increments'],
                'type' => ['type' => 'string'],
                'from_type' => ['type' => 'string'],
                'from_id' => ['type' => 'integer'],
                'to_type' => ['type' => 'string'],
                'to_id' => ['type' => 'integer'],
                'reference' => ['type' => 'string', 'nullable' => false],
                'line_items' => ['type' => 'json', 'nullable' => true],
                'subtotal' => ['type' => 'decimal', 'precision' => 12, 'scale' => 2],
                'tax_amount' => ['type' => 'decimal', 'precision' => 12, 'scale' => 2, 'default' => 0.00],
                'total_amount' => ['type' => 'decimal', 'precision' => 12, 'scale' => 2],
                'status' => ['type' => 'string', 'default' => 'pending'],
                'due_date' => ['type' => 'date'],
                'paid_at' => ['type' => 'timestamp', 'nullable' => true],
                'payment_reference' => ['type' => 'string', 'nullable' => true],
                'metadata' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'type' => 'required|in:employer_to_agency,agency_to_platform,employer_to_platform',
                'from_type' => 'required|string',
                'to_type' => 'required|string',
                'subtotal' => 'required|numeric|min:0',
                'total_amount' => 'required|numeric|min:0',
                'due_date' => 'required|date',
                'status' => 'required|in:pending,paid,partial,overdue,cancelled'
            ],
            'relationships' => [
                ['type' => 'morphTo', 'name' => 'from'],
                ['type' => 'morphTo', 'name' => 'to'],
                ['type' => 'hasMany', 'related' => 'Payment']
            ],
            'indexes' => [
                ['fields' => ['reference'], 'unique' => true],
                ['fields' => ['from_type', 'from_id']],
                ['fields' => ['to_type', 'to_id']],
                ['fields' => ['status', 'due_date']]
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Payment (payment records)
        |--------------------------------------------------------------------------
        */
        'payment' => [
            'table' => 'payments',
            'fields' => [
                'id' => ['type' => 'increments'],
                'invoice_id' => ['type' => 'foreign', 'references' => 'invoices,id'],
                'payer_type' => ['type' => 'string'],
                'payer_id' => ['type' => 'integer'],
                'amount' => ['type' => 'decimal', 'precision' => 12, 'scale' => 2],
                'method' => ['type' => 'string'],
                'processor_id' => ['type' => 'string', 'nullable' => true],
                'status' => ['type' => 'string', 'default' => 'completed'],
                'fee_amount' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2, 'default' => 0.00],
                'net_amount' => ['type' => 'decimal', 'precision' => 12, 'scale' => 2],
                'metadata' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'invoice_id' => 'required|exists:invoices,id',
                'amount' => 'required|numeric|min:0',
                'method' => 'required|in:stripe,bacs,sepa,paypal,bank_transfer',
                'status' => 'required|in:pending,completed,failed,refunded'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Invoice'],
                ['type' => 'morphTo', 'name' => 'payer']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Payout (batch payroll processing)
        |--------------------------------------------------------------------------
        */
        'payout' => [
            'table' => 'payouts',
            'fields' => [
                'id' => ['type' => 'increments'],
                'agency_id' => ['type' => 'foreign', 'references' => 'agencies,id'],
                'period_start' => ['type' => 'date'],
                'period_end' => ['type' => 'date'],
                'total_amount' => ['type' => 'decimal', 'precision' => 12, 'scale' => 2],
                'employee_count' => ['type' => 'integer'],
                'status' => ['type' => 'string', 'default' => 'processing'],
                'provider_payout_id' => ['type' => 'string', 'nullable' => true],
                'metadata' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'agency_id' => 'required|exists:agencies,id',
                'period_start' => 'required|date',
                'period_end' => 'required|date|after:period_start',
                'status' => 'required|in:processing,paid,failed,cancelled'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Agency'],
                ['type' => 'hasMany', 'related' => 'Payroll']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Subscription (platform subscriptions)
        |--------------------------------------------------------------------------
        */
        'subscription' => [
            'table' => 'subscriptions',
            'fields' => [
                'id' => ['type' => 'increments'],
                'entity_type' => ['type' => 'string'],
                'entity_id' => ['type' => 'integer'],
                'plan_key' => ['type' => 'string'],
                'plan_name' => ['type' => 'string'],
                'amount' => ['type' => 'decimal', 'precision' => 8, 'scale' => 2],
                'interval' => ['type' => 'string', 'default' => 'monthly'],
                'status' => ['type' => 'string', 'default' => 'active'],
                'started_at' => ['type' => 'timestamp'],
                'current_period_start' => ['type' => 'timestamp', 'nullable' => true],
                'current_period_end' => ['type' => 'timestamp', 'nullable' => true],
                'meta' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'entity_type' => 'required|in:agency,employer',
                'entity_id' => 'required|integer',
                'plan_key' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'status' => 'required|in:active,past_due,cancelled,suspended'
            ],
            'relationships' => [
                ['type' => 'morphTo', 'name' => 'subscriber']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Rate Card (pricing rules)
        |--------------------------------------------------------------------------
        */
        'rate_card' => [
            'table' => 'rate_cards',
            'fields' => [
                'id' => ['type' => 'increments'],
                'employer_id' => ['type' => 'foreign', 'references' => 'employers,id', 'nullable' => true],
                'agency_id' => ['type' => 'foreign', 'references' => 'agencies,id', 'nullable' => true],
                'role_key' => ['type' => 'string'],
                'location_id' => ['type' => 'foreign', 'references' => 'locations,id', 'nullable' => true],
                'day_of_week' => ['type' => 'string', 'nullable' => true],
                'start_time' => ['type' => 'time', 'nullable' => true],
                'end_time' => ['type' => 'time', 'nullable' => true],
                'rate' => ['type' => 'decimal', 'precision' => 8, 'scale' => 2],
                'currency' => ['type' => 'string', 'default' => 'GBP'],
                'effective_from' => ['type' => 'date', 'nullable' => true],
                'effective_to' => ['type' => 'date', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'role_key' => 'required|string',
                'rate' => 'required|numeric|min:0'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Employer'],
                ['type' => 'belongsTo', 'related' => 'Agency'],
                ['type' => 'belongsTo', 'related' => 'Location']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        */
        'audit_log' => [
            'table' => 'audit_logs',
            'fields' => [
                'id' => ['type' => 'increments'],
                'actor_type' => ['type' => 'string'],
                'actor_id' => ['type' => 'integer', 'nullable' => true],
                'action' => ['type' => 'string'],
                'target_type' => ['type' => 'string', 'nullable' => true],
                'target_id' => ['type' => 'integer', 'nullable' => true],
                'payload' => ['type' => 'json', 'nullable' => true],
                'ip_address' => ['type' => 'string', 'nullable' => true],
                'user_agent' => ['type' => 'string', 'nullable' => true],
                'created_at' => ['type' => 'timestamp']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Notification
        |--------------------------------------------------------------------------
        */
        'system_notification' => [
            'table' => 'system_notifications',
            'fields' => [
                'id' => ['type' => 'increments'],
                'recipient_type' => ['type' => 'string'],
                'recipient_id' => ['type' => 'integer'],
                'channel' => ['type' => 'string', 'default' => 'in_app'],
                'template_key' => ['type' => 'string'],
                'payload' => ['type' => 'json', 'nullable' => true],
                'is_read' => ['type' => 'boolean', 'default' => false],
                'sent_at' => ['type' => 'timestamp', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Webhook Subscription
        |--------------------------------------------------------------------------
        */
        'webhook_subscription' => [
            'table' => 'webhook_subscriptions',
            'fields' => [
                'id' => ['type' => 'increments'],
                'owner_type' => ['type' => 'string'],
                'owner_id' => ['type' => 'integer'],
                'url' => ['type' => 'string'],
                'events' => ['type' => 'json'],
                'secret' => ['type' => 'string'],
                'status' => ['type' => 'string', 'default' => 'active'],
                'last_delivery_at' => ['type' => 'timestamp', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | BUSINESS RULES & CONSTRAINTS - UPDATED
    |--------------------------------------------------------------------------
    */
    'business_rules' => [
        'core_relationship_model' => [
            'rule' => 'ALL employee-employer relationships MUST be mediated through agency_employees → assignments',
            'enforcement' => 'Database constraints + application validation',
            'description' => 'No direct employee-employer relationships allowed'
        ],
        'assignment_prerequisites' => [
            'rule' => 'Assignment requires: active EmployerAgencyContract + active AgencyEmployee + valid Location + accepted AgencyResponse',
            'enforcement' => 'Foreign key constraints + application validation',
            'description' => 'Cannot assign employee without valid contract, agency registration, and accepted response'
        ],
        'assignment_creation_flow' => [ // ADDED
            'rule' => 'Assignment must be created from accepted AgencyResponse with matching terms',
            'enforcement' => 'Application validation + database constraints',
            'description' => 'Prevents orphaned assignments without proper agency response context'
        ],
        'shift_to_assignment_link' => [ // ADDED
            'rule' => 'All shifts must belong to an active assignment',
            'enforcement' => 'Foreign key constraint + application validation',
            'description' => 'Ensures shifts have proper assignment context'
        ],
        'employee_availability_scope' => [
            'rule' => 'Employee availability and time-off apply across ALL agencies',
            'enforcement' => 'Global validation checks',
            'description' => 'Prevents double-booking regardless of agency'
        ],
        'shift_overlap_prevention' => [
            'rule' => 'Employee cannot have overlapping shifts across ALL assignments and agencies',
            'enforcement' => 'Application validation before shift creation',
            'query' => 'Check shifts across all agency_employee relationships',
            'description' => 'Global overlap prevention for employee time'
        ],
        'assignment_overlap_prevention' => [
            'rule' => 'Employee cannot have overlapping active assignments',
            'enforcement' => 'Application validation before assignment creation',
            'description' => 'Prevents conflicting long-term placements across agencies'
        ],
        'multi_agency_employee_management' => [
            'rule' => 'Employee can have multiple active agency relationships with independent terms',
            'enforcement' => 'Application logic',
            'description' => 'Support flexible staffing across multiple agencies'
        ],
        'rate_integrity' => [
            'rule' => 'Assignment agreed_rate >= pay_rate, markup calculated automatically, agreed_rate should match agency_response.proposed_rate',
            'enforcement' => 'Application validation + database triggers',
            'description' => 'Ensures proper financial structure and consistency with agency response'
        ],
        'agency_response_integrity' => [ // ADDED
            'rule' => 'Agency response proposed_rate cannot exceed shift_request.max_hourly_rate',
            'enforcement' => 'Application validation',
            'description' => 'Maintains pricing boundaries set by employer'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | DATA FLOW: Employer → Assignment → Shift - UPDATED
    |--------------------------------------------------------------------------
    */
    'data_flow' => [
        'employee_registration' => [
            '1' => 'Employee creates base profile (no employer context)',
            '2' => 'Agency registers employee via agency_employees with agency-specific terms',
            '3' => 'Employee can be registered with multiple agencies simultaneously',
            '4' => 'Each agency-employee relationship has independent pay rates and terms'
        ],

        'assignment_creation' => [ // UPDATED
            '1' => 'Employer creates ShiftRequest for staffing need',
            '2' => 'Agencies with active contracts can view and respond',
            '3' => 'Agency submits AgencyResponse with proposed employee, rates, and dates',
            '4' => 'Employer reviews and accepts preferred AgencyResponse',
            '5' => 'System validates: active contract + available employee + no conflicts',
            '6' => 'System creates Assignment linking AgencyResponse to AgencyEmployee via Contract',
            '7' => 'Shifts generated within assignment date boundaries',
            '8' => 'Assignment terms (rates, dates) inherited from accepted AgencyResponse'
        ],

        'multi_agency_scenario' => [
            'employee_john' => [
                'registered_with' => ['Agency A', 'Agency B'],
                'assignment_agency_a' => 'Employer X (Mon-Wed)',
                'assignment_agency_b' => 'Employer Y (Thu-Fri)',
                'validation' => 'System prevents overlapping shifts across both assignments'
            ]
        ],

        'financial_flow' => [
            '1' => 'Employee works shifts, timesheets created',
            '2' => 'Timesheets approved by employer contact',
            '3' => 'Invoice generated (Employer → Agency) for agreed_rate × hours',
            '4' => 'Employer pays invoice to agency',
            '5' => 'Agency processes payroll (Agency → Employee) for pay_rate × hours',
            '6' => 'Platform commission invoiced (Agency → Platform)',
            '7' => 'Each agency manages payroll independently for their employees'
        ]
    ],

    // ... rest of the configuration remains the same (events, flows, roles, etc.)
    // Only the entities and business rules sections were updated

    /*
    |--------------------------------------------------------------------------
    | VALIDATION HELPERS
    |--------------------------------------------------------------------------
    */
    'validation_queries' => [
        'check_global_shift_overlap' => "
            SELECT COUNT(*) FROM shifts s
            JOIN assignments a ON s.assignment_id = a.id
            JOIN agency_employees ae ON a.agency_employee_id = ae.id
            WHERE ae.employee_id = :employee_id
            AND s.id != :exclude_shift_id
            AND s.start_time < :new_end_time
            AND s.end_time > :new_start_time
            AND s.status NOT IN ('cancelled', 'no_show')
        ",

        'check_global_assignment_overlap' => "
            SELECT COUNT(*) FROM assignments a
            JOIN agency_employees ae ON a.agency_employee_id = ae.id
            WHERE ae.employee_id = :employee_id
            AND a.id != :exclude_assignment_id
            AND a.status = 'active'
            AND a.start_date <= COALESCE(:new_end_date, '9999-12-31')
            AND (a.end_date IS NULL OR a.end_date >= :new_start_date)
        ",

        'get_employee_agency_context' => "
            SELECT
                ae.id as agency_employee_id,
                ae.agency_id,
                a.name as agency_name,
                ae.pay_rate,
                ae.status as agency_status,
                ae.employment_type
            FROM agency_employees ae
            JOIN agencies a ON ae.agency_id = a.id
            WHERE ae.employee_id = :employee_id
            AND ae.status = 'active'
        ",

        'get_available_employees_for_shift_global' => "
            SELECT DISTINCT
                e.*,
                ae.id as agency_employee_id,
                ae.agency_id,
                ae.pay_rate as agency_pay_rate
            FROM employees e
            JOIN agency_employees ae ON e.id = ae.employee_id
            WHERE ae.agency_id = :agency_id
            AND ae.status = 'active'
            AND e.status = 'active'
            AND e.id NOT IN (
                -- Exclude employees with overlapping shifts (any agency)
                SELECT ae2.employee_id
                FROM agency_employees ae2
                JOIN assignments a ON ae2.id = a.agency_employee_id
                JOIN shifts s ON a.id = s.assignment_id
                WHERE s.start_time < :shift_end_time
                AND s.end_time > :shift_start_time
                AND s.status NOT IN ('cancelled', 'no_show')
            )
            AND e.id NOT IN (
                -- Exclude employees with approved time off
                SELECT employee_id FROM time_off_requests
                WHERE status = 'approved'
                AND start_date <= :shift_end_date
                AND end_date >= :shift_start_date
            )
            AND e.id NOT IN (
                -- Exclude employees who have declined this shift previously
                SELECT so.agency_employee_id
                FROM shift_offers so
                JOIN shifts s ON so.shift_id = s.id
                WHERE so.agency_employee_id = ae.id
                AND s.assignment_id = :assignment_id
                AND so.status = 'rejected'
            )
        ",

        'calculate_employee_utilization' => "
            SELECT
                e.id as employee_id,
                ae.agency_id,
                COUNT(DISTINCT a.id) as active_assignments,
                COUNT(DISTINCT s.id) as scheduled_shifts,
                SUM(EXTRACT(EPOCH FROM (s.end_time - s.start_time))/3600 as scheduled_hours
            FROM employees e
            JOIN agency_employees ae ON e.id = ae.employee_id
            LEFT JOIN assignments a ON ae.id = a.agency_employee_id AND a.status = 'active'
            LEFT JOIN shifts s ON a.id = s.assignment_id
                AND s.status IN ('scheduled', 'in_progress')
                AND s.start_time >= :period_start
                AND s.end_time <= :period_end
            WHERE e.id = :employee_id
            GROUP BY e.id, ae.agency_id
        "
    ],

    /*
    |--------------------------------------------------------------------------
    | REFINED EVENTS
    |--------------------------------------------------------------------------
    */
    'events' => [
        'shift_request.created' => ['description' => 'Employer created shift request'],
        'shift_request.published' => ['description' => 'Shift request made available to agencies'],
        'agency_response.submitted' => ['description' => 'Agency submitted proposal for shift request'],
        'agency_response.accepted' => ['description' => 'Employer accepted agency proposal'],
        'agency_response.rejected' => ['description' => 'Employer rejected agency proposal'],
        'assignment.created' => ['description' => 'Employee assigned to employer via agency'],
        'assignment.completed' => ['description' => 'Assignment period ended'],
        'assignment.cancelled' => ['description' => 'Assignment cancelled'],
        'shift.created' => ['description' => 'Shift scheduled for assignment'],
        'shift.completed' => ['description' => 'Employee completed shift'],
        'shift.cancelled' => ['description' => 'Shift cancelled'],
        'shift_offer.sent' => ['description' => 'Agency offered shift to employee'],
        'shift_offer.accepted' => ['description' => 'Employee accepted shift offer'],
        'shift_offer.rejected' => ['description' => 'Employee rejected shift offer'],
        'timesheet.submitted' => ['description' => 'Timesheet submitted'],
        'timesheet.agency_approved' => ['description' => 'Agency approved timesheet'],
        'timesheet.employer_approved' => ['description' => 'Employer approved timesheet'],
        'invoice.generated' => ['description' => 'Invoice created'],
        'invoice.paid' => ['description' => 'Invoice payment received'],
        'payroll.generated' => ['description' => 'Payroll records created'],
        'payout.processed' => ['description' => 'Payout batch processed'],
        'availability.updated' => ['description' => 'Employee availability updated'],
        'time_off.requested' => ['description' => 'Employee requested time off'],
        'time_off.approved' => ['description' => 'Time off request approved'],
        'time_off.rejected' => ['description' => 'Time off request rejected'],
    ],

    /*
    |--------------------------------------------------------------------------
    | REFINED FLOWS
    |--------------------------------------------------------------------------
    */
    'flows' => [
        /*
        |--------------------------------------------------------------------------
        | Shift Request → Assignment → Shift Lifecycle
        |--------------------------------------------------------------------------
        */
        'full_shift_lifecycle' => [
            [
                'step' => 'employer_creates_shift_request',
                'actor' => 'employer_admin',
                'emit' => 'shift_request.created',
                'jobs' => ['ValidateShiftRequest', 'IdentifyEligibleAgencies'],
                'notifications' => []
            ],
            [
                'step' => 'employer_publishes_request',
                'actor' => 'employer_admin',
                'emit' => 'shift_request.published',
                'jobs' => ['NotifyAgencies', 'SetResponseDeadline'],
                'notifications' => ['shift_request.published:agency']
            ],
            [
                'step' => 'agency_submits_response',
                'actor' => 'agent',
                'emit' => 'agency_response.submitted',
                'jobs' => ['ValidateProposedEmployee', 'CalculateMarkup', 'CheckAvailability'],
                'notifications' => ['agency_response.submitted:employer']
            ],
            [
                'step' => 'employer_accepts_response',
                'actor' => 'employer_admin',
                'emit' => 'agency_response.accepted',
                'jobs' => ['CreateAssignment', 'GenerateShifts', 'NotifyAgency'],
                'notifications' => ['agency_response.accepted:agency', 'assignment.created:employee']
            ],
            [
                'step' => 'employee_works_shift',
                'actor' => 'employee',
                'emit' => 'shift.completed',
                'jobs' => ['CreateTimesheet', 'CalculateHours'],
                'notifications' => ['timesheet.submitted:agency']
            ],
            [
                'step' => 'agency_approves_timesheet',
                'actor' => 'agent',
                'emit' => 'timesheet.agency_approved',
                'jobs' => ['ValidateHours', 'NotifyEmployer'],
                'notifications' => ['timesheet.agency_approved:employer']
            ],
            [
                'step' => 'employer_approves_timesheet',
                'actor' => 'contact',
                'emit' => 'timesheet.employer_approved',
                'jobs' => ['FinalizeTimesheet', 'GenerateInvoice'],
                'notifications' => ['timesheet.employer_approved:agency', 'invoice.generated:employer']
            ],
            [
                'step' => 'employer_pays_invoice',
                'actor' => 'employer_admin',
                'emit' => 'invoice.paid',
                'jobs' => ['RecordPayment', 'CalculatePlatformFee', 'TriggerPayroll'],
                'notifications' => ['invoice.paid:agency']
            ],
            [
                'step' => 'agency_processes_payroll',
                'actor' => 'system',
                'emit' => 'payroll.generated',
                'jobs' => ['CreatePayrollRecords', 'CalculateTaxes'],
                'notifications' => ['payroll.generated:employee']
            ],
            [
                'step' => 'agency_executes_payout',
                'actor' => 'system',
                'emit' => 'payout.processed',
                'jobs' => ['BatchPayments', 'UpdatePayrollStatus'],
                'notifications' => ['payout.processed:employee']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Employee Multi-Agency Registration
        |--------------------------------------------------------------------------
        */
        'employee_multi_agency' => [
            [
                'step' => 'employee_registers_with_agency_a',
                'actor' => 'agency_admin',
                'emit' => null,
                'jobs' => ['CreateAgencyEmployee', 'ValidateCredentials'],
                'notifications' => ['registration.complete:employee']
            ],
            [
                'step' => 'employee_registers_with_agency_b',
                'actor' => 'agency_admin',
                'emit' => null,
                'jobs' => ['CreateAgencyEmployee', 'ValidateCredentials', 'CheckExistingRegistrations'],
                'notifications' => ['registration.complete:employee']
            ],
            [
                'step' => 'agency_a_assigns_to_employer_x',
                'actor' => 'agent',
                'emit' => 'assignment.created',
                'jobs' => ['CreateAssignment', 'ValidateNoConflicts'],
                'notifications' => ['assignment.created:employee', 'assignment.created:employer']
            ],
            [
                'step' => 'agency_b_attempts_assign_to_employer_y',
                'actor' => 'agent',
                'emit' => null,
                'jobs' => ['ValidateNoOverlap', 'CreateAssignment'],
                'notifications' => ['assignment.created:employee']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Availability and Time Off Management
        |--------------------------------------------------------------------------
        */
        'availability_management' => [
            [
                'step' => 'employee_sets_availability',
                'actor' => 'employee',
                'emit' => 'availability.updated',
                'jobs' => ['ValidateAvailability', 'UpdateEmployeeCalendar'],
                'notifications' => ['availability.updated:agency']
            ],
            [
                'step' => 'employee_requests_time_off',
                'actor' => 'employee',
                'emit' => 'time_off.requested',
                'jobs' => ['CheckShiftConflicts', 'NotifyRelevantAgencies'],
                'notifications' => ['time_off.requested:agency']
            ],
            [
                'step' => 'agency_reviews_time_off',
                'actor' => 'agency_admin',
                'emit' => 'time_off.approved',
                'jobs' => ['ApproveTimeOff', 'BlockDates', 'CheckAffectedShifts'],
                'notifications' => ['time_off.approved:employee', 'time_off.impact:employer']
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Conflict Prevention
        |--------------------------------------------------------------------------
        */
        'shift_validation' => [
            [
                'step' => 'validate_before_shift_creation',
                'actor' => 'system',
                'emit' => null,
                'jobs' => [
                    'CheckEmployeeShiftOverlap',
                    'CheckTimeOffConflict',
                    'CheckAssignmentActive',
                    'ValidateAvailability'
                ],
                'notifications' => []
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ROLES & PERMISSIONS (Updated)
    |--------------------------------------------------------------------------
    */
    'user_roles' => [
        'super_admin' => [
            'label' => 'Super Admin',
            'description' => 'Full platform access',
            'permissions' => ['*']
        ],
        'agency_admin' => [
            'label' => 'Agency Admin',
            'description' => 'Manage agency operations, employees, assignments',
            'permissions' => [
                'agency_employee:*',
                'agent:*',
                'assignment:*',
                'shift:view,create,update',
                'timesheet:*',
                'invoice:view,create',
                'payroll:*',
                'payout:*',
                'agency_response:*',
                'availability:view',
                'time_off:approve',
            ]
        ],
        'agent' => [
            'label' => 'Agent',
            'description' => 'Manage assignments and shifts for agency',
            'permissions' => [
                'agency_employee:view',
                'assignment:view,create,update',
                'shift:view,create,update',
                'shift_offer:*',
                'agency_response:create,view',
                'timesheet:view,approve',
            ]
        ],
        'employer_admin' => [
            'label' => 'Employer Admin',
            'description' => 'Manage shift requests, approve timesheets, pay invoices',
            'permissions' => [
                'shift_request:*',
                'agency_response:view',
                'assignment:view',
                'shift:view',
                'contact:*',
                'location:*',
                'timesheet:approve,view',
                'invoice:view,pay',
            ]
        ],
        'contact' => [
            'label' => 'Employer Contact',
            'description' => 'Approve timesheets and shifts on-site',
            'permissions' => [
                'timesheet:approve,view',
                'shift:approve,view',
                'shift_approval:create',
            ]
        ],
        'employee' => [
            'label' => 'Employee',
            'description' => 'View assignments, work shifts, manage availability',
            'permissions' => [
                'assignment:view:own',
                'shift:view:own',
                'shift_offer:respond:own',
                'timesheet:create:own,view:own',
                'availability:manage:own',
                'time_off:request',
                'agency_employee:view:own',
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SCHEDULING CONFIGURATION
    |--------------------------------------------------------------------------
    */
    'scheduling' => [
        'overlap_prevention' => [
            'enabled' => true,
            'check_across_all_agencies' => true,
            'check_time_off_requests' => true,
            'buffer_minutes' => 0, // No buffer, strict overlap prevention
        ],
        'availability' => [
            'default_timezone' => 'UTC',
            'min_shift_notice_hours' => 24,
            'max_shift_length_hours' => 12,
            'min_shift_length_hours' => 2,
            'break_required_after_hours' => 6,
            'break_minutes' => 30,
            'max_consecutive_days' => 7,
            'min_rest_hours_between_shifts' => 11,
        ],
        'matching' => [
            'consider_availability' => true,
            'consider_qualifications' => true,
            'consider_location_preference' => true,
            'max_travel_distance_km' => 50,
            'prefer_employees_with_history' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION TEMPLATES
    |--------------------------------------------------------------------------
    */
    'notification_templates' => [
        'shift_request.published:agency' => ['channels' => ['email', 'in_app']],
        'agency_response.submitted:employer' => ['channels' => ['email', 'in_app']],
        'agency_response.accepted:agency' => ['channels' => ['email', 'in_app']],
        'assignment.created:employee' => ['channels' => ['sms', 'email', 'in_app']],
        'assignment.created:employer' => ['channels' => ['email', 'in_app']],
        'shift.created:employee' => ['channels' => ['sms', 'email', 'in_app']],
        'shift_offer.sent:employee' => ['channels' => ['sms', 'in_app']],
        'timesheet.submitted:agency' => ['channels' => ['email', 'in_app']],
        'timesheet.agency_approved:employer' => ['channels' => ['email', 'in_app']],
        'timesheet.employer_approved:agency' => ['channels' => ['email', 'in_app']],
        'invoice.generated:employer' => ['channels' => ['email']],
        'invoice.paid:agency' => ['channels' => ['email']],
        'payroll.generated:employee' => ['channels' => ['email', 'in_app']],
        'payout.processed:employee' => ['channels' => ['email', 'in_app']],
        'availability.updated:agency' => ['channels' => ['in_app']],
        'time_off.requested:agency' => ['channels' => ['email', 'in_app']],
        'time_off.approved:employee' => ['channels' => ['email', 'in_app']],
        'time_off.impact:employer' => ['channels' => ['email', 'in_app']],
    ],

    /*
    |--------------------------------------------------------------------------
    | PAYMENT CONFIGURATION
    |--------------------------------------------------------------------------
    */
    'payments' => [
        'providers' => [
            'stripe' => ['key' => env('STRIPE_KEY'), 'secret' => env('STRIPE_SECRET'), 'connect' => true],
            'gocardless' => ['key' => env('GOCARDLESS_KEY'), 'secret' => env('GOCARDLESS_SECRET')],
        ],
        'default_provider' => env('PAYMENT_PROVIDER', 'stripe'),
        'platform_account' => [
            'stripe_account_id' => env('STRIPE_ACCOUNT_ID'),
        ],
        'capture_mode' => env('PAYMENT_CAPTURE_MODE', 'auto'),
    ],

    /*
    |--------------------------------------------------------------------------
    | TAX CONFIGURATION
    |--------------------------------------------------------------------------
    */
    'tax' => [
        'default_tax_percent' => 0.00,
        'country_rates' => [
            'GB' => 20.00,
            'US' => 0.00,
            'DE' => 19.00,
        ],
        'apply_tax_to_platform_fees' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | PAYOUT CONFIGURATION
    |--------------------------------------------------------------------------
    */
    'payouts' => [
        'min_payout_amount' => 50.00,
        'payout_batch_window_days' => 7,
        'payout_provider' => 'stripe_connect',
        'payout_hold_days' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | INVOICE CONFIGURATION
    |--------------------------------------------------------------------------
    */
    'invoicing' => [
        'invoice_due_days' => env('INVOICE_DUE_DAYS', 14),
        'auto_generate_on_timesheet_approval' => true,
        'line_item_template' => [
            'shift_description' => '{role} at {location} - {date} {start_time} to {end_time}',
            'include_employee_name' => false, // Privacy consideration
            'include_hourly_breakdown' => true,
        ],
        'platform_commission' => [
            'rate_percent' => 2.00,
            'applied_to' => 'agency_markup', // or 'total_amount'
            'invoice_separately' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | REPORTS
    |--------------------------------------------------------------------------
    */
    'reports' => [
        'employee_utilization' => ['description' => 'Hours worked by employee across all agencies'],
        'agency_revenue' => ['description' => 'Revenue by agency per period'],
        'employer_spend' => ['description' => 'Total spend by employer per agency'],
        'assignment_duration' => ['description' => 'Average assignment length'],
        'shift_fill_rate' => ['description' => 'Percentage of shifts filled vs requested'],
        'overlap_violations' => ['description' => 'Any detected shift overlaps'],
        'availability_compliance' => ['description' => 'Shifts scheduled outside availability'],
    ],

    /*
    |--------------------------------------------------------------------------
    | WEBHOOKS
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        'enabled_events' => [
            'assignment.created',
            'shift.completed',
            'timesheet.employer_approved',
            'invoice.paid',
            'availability.updated',
            'time_off.approved',
        ],
        'retry_policy' => ['attempts' => 5, 'backoff_seconds' => 60]
    ],

    /*
    |--------------------------------------------------------------------------
    | SECURITY & AUDIT
    |--------------------------------------------------------------------------
    */
    'security' => [
        'audit_enabled' => true,
        'audit_exclude' => ['notifications', 'sessions'],
        'password_policy' => ['min_length' => 8, 'require_numbers' => true, 'require_special' => false],
        'session_timeout_minutes' => 120,
        '2fa_required_roles' => ['agency_admin', 'employer_admin', 'super_admin'],
    ],

    /*
    |--------------------------------------------------------------------------
    | OPERATIONAL SETTINGS
    |--------------------------------------------------------------------------
    */
    'ops' => [
        'max_shift_lookahead_days' => 365,
        'timesheet_edit_window_minutes' => 60,
        'shift_offer_expiry_hours' => 24,
        'auto_reject_expired_offers' => true,
        'assignment_max_duration_days' => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | DATA RETENTION
    |--------------------------------------------------------------------------
    */
    'retention' => [
        'audit_logs_days' => env('AUDIT_LOG_RETENTION_DAYS', 365),
        'invoice_storage_days' => env('INVOICE_STORAGE_DAYS', 2555), // 7 years
        'timesheet_storage_days' => env('TIMESHEET_STORAGE_DAYS', 2555),
    ],

];
