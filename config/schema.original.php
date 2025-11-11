<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ShiftPilot - Complete Platform Configuration
    |--------------------------------------------------------------------------
    |
    | One-stop config describing entities, validations, relationships,
    | permissions, billing, payouts, tax, webhooks, events, flows and platform
    | operational knobs required to run in production.
    |
    */

    'meta' => [
        'name' => 'ShiftPilot',
        'version' => '1.0.0',
        'currency' => 'GBP',
        'default_timezone' => 'UTC+01:00',
        'audit_retention_days' => 365,
        'invoice_due_days' => 14,
    ],

    /*
    |--------------------------------------------------------------------------
    | ENTITIES
    |--------------------------------------------------------------------------
    | Each entity contains fields, validation rules, relationships (for
    | automated generation), and policy_rules (high level). Use these to
    | scaffold migrations, models, resources and policies.
    */
    'entities' => [

        /*
        |--------------------------------------------------------------------------
        | User (base)
        |--------------------------------------------------------------------------
        */
        'user' => [
            'table' => 'users',
            'fields' => [
                'id' => ['type' => 'increments'],
                'name' => ['type' => 'string', 'nullable' => false],
                'email' => ['type' => 'string', 'nullable' => false, 'unique' => true],
                'password' => ['type' => 'string', 'nullable' => false],
                'role' => ['type' => 'string', 'nullable' => false, 'default' => 'employee'], // super_admin, agency_admin, agent, employer_admin, manager, contact, employee
                'phone' => ['type' => 'string', 'nullable' => true],
                'status' => ['type' => 'string', 'nullable' => false, 'default' => 'active'],
                'meta' => ['type' => 'json', 'nullable' => true], // preferences, 2FA, etc
                'email_verified_at' => ['type' => 'timestamp', 'nullable' => true],
                'last_login_at' => ['type' => 'timestamp', 'nullable' => true],
                'created_at' => ['type' => 'timestamp', 'nullable' => true],
                'updated_at' => ['type' => 'timestamp', 'nullable' => true],
            ],
            'validation' => [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8',
                'role' => 'required|in:super_admin,agency_admin,agent,employer_admin,manager,contact,employee',
                'status' => 'required|in:active,inactive,suspended',
            ],
            'relationships' => [
                ['type' => 'morphOne', 'related' => 'Profile', 'name' => 'profile'], // polymorphic profile (Employer / Agency / Employee / Agent / Contact)
            ],
            'policy_rules' => [
                'create' => ['super_admin'],
                'view' => ['super_admin', 'self', 'related:profile.owner'],
                'update' => ['super_admin', 'self'],
                'delete' => ['super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Agency
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
                'commission_rate' => ['type' => 'decimal', 'precision' => 5, 'scale' => 2, 'default' => 10.00], // percent
                'subscription_status' => ['type' => 'string', 'default' => 'active'],
                'meta' => ['type' => 'json', 'nullable' => true], // payment provider settings, business hours
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'user_id' => 'required|exists:users,id',
                'name' => 'required|string|max:255',
                'billing_email' => 'nullable|email',
                'commission_rate' => 'nullable|numeric|min:0|max:100'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'User'],
                ['type' => 'hasMany', 'related' => 'Agent'],
                ['type' => 'hasMany', 'related' => 'Employee'],
                ['type' => 'hasMany', 'related' => 'EmployerAgencyLink'],
                ['type' => 'hasMany', 'related' => 'Invoice'],
                ['type' => 'hasMany', 'related' => 'Payroll']
            ],
            'policy_rules' => [
                'create' => ['super_admin'],
                'view' => ['super_admin', 'related:user'],
                'update' => ['super_admin', 'related:user'],
                'delete' => ['super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Agent (agency user acting on behalf)
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
            'policy_rules' => [
                'create' => ['agency_admin', 'super_admin'],
                'view' => ['agency_admin', 'super_admin'],
                'update' => ['agency_admin', 'super_admin'],
                'delete' => ['agency_admin', 'super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Employer (client)
        |--------------------------------------------------------------------------
        */
        'employer' => [
            'table' => 'employers',
            'fields' => [
                'id' => ['type' => 'increments'],
                'user_id' => ['type' => 'foreign', 'references' => 'users,id'],
                'name' => ['type' => 'string'],
                'billing_email' => ['type' => 'string', 'nullable' => true],
                'address' => ['type' => 'string', 'nullable' => true],
                'city' => ['type' => 'string', 'nullable' => true],
                'country' => ['type' => 'string', 'nullable' => true],
                'subscription_status' => ['type' => 'string', 'default' => 'active'],
                'meta' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'user_id' => 'required|exists:users,id',
                'name' => 'required|string|max:255',
                'billing_email' => 'nullable|email'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'User'],
                ['type' => 'hasMany', 'related' => 'Contact'],
                ['type' => 'hasMany', 'related' => 'Location'],
                ['type' => 'hasMany', 'related' => 'Shift'],
                ['type' => 'hasMany', 'related' => 'EmployerAgencyLink'],
                ['type' => 'hasMany', 'related' => 'Invoice']
            ],
            'policy_rules' => [
                'create' => ['super_admin', 'agency_admin'],
                'view' => ['super_admin', 'related:user', 'related:agency'],
                'update' => ['super_admin', 'related:user', 'related:agency'],
                'delete' => ['super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Employer Agency Link (contract)
        |--------------------------------------------------------------------------
        */
        'employer_agency_link' => [
            'table' => 'employer_agency_links',
            'fields' => [
                'id' => ['type' => 'increments'],
                'employer_id' => ['type' => 'foreign', 'references' => 'employers,id'],
                'agency_id' => ['type' => 'foreign', 'references' => 'agencies,id'],
                'status' => ['type' => 'string', 'default' => 'pending'], // pending, approved, suspended, terminated
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
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Employer'],
                ['type' => 'belongsTo', 'related' => 'Agency']
            ],
            'policy_rules' => [
                'create' => ['agency_admin', 'employer_admin', 'super_admin'],
                'view' => ['agency_admin', 'employer_admin', 'super_admin'],
                'update' => ['agency_admin', 'employer_admin', 'super_admin'],
                'delete' => ['super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Contact (employer-side approver/manager)
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
                'role' => ['type' => 'string', 'default' => 'manager'], // manager, approver, supervisor
                'can_sign_timesheets' => ['type' => 'boolean', 'default' => false],
                'meta' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'employer_id' => 'required|exists:employers,id',
                'email' => 'required|email',
                'name' => 'required|string'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Employer'],
                ['type' => 'belongsTo', 'related' => 'User', 'foreign_key' => 'user_id']
            ],
            'policy_rules' => [
                'create' => ['employer_admin', 'super_admin'],
                'view' => ['employer_admin', 'super_admin', 'related:employer'],
                'update' => ['employer_admin', 'super_admin'],
                'delete' => ['super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Location (employer site)
        |--------------------------------------------------------------------------
        */
        'location' => [
            'table' => 'locations',
            'fields' => [
                'id' => ['type' => 'increments'],
                'employer_id' => ['type' => 'foreign', 'references' => 'employers,id'],
                'name' => ['type' => 'string'],
                'address' => ['type' => 'string', 'nullable' => true],
                'latitude' => ['type' => 'decimal', 'precision' => 10, 'scale' => 6, 'nullable' => true],
                'longitude' => ['type' => 'decimal', 'precision' => 10, 'scale' => 6, 'nullable' => true],
                'meta' => ['type' => 'json', 'nullable' => true], // parking, entry instructions
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'employer_id' => 'required|exists:employers,id',
                'name' => 'required|string'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Employer'],
                ['type' => 'hasMany', 'related' => 'Shift']
            ],
            'policy_rules' => [
                'create' => ['employer_admin', 'super_admin'],
                'view' => ['employer_admin', 'super_admin', 'related:employer'],
                'update' => ['employer_admin', 'super_admin'],
                'delete' => ['super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Employee (worker)
        |--------------------------------------------------------------------------
        */
        'employee' => [
            'table' => 'employees',
            'fields' => [
                'id' => ['type' => 'increments'],
                'user_id' => ['type' => 'foreign', 'references' => 'users,id'],
                'agency_id' => ['type' => 'foreign', 'references' => 'agencies,id', 'nullable' => true],
                'employer_id' => ['type' => 'foreign', 'references' => 'employers,id', 'nullable' => true], // direct-hire optional
                'position' => ['type' => 'string', 'nullable' => true],
                'pay_rate' => ['type' => 'decimal', 'precision' => 8, 'scale' => 2, 'nullable' => true], // what the employee is paid
                'availability' => ['type' => 'json', 'nullable' => true], // weekly availability patterns
                'qualifications' => ['type' => 'json', 'nullable' => true], // skills, certificates
                'employment_type' => ['type' => 'string', 'default' => 'temp'], // temp, perm, part_time
                'status' => ['type' => 'string', 'default' => 'active'],
                'meta' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'user_id' => 'required|exists:users,id',
                'agency_id' => 'nullable|exists:agencies,id',
                'pay_rate' => 'nullable|numeric|min:0'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'User'],
                ['type' => 'belongsTo', 'related' => 'Agency'],
                ['type' => 'belongsTo', 'related' => 'Employer'],
                ['type' => 'hasMany', 'related' => 'Placement'],
                ['type' => 'hasMany', 'related' => 'Shift'],
                ['type' => 'hasMany', 'related' => 'Timesheet'],
                ['type' => 'hasMany', 'related' => 'Payroll'],
                ['type' => 'hasMany', 'related' => 'EmployeeAvailability'],
                ['type' => 'hasMany', 'related' => 'TimeOffRequest']
            ],
            'policy_rules' => [
                'create' => ['agency_admin', 'super_admin'],
                'view' => ['super_admin', 'related:agency', 'related:employer', 'self'],
                'update' => ['super_admin', 'related:agency', 'related:employer', 'self'],
                'delete' => ['super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Employee Availability (recurring and one-off availability)
        |--------------------------------------------------------------------------
        */
        'employee_availability' => [
            'table' => 'employee_availabilities',
            'fields' => [
                'id' => ['type' => 'increments'],
                'employee_id' => ['type' => 'foreign', 'references' => 'employees,id'],
                'type' => ['type' => 'string', 'default' => 'recurring'], // recurring, one_time
                'day_of_week' => ['type' => 'string', 'nullable' => true], // mon, tue, wed, thu, fri, sat, sun
                'start_date' => ['type' => 'date', 'nullable' => true], // for one_time type
                'end_date' => ['type' => 'date', 'nullable' => true], // for one_time type
                'start_time' => ['type' => 'time'],
                'end_time' => ['type' => 'time'],
                'timezone' => ['type' => 'string', 'default' => 'UTC'],
                'status' => ['type' => 'string', 'default' => 'available'], // available, unavailable, preferred
                'priority' => ['type' => 'integer', 'default' => 1], // 1-10, higher = more preferred
                'location_preference' => ['type' => 'json', 'nullable' => true], // preferred locations/areas
                'max_shift_length_hours' => ['type' => 'integer', 'nullable' => true],
                'min_shift_length_hours' => ['type' => 'integer', 'nullable' => true],
                'notes' => ['type' => 'text', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'employee_id' => 'required|exists:employees,id',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'type' => 'required|in:recurring,one_time'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Employee']
            ],
            'policy_rules' => [
                'create' => ['employee', 'agency_admin', 'super_admin'],
                'view' => ['employee', 'agency_admin', 'super_admin'],
                'update' => ['employee', 'agency_admin', 'super_admin'],
                'delete' => ['employee', 'agency_admin', 'super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Time Off Request (unavailability due to time off)
        |--------------------------------------------------------------------------
        */
        'time_off_request' => [
            'table' => 'time_off_requests',
            'fields' => [
                'id' => ['type' => 'increments'],
                'employee_id' => ['type' => 'foreign', 'references' => 'employees,id'],
                'type' => ['type' => 'string', 'default' => 'vacation'], // vacation, sick, personal, bereavement, other
                'start_date' => ['type' => 'date'],
                'end_date' => ['type' => 'date'],
                'start_time' => ['type' => 'time', 'nullable' => true], // if partial day
                'end_time' => ['type' => 'time', 'nullable' => true], // if partial day
                'status' => ['type' => 'string', 'default' => 'pending'], // pending, approved, rejected, cancelled
                'reason' => ['type' => 'text', 'nullable' => true],
                'approved_by_id' => ['type' => 'foreign', 'references' => 'users,id', 'nullable' => true],
                'approved_at' => ['type' => 'timestamp', 'nullable' => true],
                'attachments' => ['type' => 'json', 'nullable' => true], // supporting documents
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'employee_id' => 'required|exists:employees,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'type' => 'required|in:vacation,sick,personal,bereavement,other'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Employee'],
                ['type' => 'belongsTo', 'related' => 'User', 'foreign_key' => 'approved_by_id']
            ],
            'policy_rules' => [
                'create' => ['employee', 'agency_admin', 'super_admin'],
                'view' => ['employee', 'agency_admin', 'super_admin'],
                'update' => ['employee', 'agency_admin', 'super_admin'],
                'delete' => ['employee', 'agency_admin', 'super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Placement (employee assigned to employer via agency)
        |--------------------------------------------------------------------------
        */
        'placement' => [
            'table' => 'placements',
            'fields' => [
                'id' => ['type' => 'increments'],
                'employer_id' => ['type' => 'foreign', 'references' => 'employers,id'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'text', 'nullable' => true],
                'role_requirements' => ['type' => 'json'],
                'required_qualifications' => ['type' => 'json', 'nullable' => true],
                'experience_level' => ['type' => 'string', 'default' => 'entry'],
                'background_check_required' => ['type' => 'boolean', 'default' => false],
                'location_id' => ['type' => 'foreign', 'references' => 'locations,id'],
                'location_instructions' => ['type' => 'text', 'nullable' => true],
                'start_date' => ['type' => 'date'],
                'end_date' => ['type' => 'date', 'nullable' => true],
                'shift_pattern' => ['type' => 'string', 'default' => 'one_time'],
                'recurrence_rules' => ['type' => 'json', 'nullable' => true],
                'budget_type' => ['type' => 'string', 'default' => 'hourly'],
                'budget_amount' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2],
                'currency' => ['type' => 'string', 'default' => 'GBP'],
                'overtime_rules' => ['type' => 'json', 'nullable' => true],
                'target_agencies' => ['type' => 'string', 'default' => 'all'],
                'specific_agency_ids' => ['type' => 'json', 'nullable' => true],
                'response_deadline' => ['type' => 'timestamp', 'nullable' => true],
                'status' => ['type' => 'string', 'default' => 'draft'],
                'selected_agency_id' => ['type' => 'foreign', 'references' => 'agencies,id', 'nullable' => true],
                'selected_employee_id' => ['type' => 'foreign', 'references' => 'employees,id', 'nullable' => true],
                'agreed_rate' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2, 'nullable' => true],
                'created_by_id' => ['type' => 'foreign', 'references' => 'users,id'],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'employer_id' => 'required|exists:employers,id',
                'title' => 'required|string|max:255',
                'location_id' => 'required|exists:locations,id',
                'start_date' => 'required|date',
                'budget_amount' => 'required|numeric|min:0',
                'target_agencies' => 'required|in:all,specific',
                'status' => 'required|in:draft,active,filled,cancelled,completed'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Employer'],
                ['type' => 'belongsTo', 'related' => 'Location'],
                ['type' => 'belongsTo', 'related' => 'User', 'foreign_key' => 'created_by_id'],
                ['type' => 'belongsTo', 'related' => 'Agency', 'foreign_key' => 'selected_agency_id'],
                ['type' => 'belongsTo', 'related' => 'Employee', 'foreign_key' => 'selected_employee_id'],
                ['type' => 'hasMany', 'related' => 'AgencyResponse'],
                ['type' => 'hasMany', 'related' => 'Shift']
            ],
            'policy_rules' => [
                'create' => ['employer_admin', 'super_admin'],
                'view' => ['employer_admin', 'agency_admin', 'super_admin'],
                'update' => ['employer_admin', 'super_admin'],
                'delete' => ['super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Shift
        |--------------------------------------------------------------------------
        */
        'shift' => [
            'table' => 'shifts',
            'fields' => [
                'id' => ['type' => 'increments'],
                'employer_id' => ['type' => 'foreign', 'references' => 'employers,id'],
                'agency_id' => ['type' => 'foreign', 'references' => 'agencies,id', 'nullable' => true],
                'placement_id' => ['type' => 'foreign', 'references' => 'placements,id', 'nullable' => true],
                'employee_id' => ['type' => 'foreign', 'references' => 'employees,id', 'nullable' => true],
                'agent_id' => ['type' => 'foreign', 'references' => 'agents,id', 'nullable' => true],
                'location_id' => ['type' => 'foreign', 'references' => 'locations,id'],
                'start_time' => ['type' => 'timestamp'],
                'end_time' => ['type' => 'timestamp'],
                'hourly_rate' => ['type' => 'decimal', 'precision' => 8, 'scale' => 2, 'nullable' => true], // if null -> use placement.client_rate
                'status' => ['type' => 'string', 'default' => 'open'], // open, offered, assigned, completed, agency_approved, employer_approved, billed, cancelled
                'created_by_type' => ['type' => 'string'], // employer / agency
                'created_by_id' => ['type' => 'integer'],
                'meta' => ['type' => 'json', 'nullable' => true],
                'notes' => ['type' => 'text', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'employer_id' => 'required|exists:employers,id',
                'start_time' => 'required|date',
                'end_time' => 'required|date|after:start_time',
                'status' => 'required|in:open,offered,assigned,completed,agency_approved,employer_approved,billed,cancelled'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Employer'],
                ['type' => 'belongsTo', 'related' => 'Agency'],
                ['type' => 'belongsTo', 'related' => 'Placement'],
                ['type' => 'belongsTo', 'related' => 'Employee'],
                ['type' => 'belongsTo', 'related' => 'Agent'],
                ['type' => 'belongsTo', 'related' => 'Location'],
                ['type' => 'hasOne', 'related' => 'Timesheet'],
                ['type' => 'hasMany', 'related' => 'ShiftApproval']
            ],
            'policy_rules' => [
                'create' => ['employer_admin', 'agency_admin', 'super_admin'],
                'view' => ['super_admin', 'related:employer_admin', 'related:agency_admin', 'related:employee', 'related:agent'],
                'update' => ['employer_admin', 'agency_admin', 'super_admin'],
                'delete' => ['super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Shift Template (recurring shifts)
        |--------------------------------------------------------------------------
        */
        'shift_template' => [
            'table' => 'shift_templates',
            'fields' => [
                'id' => ['type' => 'increments'],
                'employer_id' => ['type' => 'foreign', 'references' => 'employers,id'],
                'location_id' => ['type' => 'foreign', 'references' => 'locations,id'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'text', 'nullable' => true],
                'day_of_week' => ['type' => 'string'], // mon, tue, wed, thu, fri, sat, sun
                'start_time' => ['type' => 'time'],
                'end_time' => ['type' => 'time'],
                'role_requirement' => ['type' => 'string', 'nullable' => true],
                'required_qualifications' => ['type' => 'json', 'nullable' => true],
                'hourly_rate' => ['type' => 'decimal', 'precision' => 8, 'scale' => 2, 'nullable' => true],
                'recurrence_type' => ['type' => 'string', 'default' => 'weekly'], // weekly, biweekly, monthly
                'status' => ['type' => 'string', 'default' => 'active'],
                'start_date' => ['type' => 'date', 'nullable' => true],
                'end_date' => ['type' => 'date', 'nullable' => true],
                'created_by_type' => ['type' => 'string'],
                'created_by_id' => ['type' => 'integer'],
                'meta' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'employer_id' => 'required|exists:employers,id',
                'location_id' => 'required|exists:locations,id',
                'day_of_week' => 'required|in:mon,tue,wed,thu,fri,sat,sun',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Employer'],
                ['type' => 'belongsTo', 'related' => 'Location'],
                ['type' => 'hasMany', 'related' => 'Shift']
            ],
            'policy_rules' => [
                'create' => ['employer_admin', 'agency_admin', 'super_admin'],
                'view' => ['employer_admin', 'agency_admin', 'super_admin'],
                'update' => ['employer_admin', 'agency_admin', 'super_admin'],
                'delete' => ['employer_admin', 'agency_admin', 'super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Shift Offer (agency offering shift to employee)
        |--------------------------------------------------------------------------
        */
        'shift_offer' => [
            'table' => 'shift_offers',
            'fields' => [
                'id' => ['type' => 'increments'],
                'shift_id' => ['type' => 'foreign', 'references' => 'shifts,id'],
                'employee_id' => ['type' => 'foreign', 'references' => 'employees,id'],
                'offered_by_id' => ['type' => 'foreign', 'references' => 'users,id'], // agent who offered
                'status' => ['type' => 'string', 'default' => 'pending'], // pending, accepted, rejected, expired
                'expires_at' => ['type' => 'timestamp'],
                'responded_at' => ['type' => 'timestamp', 'nullable' => true],
                'response_notes' => ['type' => 'text', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'shift_id' => 'required|exists:shifts,id',
                'employee_id' => 'required|exists:employees,id',
                'offered_by_id' => 'required|exists:users,id'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Shift'],
                ['type' => 'belongsTo', 'related' => 'Employee'],
                ['type' => 'belongsTo', 'related' => 'User', 'foreign_key' => 'offered_by_id']
            ],
            'policy_rules' => [
                'create' => ['agency_admin', 'agent', 'super_admin'],
                'view' => ['agency_admin', 'agent', 'employee', 'super_admin'],
                'update' => ['employee', 'agency_admin', 'super_admin'],
                'delete' => ['agency_admin', 'super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | ShiftApproval (who signed off; supports multiple approvers)
        |--------------------------------------------------------------------------
        */
        'shift_approval' => [
            'table' => 'shift_approvals',
            'fields' => [
                'id' => ['type' => 'increments'],
                'shift_id' => ['type' => 'foreign', 'references' => 'shifts,id'],
                'contact_id' => ['type' => 'foreign', 'references' => 'contacts,id'],
                'status' => ['type' => 'string', 'default' => 'pending'], // pending, approved, rejected
                'signed_at' => ['type' => 'timestamp', 'nullable' => true],
                'signature_blob_url' => ['type' => 'string', 'nullable' => true], // saved signature image/pdf
                'notes' => ['type' => 'text', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'shift_id' => 'required|exists:shifts,id',
                'contact_id' => 'required|exists:contacts,id'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Shift'],
                ['type' => 'belongsTo', 'related' => 'Contact']
            ],
            'policy_rules' => [
                'create' => ['employer_admin', 'contact', 'agent', 'super_admin'],
                'view' => ['employer_admin', 'agency_admin', 'super_admin'],
                'update' => ['employer_admin', 'agency_admin', 'super_admin'],
                'delete' => ['super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Timesheet
        |--------------------------------------------------------------------------
        */
        'timesheet' => [
            'table' => 'timesheets',
            'fields' => [
                'id' => ['type' => 'increments'],
                'shift_id' => ['type' => 'foreign', 'references' => 'shifts,id'],
                'employee_id' => ['type' => 'foreign', 'references' => 'employees,id'],
                'clock_in' => ['type' => 'timestamp', 'nullable' => true],
                'clock_out' => ['type' => 'timestamp', 'nullable' => true],
                'break_minutes' => ['type' => 'integer', 'default' => 0],
                'hours_worked' => ['type' => 'decimal', 'precision' => 8, 'scale' => 2, 'nullable' => true],
                'status' => ['type' => 'string', 'default' => 'pending'], // pending, agency_approved, employer_approved, rejected
                'agency_approved_by' => ['type' => 'foreign', 'references' => 'users,id', 'nullable' => true],
                'agency_approved_at' => ['type' => 'timestamp', 'nullable' => true],
                'approved_by_contact_id' => ['type' => 'foreign', 'references' => 'contacts,id', 'nullable' => true],
                'approved_at' => ['type' => 'timestamp', 'nullable' => true],
                'notes' => ['type' => 'text', 'nullable' => true],
                'attachments' => ['type' => 'json', 'nullable' => true], // photos, docs
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'shift_id' => 'required|exists:shifts,id',
                'employee_id' => 'required|exists:employees,id'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Shift'],
                ['type' => 'belongsTo', 'related' => 'Employee'],
                ['type' => 'belongsTo', 'related' => 'Contact', 'foreign_key' => 'approved_by_contact_id']
            ],
            'policy_rules' => [
                'create' => ['employee', 'system'],
                'view' => ['super_admin', 'agency_admin', 'employer_admin', 'related:employee'],
                'update' => ['agency_admin', 'employer_admin', 'super_admin'],
                'delete' => ['super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Payroll (agency -> employee payments)
        |--------------------------------------------------------------------------
        */
        'payroll' => [
            'table' => 'payrolls',
            'fields' => [
                'id' => ['type' => 'increments'],
                'agency_id' => ['type' => 'foreign', 'references' => 'agencies,id'],
                'employee_id' => ['type' => 'foreign', 'references' => 'employees,id'],
                'period_start' => ['type' => 'date'],
                'period_end' => ['type' => 'date'],
                'total_hours' => ['type' => 'decimal', 'precision' => 8, 'scale' => 2],
                'gross_pay' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2],
                'taxes' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2, 'default' => 0.00],
                'net_pay' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2],
                'status' => ['type' => 'string', 'default' => 'unpaid'], // unpaid, paid
                'paid_at' => ['type' => 'timestamp', 'nullable' => true],
                'payout_id' => ['type' => 'foreign', 'references' => 'payouts,id', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'agency_id' => 'required|exists:agencies,id',
                'employee_id' => 'required|exists:employees,id',
                'period_start' => 'required|date',
                'period_end' => 'required|date|after:period_start',
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Agency'],
                ['type' => 'belongsTo', 'related' => 'Employee'],
                ['type' => 'belongsTo', 'related' => 'Payout', 'foreign_key' => 'payout_id']
            ],
            'policy_rules' => [
                'create' => ['system', 'agency_admin'],
                'view' => ['agency_admin', 'super_admin'],
                'update' => ['agency_admin', 'super_admin'],
                'delete' => ['super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Invoice (between parties and to ShiftPilot)
        |--------------------------------------------------------------------------
        */
        'invoice' => [
            'table' => 'invoices',
            'fields' => [
                'id' => ['type' => 'increments'],
                'type' => ['type' => 'string'], // employer_to_agency, agency_to_shiftpilot, employer_to_shiftpilot, shiftpilot_refund
                'from_type' => ['type' => 'string'], // employer / agency / shiftpilot
                'from_id' => ['type' => 'integer'],
                'to_type' => ['type' => 'string'], // employer / agency / shiftpilot
                'to_id' => ['type' => 'integer'],
                'reference' => ['type' => 'string', 'nullable' => true], // invoice number
                'line_items' => ['type' => 'json', 'nullable' => true],
                'subtotal' => ['type' => 'decimal', 'precision' => 12, 'scale' => 2],
                'tax_amount' => ['type' => 'decimal', 'precision' => 12, 'scale' => 2, 'default' => 0.00],
                'total_amount' => ['type' => 'decimal', 'precision' => 12, 'scale' => 2],
                'status' => ['type' => 'string', 'default' => 'pending'], // pending, paid, partial, overdue, cancelled
                'due_date' => ['type' => 'date'],
                'paid_at' => ['type' => 'timestamp', 'nullable' => true],
                'payment_reference' => ['type' => 'string', 'nullable' => true], // payment id
                'metadata' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'type' => 'required|string',
                'from_type' => 'required|string',
                'to_type' => 'required|string',
                'subtotal' => 'required|numeric|min:0',
                'total_amount' => 'required|numeric|min:0',
                'due_date' => 'required|date'
            ],
            'relationships' => [
                ['type' => 'morphTo', 'related' => 'Billable'], // from/to polymorphic references
                ['type' => 'hasMany', 'related' => 'Payment']
            ],
            'policy_rules' => [
                'create' => ['system', 'agency_admin', 'employer_admin', 'super_admin'],
                'view' => ['related:from', 'related:to', 'super_admin'],
                'update' => ['super_admin'],
                'delete' => ['super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Payment (records of payments)
        |--------------------------------------------------------------------------
        */
        'payment' => [
            'table' => 'payments',
            'fields' => [
                'id' => ['type' => 'increments'],
                'invoice_id' => ['type' => 'foreign', 'references' => 'invoices,id'],
                'payer_type' => ['type' => 'string'], // agency, employer, shiftpilot
                'payer_id' => ['type' => 'integer'],
                'amount' => ['type' => 'decimal', 'precision' => 12, 'scale' => 2],
                'method' => ['type' => 'string'], // stripe, bacs, sepa, paypal
                'processor_id' => ['type' => 'string', 'nullable' => true], // stripe payment id or bank transaction id
                'status' => ['type' => 'string', 'default' => 'completed'], // completed, failed, pending, refunded
                'fee_amount' => ['type' => 'decimal', 'precision' => 10, 'scale' => 2, 'default' => 0.00], // processing fees
                'net_amount' => ['type' => 'decimal', 'precision' => 12, 'scale' => 2],
                'metadata' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'invoice_id' => 'required|exists:invoices,id',
                'amount' => 'required|numeric|min:0'
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Invoice']
            ],
            'policy_rules' => [
                'create' => ['system', 'super_admin'],
                'view' => ['related:invoice', 'super_admin'],
                'update' => ['super_admin'],
                'delete' => ['super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Payout (aggregated pay runs from agency to employees)
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
                'status' => ['type' => 'string', 'default' => 'processing'], // processing, paid, failed
                'provider_payout_id' => ['type' => 'string', 'nullable' => true],
                'metadata' => ['type' => 'json', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'relationships' => [
                ['type' => 'belongsTo', 'related' => 'Agency'],
                ['type' => 'hasMany', 'related' => 'Payroll']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Subscription (ShiftPilot plans)
        |--------------------------------------------------------------------------
        */
        'subscription' => [
            'table' => 'subscriptions',
            'fields' => [
                'id' => ['type' => 'increments'],
                'entity_type' => ['type' => 'string'], // employer, agency
                'entity_id' => ['type' => 'integer'],
                'plan_key' => ['type' => 'string'], // e.g. agency_pro, employer_basic
                'plan_name' => ['type' => 'string'],
                'amount' => ['type' => 'decimal', 'precision' => 8, 'scale' => 2],
                'interval' => ['type' => 'string', 'default' => 'monthly'], // monthly, yearly
                'status' => ['type' => 'string', 'default' => 'active'], // active, past_due, cancelled
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
                'amount' => 'required|numeric|min:0'
            ],
            'relationships' => [
                ['type' => 'morphTo', 'related' => 'Subscriber']
            ],
            'policy_rules' => [
                'create' => ['system', 'super_admin'],
                'view' => ['related:subscriber', 'super_admin'],
                'update' => ['related:subscriber', 'super_admin'],
                'delete' => ['super_admin']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Platform Billing Settings
        |--------------------------------------------------------------------------
        */
        'platform_billing' => [
            'table' => 'platform_billing',
            'fields' => [
                'id' => ['type' => 'increments'],
                'commission_rate' => ['type' => 'decimal', 'precision' => 5, 'scale' => 2, 'default' => 2.00], // platform commission % per shift
                'transaction_fee_flat' => ['type' => 'decimal', 'precision' => 8, 'scale' => 2, 'default' => 0.30],
                'transaction_fee_percent' => ['type' => 'decimal', 'precision' => 5, 'scale' => 2, 'default' => 2.9],
                'payout_schedule_days' => ['type' => 'integer', 'default' => 7], // default payout lag for agencies
                'tax_vat_rate_percent' => ['type' => 'decimal', 'precision' => 5, 'scale' => 2, 'default' => 0.00],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ],
            'validation' => [
                'commission_rate' => 'required|numeric|min:0|max:100'
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Rate Card (per role/location/time)
        |--------------------------------------------------------------------------
        */
        'rate_card' => [
            'table' => 'rate_cards',
            'fields' => [
                'id' => ['type' => 'increments'],
                'employer_id' => ['type' => 'foreign', 'references' => 'employers,id', 'nullable' => true], // employer-specific override
                'agency_id' => ['type' => 'foreign', 'references' => 'agencies,id', 'nullable' => true], // agency-specific
                'role_key' => ['type' => 'string'], // nurse, chef, driver
                'location_id' => ['type' => 'foreign', 'references' => 'locations,id', 'nullable' => true],
                'day_of_week' => ['type' => 'string', 'nullable' => true], // Mon, Tue... null => all
                'start_time' => ['type' => 'time', 'nullable' => true],
                'end_time' => ['type' => 'time', 'nullable' => true],
                'rate' => ['type' => 'decimal', 'precision' => 8, 'scale' => 2],
                'currency' => ['type' => 'string', 'default' => 'USD'],
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
        | Audit Log (immutable)
        |--------------------------------------------------------------------------
        */
        'audit_log' => [
            'table' => 'audit_logs',
            'fields' => [
                'id' => ['type' => 'increments'],
                'actor_type' => ['type' => 'string'], // user, system
                'actor_id' => ['type' => 'integer', 'nullable' => true],
                'action' => ['type' => 'string'],
                'target_type' => ['type' => 'string', 'nullable' => true],
                'target_id' => ['type' => 'integer', 'nullable' => true],
                'payload' => ['type' => 'json', 'nullable' => true],
                'ip_address' => ['type' => 'string', 'nullable' => true],
                'user_agent' => ['type' => 'string', 'nullable' => true],
                'created_at' => ['type' => 'timestamp']
            ],
            'retention_days' => env('AUDIT_RETENTION_DAYS', 365)
        ],

        /*
        |--------------------------------------------------------------------------
        | Notification (in-app + email + sms)
        |--------------------------------------------------------------------------
        */
        'notification' => [
            'table' => 'notifications',
            'fields' => [
                'id' => ['type' => 'increments'],
                'recipient_type' => ['type' => 'string'], // user, agency, employer
                'recipient_id' => ['type' => 'integer'],
                'channel' => ['type' => 'string', 'default' => 'in_app'], // email, sms, in_app
                'template_key' => ['type' => 'string'],
                'payload' => ['type' => 'json', 'nullable' => true],
                'is_read' => ['type' => 'boolean', 'default' => false],
                'sent_at' => ['type' => 'timestamp', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Webhook Subscriptions (for external systems)
        |--------------------------------------------------------------------------
        */
        'webhook_subscription' => [
            'table' => 'webhook_subscriptions',
            'fields' => [
                'id' => ['type' => 'increments'],
                'owner_type' => ['type' => 'string'], // agency, employer
                'owner_id' => ['type' => 'integer'],
                'url' => ['type' => 'string'],
                'events' => ['type' => 'json'], // e.g. ["shift.assigned","timesheet.approved"]
                'secret' => ['type' => 'string'],
                'status' => ['type' => 'string', 'default' => 'active'],
                'last_delivery_at' => ['type' => 'timestamp', 'nullable' => true],
                'created_at' => ['type' => 'timestamp'],
                'updated_at' => ['type' => 'timestamp']
            ]
        ],
    ], // end entities

    /*
    |--------------------------------------------------------------------------
    | ROLES & PERMISSIONS
    |--------------------------------------------------------------------------
    | Fine-grained permission definitions to map to gates/policies and UI.
    */
    'user_roles' => [
        'super_admin' => [
            'label' => 'Super Admin',
            'description' => 'Full access to everything',
            'permissions' => ['*']
        ],
        'agency_admin' => [
            'label' => 'Agency Admin',
            'description' => 'Manage agency, staff, placements, payroll, invoices',
            'permissions' => [
                'employee:*',
                'agent:*',
                'placement:*',
                'shift:*',
                'timesheet:*',
                'invoice:view,create',
                'payroll:create,view',
                'payout:view',
                'webhook:manage',
                'subscription:view',
                'availability:view,manage',
                'time_off:approve'
            ]
        ],
        'agent' => [
            'label' => 'Agent',
            'description' => 'Assign staff and manage shifts on behalf of agency',
            'permissions' => [
                'shift:create,view,update',
                'placement:create,view',
                'timesheet:view',
                'invoice:view',
                'availability:view',
                'employee:view'
            ]
        ],
        'employer_admin' => [
            'label' => 'Employer Admin',
            'description' => 'Create shift requests, approve timesheets, pay invoices, manage contacts',
            'permissions' => [
                'shift:create,view,update',
                'contact:manage',
                'timesheet:approve,view',
                'invoice:view,create,pay',
                'shift_template:manage'
            ]
        ],
        'contact' => [
            'label' => 'Employer Contact',
            'description' => 'Can sign off/approve shifts and timesheets',
            'permissions' => [
                'timesheet:approve',
                'shift:approve'
            ]
        ],
        'employee' => [
            'label' => 'Employee',
            'description' => 'Worker; sees assigned shifts and can clock-in/out',
            'permissions' => [
                'shift:view:own',
                'timesheet:create:own',
                'timesheet:view:own',
                'availability:manage:own',
                'time_off:request'
            ]
        ],
        'system' => [
            'label' => 'System',
            'description' => 'Internal automated processes and webhooks',
            'permissions' => [
                'timesheet:create',
                'invoice:create',
                'payment:record',
                'subscription:manage',
                'shift:generate_from_template'
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | AVAILABILITY & SCHEDULING CONFIGURATION
    |--------------------------------------------------------------------------
    */
    'scheduling' => [
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
            'consider_employee_preferences' => true,
            'max_travel_distance_km' => 50,
            'prefer_employees_with_previous_shifts' => true,
            'auto_offer_to_best_match' => false,
        ],
        'notifications' => [
            'shift_offer_expiry_hours' => 24,
            'reminder_before_shift_hours' => 2,
            'availability_update_notification' => true,
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | EVENTS & FLOWS
    |--------------------------------------------------------------------------
    | Define event keys and canonical flows; used by the event bus + queue workers.
    | Flows are sequential arrays of step definitions containing:
    | - name: step name
    | - actor: who triggers it
    | - event: event emitted
    | - jobs: queued job keys to run
    | - notifications: notification templates
    */
    'events' => [
        'shift.requested' => ['description' => 'Employer requested a shift'],
        'shift.offered' => ['description' => 'Agency offered an employee for shift'],
        'shift.assigned' => ['description' => 'Shift assigned to employee'],
        'shift.completed' => ['description' => 'Employee marked shift as completed'],
        'timesheet.submitted' => ['description' => 'Timesheet submitted by employee'],
        'timesheet.agency_approved' => ['description' => 'Agency approves timesheet'],
        'timesheet.employer_approved' => ['description' => 'Employer contact approves timesheet'],
        'invoice.generated' => ['description' => 'Invoice generated'],
        'invoice.paid' => ['description' => 'Invoice paid'],
        'payout.processed' => ['description' => 'Payout processed to employees'],
        'subscription.renewed' => ['description' => 'Subscription renewed'],
        'webhook.dispatch' => ['description' => 'Dispatch external webhook'],
        'availability.updated' => ['description' => 'Employee availability updated'],
        'time_off.requested' => ['description' => 'Employee requested time off'],
        'time_off.approved' => ['description' => 'Time off request approved'],
        'shift_offer.sent' => ['description' => 'Shift offer sent to employee'],
        'shift_offer.accepted' => ['description' => 'Employee accepted shift offer'],
        'shift_offer.rejected' => ['description' => 'Employee rejected shift offer'],
    ],

    'flows' => [

        /*
        |--------------------------------------------------------------------------
        | Shift request -> assignment -> completion -> billing
        |--------------------------------------------------------------------------
        */
        'shift_lifecycle' => [
            ['step' => 'employer_creates_shift', 'actor' => 'employer_admin', 'emit' => 'shift.requested', 'jobs' => ['ValidateShift', 'FindAvailableEmployees'], 'notifications' => ['shift.requested:agency']],
            ['step' => 'agency_offers_employee', 'actor' => 'agent', 'emit' => 'shift_offer.sent', 'jobs' => ['LockCandidate', 'NotifyEmployee'], 'notifications' => ['shift_offer.sent:employee']],
            ['step' => 'employee_responds_offer', 'actor' => 'employee', 'emit' => 'shift_offer.accepted', 'jobs' => ['CreatePlacementIfMissing', 'CreateAssignment'], 'notifications' => ['shift_offer.accepted:agency', 'shift.assigned:employer']],
            ['step' => 'employee_clocks_in_out', 'actor' => 'employee', 'emit' => null, 'jobs' => ['CalculateHours'], 'notifications' => []],
            ['step' => 'employee_marks_completed', 'actor' => 'employee', 'emit' => 'shift.completed', 'jobs' => ['CreateTimesheet', 'NotifyAgencyForApproval'], 'notifications' => ['timesheet.submitted:agency']],
            ['step' => 'agency_approves_timesheet', 'actor' => 'agency_admin', 'emit' => 'timesheet.agency_approved', 'jobs' => ['MarkTimesheetAgencyApproved', 'NotifyEmployerForSignoff'], 'notifications' => ['timesheet.agency_approved:employer']],
            ['step' => 'employer_contact_signs', 'actor' => 'contact', 'emit' => 'timesheet.employer_approved', 'jobs' => ['MarkTimesheetEmployerApproved', 'GenerateInvoice'], 'notifications' => ['timesheet.employer_approved:agency', 'invoice.generated:employer']],
            ['step' => 'invoice_to_employer', 'actor' => 'system', 'emit' => 'invoice.generated', 'jobs' => ['CreateInvoiceLines', 'ApplyTaxes', 'SendInvoice'], 'notifications' => ['invoice.generated:employer']],
            ['step' => 'employer_pays_invoice', 'actor' => 'employer_admin', 'emit' => 'invoice.paid', 'jobs' => ['RecordPayment', 'SchedulePayoutToAgency', 'RecordPlatformFee'], 'notifications' => ['invoice.paid:agency', 'invoice.paid:shiftpilot']],
            ['step' => 'agency_processes_payroll', 'actor' => 'agency_admin', 'emit' => 'payout.processed', 'jobs' => ['CreatePayrollRecords', 'ExecutePayouts', 'MarkPayrollPaid'], 'notifications' => ['payout.processed:employee', 'payout.processed:agency']],
        ],

        /*
        |--------------------------------------------------------------------------
        | Employee Availability Management
        |--------------------------------------------------------------------------
        */
        'availability_management' => [
            ['step' => 'employee_sets_availability', 'actor' => 'employee', 'emit' => 'availability.updated', 'jobs' => ['ValidateAvailability', 'UpdateEmployeeCalendar'], 'notifications' => ['availability.updated:agency']],
            ['step' => 'employee_requests_time_off', 'actor' => 'employee', 'emit' => 'time_off.requested', 'jobs' => ['CheckShiftConflicts', 'NotifyAgency'], 'notifications' => ['time_off.requested:agency']],
            ['step' => 'agency_reviews_time_off', 'actor' => 'agency_admin', 'emit' => 'time_off.approved', 'jobs' => ['ApproveTimeOff', 'UpdateUnavailablePeriods'], 'notifications' => ['time_off.approved:employee']],
        ],

        /*
        |--------------------------------------------------------------------------
        | Automated Shift Assignment
        |--------------------------------------------------------------------------
        */
        'auto_scheduling' => [
            ['step' => 'find_available_employees', 'actor' => 'system', 'emit' => null, 'jobs' => ['MatchEmployeesToShift', 'ScoreCandidates'], 'notifications' => []],
            ['step' => 'auto_offer_shifts', 'actor' => 'system', 'emit' => 'shift_offer.sent', 'jobs' => ['CreateShiftOffers', 'NotifyEmployees'], 'notifications' => ['shift_offer.sent:employee']],
            ['step' => 'monitor_responses', 'actor' => 'system', 'emit' => null, 'jobs' => ['TrackOfferExpiry', 'EscalateUnfilledShifts'], 'notifications' => ['shift_offer.expiring:employee', 'shift.unfilled:agency']],
        ],

        /*
        |--------------------------------------------------------------------------
        | Subscription / platform billing flow
        |--------------------------------------------------------------------------
        */
        'platform_billing' => [
            ['step' => 'subscription_renewal', 'actor' => 'system', 'emit' => 'subscription.renewed', 'jobs' => ['ChargeSubscription', 'RecordRevenue', 'NotifySubscriber'], 'notifications' => ['subscription.renewed:subscriber']],
            ['step' => 'transaction_commission', 'actor' => 'system', 'emit' => null, 'jobs' => ['CalculateCommission', 'InvoiceAgencyForCommission', 'RecordPlatformRevenue'], 'notifications' => ['commission.invoice:agency']],
            ['step' => 'agency_pays_platform', 'actor' => 'agency_admin', 'emit' => null, 'jobs' => ['RecordPayment', 'ApplyBalance'], 'notifications' => ['payment.received:agency_admin']]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION TEMPLATES (keys only, content stored in templates)
    |--------------------------------------------------------------------------
    */
    'notification_templates' => [
        'shift.requested:agency' => ['channels' => ['email', 'in_app']],
        'shift.offered:employer' => ['channels' => ['email', 'in_app']],
        'shift.assigned:employee' => ['channels' => ['sms', 'email', 'in_app']],
        'timesheet.submitted:agency' => ['channels' => ['email', 'in_app']],
        'timesheet.agency_approved:employer' => ['channels' => ['email', 'in_app']],
        'invoice.generated:employer' => ['channels' => ['email']],
        'invoice.paid:agency' => ['channels' => ['email']],
        'invoice.paid:shiftpilot' => ['channels' => ['email']],
        'payout.processed:employee' => ['channels' => ['email', 'in_app']],
        'subscription.renewed:subscriber' => ['channels' => ['email']],
        'shift_offer.sent:employee' => ['channels' => ['sms', 'email', 'in_app']],
        'shift_offer.accepted:agency' => ['channels' => ['email', 'in_app']],
        'shift_offer.expiring:employee' => ['channels' => ['sms', 'in_app']],
        'shift.unfilled:agency' => ['channels' => ['email', 'in_app']],
        'availability.updated:agency' => ['channels' => ['email', 'in_app']],
        'time_off.requested:agency' => ['channels' => ['email', 'in_app']],
        'time_off.approved:employee' => ['channels' => ['email', 'in_app']],
    ],

    /*
    |--------------------------------------------------------------------------
    | PAYMENT / PAYOUT PROVIDERS
    |--------------------------------------------------------------------------
    | Providers configured in env and referred here by key.
    */
    'payments' => [
        'providers' => [
            'stripe' => ['key' => env('STRIPE_KEY'), 'secret' => env('STRIPE_SECRET'), 'connect' => true],
            'gocardless' => ['key' => env('GOCARDLESS_KEY'), 'secret' => env('GOCARDLESS_SECRET')],
        ],
        'default_provider' => env('PAYMENT_PROVIDER', 'stripe'),
        'platform_account' => [
            'stripe_account_id' => env('STRIPE_ACCOUNT_ID'),
            'bank_details' => env('PLATFORM_BANK_DETAILS', null),
        ],
        // When to auto-capture/submission behaviour
        'capture_mode' => env('PAYMENT_CAPTURE_MODE', 'auto'), // auto/manual
    ],

    /*
    |--------------------------------------------------------------------------
    | TAX & VAT RULES
    |--------------------------------------------------------------------------
    | Basic config: tax rates per country and mode (expand with plugin).
    */
    'tax' => [
        'default_tax_percent' => 0.00,
        'country_rates' => [
            'GB' => 20.00,
            'US' => 0.00, // use regional state logic in app
            'DE' => 19.00,
        ],
        'apply_tax_to_platform_fees' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | PAYOUT SETTINGS
    |--------------------------------------------------------------------------
    */
    'payouts' => [
        'min_payout_amount' => 50.00,
        'payout_batch_window_days' => 7,
        'payout_provider' => 'stripe_connect', // expected to use Connect for agency payouts
        'payout_hold_days' => 2, // hold after employer payment clears
    ],

    /*
    |--------------------------------------------------------------------------
    | INVOICE / LINE ITEM SCHEMA
    |--------------------------------------------------------------------------
    | Line items created when invoicing employer (per shift)
    */
    'invoice_line_item_template' => [
        'fields' => ['description', 'quantity', 'unit_price', 'tax_rate', 'total'],
        'shift_line_item' => [
            'description_template' => 'Shift: {role} at {location} on {date} {start}-{end}',
            'quantity' => 1,
            'unit_price_source' => 'shift.hourly_rate', // multiplied by hours worked
            'taxable' => true
        ],
        'commission_line_item' => [
            'description_template' => 'ShiftPilot commission ({percent}%)',
            'calculation_source' => 'platform.commission_rate'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | REPORTS & RECONCILIATION
    |--------------------------------------------------------------------------
    | Keys that can be used to generate reports and reconcile payments.
    */
    'reports' => [
        'daily_revenue' => ['sql_view' => 'report_daily_revenue'],
        'outstanding_invoices' => ['sql_view' => 'report_outstanding_invoices'],
        'agency_balance' => ['sql_view' => 'report_agency_balance'],
        'payouts_pending' => ['sql_view' => 'report_payouts_pending'],
        'employee_availability' => ['sql_view' => 'report_employee_availability'],
        'shift_fill_rate' => ['sql_view' => 'report_shift_fill_rate'],
        'time_off_balance' => ['sql_view' => 'report_time_off_balance'],
    ],

    /*
    |--------------------------------------------------------------------------
    | WEBHOOKS (internal events -> external consumers)
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        'enabled_events' => [
            'shift.assigned',
            'timesheet.approved',
            'invoice.paid',
            'payout.processed',
            'availability.updated',
            'time_off.approved'
        ],
        'retry_policy' => ['attempts' => 5, 'backoff_seconds' => 60]
    ],

    /*
    |--------------------------------------------------------------------------
    | AUDIT / SECURITY
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
    | OPERATIONAL KNOBS
    |--------------------------------------------------------------------------
    */
    'ops' => [
        'invoice_due_days' => env('INVOICE_DUE_DAYS', 14),
        'payout_delay_days' => env('PAYOUT_DELAY_DAYS', 7),
        'max_shift_lookahead_days' => 365,
        'timesheet_edit_window_minutes' => 60,
        'shift_offer_expiry_hours' => 24,
        'auto_schedule_enabled' => true,
        'max_auto_offers_per_shift' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | DATA RETENTION & BACKUPS
    |--------------------------------------------------------------------------
    */
    'retention' => [
        'audit_logs_days' => env('AUDIT_LOG_RETENTION_DAYS', 365),
        'invoice_storage_days' => env('INVOICE_STORAGE_DAYS', 365 * 7),
        'availability_data_days' => env('AVAILABILITY_RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI / RESOURCE HINTS (for scaffolding Filament resources)
    |--------------------------------------------------------------------------
    | Helpful hints used to scaffold admin panels and relation managers.
    */
    'ui' => [
        'filament' => [
            'resources' => [
                'employer' => ['navigation' => ['label' => 'Employers', 'icon' => 'heroicon-o-office-building']],
                'agency' => ['navigation' => ['label' => 'Agencies', 'icon' => 'heroicon-o-briefcase']],
                'shift' => ['navigation' => ['label' => 'Shifts', 'icon' => 'heroicon-o-calendar']],
                'timesheet' => ['navigation' => ['label' => 'Timesheets', 'icon' => 'heroicon-o-clock']],
                'invoice' => ['navigation' => ['label' => 'Invoices', 'icon' => 'heroicon-o-document-text']],
                'payroll' => ['navigation' => ['label' => 'Payroll', 'icon' => 'heroicon-o-currency-pound']],
                'employee_availability' => ['navigation' => ['label' => 'Availability', 'icon' => 'heroicon-o-calendar']],
                'time_off_request' => ['navigation' => ['label' => 'Time Off', 'icon' => 'heroicon-o-clock']],
            ]
        ]
    ]
];
