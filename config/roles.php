<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ShiftPilot - Role Based Access Control Configuration
    |--------------------------------------------------------------------------
    |
    | CORE PRINCIPLES:
    | - All access is role-based with explicit permissions
    | - Data ownership follows: Platform → Agency → Employer → Employee hierarchy
    | - Multi-tenancy enforced at agency and employer levels
    | - Employee data access follows agency registration relationships
    |
    */

    /*
    |--------------------------------------------------------------------------
    | PERMISSION MATRIX
    |--------------------------------------------------------------------------
    |
    | Defines granular permissions for each resource and action
    |
    */
    'permissions' => [

        // Platform-level resources
        'platform' => [
            'view_dashboard' => 'Access platform analytics and reports',
            'manage_system_settings' => 'Configure platform-wide settings',
            'view_all_agencies' => 'View all agencies in platform',
            'view_all_employers' => 'View all employers in platform',
            'manage_price_plans' => 'Create/update subscription plans',
            'view_audit_logs' => 'Access system audit logs',
            'manage_webhooks' => 'Configure system webhooks',
        ],

        // Agency management
        'agency' => [
            'view' => 'View agency details',
            'update' => 'Update agency information',
            'manage_branches' => 'Create/update agency branches',
            'view_financials' => 'View agency financial reports',
            'manage_subscription' => 'Manage agency subscription',
        ],

        // Agency staff management
        'agent' => [
            'view' => 'View agency staff',
            'create' => 'Add new agents to agency',
            'update' => 'Update agent permissions',
            'delete' => 'Remove agents from agency',
            'manage_permissions' => 'Modify agent access levels',
        ],

        // Employee registration and management
        'agency_employee' => [
            'view' => 'View registered employees',
            'register' => 'Register new employees with agency',
            'update_terms' => 'Modify employment terms',
            'update_status' => 'Change employment status',
            'view_financials' => 'View employee pay rates and financials',
            'manage_availability' => 'View and manage employee availability',
        ],

        // Employer contracts
        'employer_agency_contract' => [
            'view' => 'View contract details',
            'create' => 'Create new contracts',
            'update' => 'Modify contract terms',
            'terminate' => 'Terminate contracts',
            'view_financials' => 'View contract financial terms',
        ],

        // Shift requests from employers
        'shift_request' => [
            'view' => 'View shift requests',
            'create' => 'Create new shift requests',
            'update' => 'Modify shift requests',
            'publish' => 'Publish requests to agencies',
            'cancel' => 'Cancel shift requests',
            'view_responses' => 'View agency responses',
        ],

        // Agency responses to shift requests
        'agency_response' => [
            'view' => 'View agency responses',
            'create' => 'Submit responses to shift requests',
            'update' => 'Modify responses',
            'withdraw' => 'Withdraw responses',
            'accept' => 'Accept agency responses (employer)',
            'reject' => 'Reject agency responses (employer)',
        ],

        // Assignments (employee placements)
        'assignment' => [
            'view' => 'View assignments',
            'create' => 'Create new assignments',
            'update' => 'Modify assignment details',
            'cancel' => 'Cancel assignments',
            'view_financials' => 'View assignment rates and financials',
        ],

        // Individual shifts
        'shift' => [
            'view' => 'View shifts',
            'create' => 'Create shifts within assignments',
            'update' => 'Modify shift details',
            'cancel' => 'Cancel shifts',
            'approve' => 'Approve shifts (employer contact)',
            'manage_templates' => 'Create and manage shift templates',
        ],

        // Shift offers to employees
        'shift_offer' => [
            'view' => 'View shift offers',
            'create' => 'Offer shifts to employees',
            'update' => 'Modify offers',
            'cancel' => 'Cancel offers',
            'respond' => 'Accept/reject offers (employee)',
        ],

        // Timesheets and hours tracking
        'timesheet' => [
            'view' => 'View timesheets',
            'create' => 'Submit timesheets',
            'update' => 'Modify timesheets',
            'agency_approve' => 'Approve timesheets (agency)',
            'employer_approve' => 'Approve timesheets (employer)',
            'dispute' => 'Dispute timesheet entries',
        ],

        // Financial management
        'invoice' => [
            'view' => 'View invoices',
            'create' => 'Generate invoices',
            'update' => 'Modify invoices',
            'pay' => 'Pay invoices',
            'export' => 'Export invoice data',
        ],

        'payroll' => [
            'view' => 'View payroll records',
            'create' => 'Generate payroll',
            'update' => 'Modify payroll entries',
            'process' => 'Process payroll payments',
            'export' => 'Export payroll data',
        ],

        'payout' => [
            'view' => 'View payout batches',
            'create' => 'Create payout batches',
            'process' => 'Process payouts to employees',
            'reconcile' => 'Reconcile payout transactions',
        ],

        // Employee self-service
        'employee_profile' => [
            'view' => 'View own profile',
            'update' => 'Update personal information',
            'manage_availability' => 'Set availability preferences',
            'view_schedule' => 'View assigned shifts',
            'view_payslips' => 'View payroll history',
        ],

        // Availability management
        'availability' => [
            'view' => 'View availability',
            'manage' => 'Set availability patterns',
            'view_others' => 'View other employees availability (agency)',
        ],

        // Time off management
        'time_off' => [
            'view' => 'View time off requests',
            'request' => 'Request time off',
            'approve' => 'Approve/reject requests (agency)',
            'cancel' => 'Cancel own requests',
        ],

        // Communication
        'messaging' => [
            'send' => 'Send messages to relevant parties',
            'view_conversations' => 'Access conversation history',
            'manage_groups' => 'Create and manage group conversations',
        ],

        // Reporting
        'reports' => [
            'view_utilization' => 'View employee utilization reports',
            'view_financial' => 'View financial performance reports',
            'export_data' => 'Export report data',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ROLE DEFINITIONS
    |--------------------------------------------------------------------------
    |
    | Maps roles to specific permissions with data scope rules
    |
    */
    'roles' => [

        'super_admin' => [
            'label' => 'Super Administrator',
            'description' => 'Full platform access across all agencies and employers',
            'data_scope' => 'platform', // Access to all data
            'permissions' => [
                'platform:*',
                'agency:*',
                'agent:*',
                'agency_employee:*',
                'employer_agency_contract:*',
                'shift_request:*',
                'agency_response:*',
                'assignment:*',
                'shift:*',
                'shift_offer:*',
                'timesheet:*',
                'invoice:*',
                'payroll:*',
                'payout:*',
                'availability:*',
                'time_off:*',
                'messaging:*',
                'reports:*',
            ],
            'restrictions' => []
        ],

        'agency_admin' => [
            'label' => 'Agency Administrator',
            'description' => 'Full access to agency operations and management',
            'data_scope' => 'agency', // Own agency only
            'permissions' => [
                'agency:view,update,manage_branches,view_financials,manage_subscription',
                'agent:view,create,update,delete,manage_permissions',
                'agency_employee:view,register,update_terms,update_status,view_financials,manage_availability',
                'employer_agency_contract:view,create,update,terminate,view_financials',
                'shift_request:view',
                'agency_response:view,create,update,withdraw',
                'assignment:view,create,update,cancel,view_financials',
                'shift:view,create,update,cancel,manage_templates',
                'shift_offer:view,create,update,cancel',
                'timesheet:view,agency_approve,dispute',
                'invoice:view,create,update,export',
                'payroll:view,create,update,process,export',
                'payout:view,create,process,reconcile',
                'availability:view,view_others',
                'time_off:view,approve',
                'messaging:send,view_conversations,manage_groups',
                'reports:view_utilization,view_financial,export_data',
            ],
            'restrictions' => [
                'agency_id' => 'own_agency', // Auto-scoped to user's agency
                'employer_access' => 'contracted_only', // Only employers with active contracts
            ]
        ],

        'agent' => [
            'label' => 'Agency Staff',
            'description' => 'Manage day-to-day operations and employee assignments',
            'data_scope' => 'agency_branch', // Own branch or assigned branches
            'permissions' => [
                'agency:view',
                'agency_employee:view,update_terms,update_status,manage_availability',
                'employer_agency_contract:view',
                'shift_request:view',
                'agency_response:view,create,update,withdraw',
                'assignment:view,create,update,cancel',
                'shift:view,create,update,cancel',
                'shift_offer:view,create,update,cancel',
                'timesheet:view,agency_approve',
                'invoice:view',
                'payroll:view,create',
                'availability:view,view_others',
                'time_off:view',
                'messaging:send,view_conversations',
                'reports:view_utilization',
            ],
            'restrictions' => [
                'agency_id' => 'own_agency',
                'branch_id' => 'assigned_branches', // Only assigned branches
                'financial_data' => 'limited', // No payment processing
            ]
        ],

        'employer_admin' => [
            'label' => 'Employer Administrator',
            'description' => 'Full access to employer operations and staffing management',
            'data_scope' => 'employer', // Own employer only
            'permissions' => [
                'shift_request:view,create,update,publish,cancel,view_responses',
                'agency_response:view,accept,reject',
                'assignment:view',
                'shift:view,approve',
                'timesheet:view,employer_approve',
                'invoice:view,pay',
                'messaging:send,view_conversations',
                'reports:view_utilization,view_financial',
            ],
            'restrictions' => [
                'employer_id' => 'own_employer',
                'agency_data' => 'contracted_only', // Only agencies with active contracts
                'employee_data' => 'assigned_only', // Only employees assigned to their shifts
            ]
        ],

        'contact' => [
            'label' => 'Employer Contact',
            'description' => 'On-site management of shifts and timesheet approval',
            'data_scope' => 'location', // Assigned locations only
            'permissions' => [
                'shift:view,approve',
                'timesheet:view,employer_approve',
                'messaging:send,view_conversations',
            ],
            'restrictions' => [
                'employer_id' => 'own_employer',
                'location_id' => 'assigned_locations', // Only assigned locations
                'financial_data' => 'none', // No access to financial information
            ]
        ],

        'employee' => [
            'label' => 'Employee',
            'description' => 'Self-service access to shifts, schedule, and personal information',
            'data_scope' => 'own_data', // Own data only
            'permissions' => [
                'employee_profile:view,update,manage_availability,view_schedule,view_payslips',
                'shift:view',
                'shift_offer:view,respond',
                'timesheet:view,create,update',
                'availability:view,manage',
                'time_off:view,request,cancel',
                'messaging:send,view_conversations',
            ],
            'restrictions' => [
                'user_id' => 'own_data', // Strictly own data only
                'agency_data' => 'registered_agencies', // Only agencies they're registered with
                'employer_data' => 'assigned_employers', // Only employers they're assigned to
                'financial_data' => 'own_payroll_only', // Only own payroll information
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | DATA SCOPING RULES
    |--------------------------------------------------------------------------
    |
    | Defines how data access is scoped based on user role and relationships
    |
    */
    'data_scoping' => [

        'platform' => [
            'description' => 'Access to all data across platform',
            'queries' => [
                // No additional WHERE clauses
            ]
        ],

        'agency' => [
            'description' => 'Access limited to user\'s agency and related data',
            'queries' => [
                'agency' => 'agencies.id = :user_agency_id',
                'agent' => 'agents.agency_id = :user_agency_id',
                'agency_employee' => 'agency_employees.agency_id = :user_agency_id',
                'employer_agency_contract' => 'employer_agency_contracts.agency_id = :user_agency_id',
                'assignment' => 'assignments.contract_id IN (SELECT id FROM employer_agency_contracts WHERE agency_id = :user_agency_id)',
                'shift' => 'shifts.assignment_id IN (SELECT assignments.id FROM assignments JOIN employer_agency_contracts ON assignments.contract_id = employer_agency_contracts.id WHERE employer_agency_contracts.agency_id = :user_agency_id)',
                'timesheet' => 'timesheets.shift_id IN (SELECT shifts.id FROM shifts JOIN assignments ON shifts.assignment_id = assignments.id JOIN employer_agency_contracts ON assignments.contract_id = employer_agency_contracts.id WHERE employer_agency_contracts.agency_id = :user_agency_id)',
            ]
        ],

        'agency_branch' => [
            'description' => 'Access limited to user\'s assigned branches',
            'queries' => [
                'agency_employee' => 'agency_employees.agency_branch_id IN (:user_branch_ids)',
                'assignment' => 'assignments.agency_branch_id IN (:user_branch_ids)',
                'shift' => 'shifts.assignment_id IN (SELECT id FROM assignments WHERE agency_branch_id IN (:user_branch_ids))',
            ]
        ],

        'employer' => [
            'description' => 'Access limited to user\'s employer and related data',
            'queries' => [
                'shift_request' => 'shift_requests.employer_id = :user_employer_id',
                'agency_response' => 'agency_responses.shift_request_id IN (SELECT id FROM shift_requests WHERE employer_id = :user_employer_id)',
                'assignment' => 'assignments.contract_id IN (SELECT id FROM employer_agency_contracts WHERE employer_id = :user_employer_id)',
                'shift' => 'shifts.assignment_id IN (SELECT assignments.id FROM assignments JOIN employer_agency_contracts ON assignments.contract_id = employer_agency_contracts.id WHERE employer_agency_contracts.employer_id = :user_employer_id)',
                'timesheet' => 'timesheets.shift_id IN (SELECT shifts.id FROM shifts JOIN assignments ON shifts.assignment_id = assignments.id JOIN employer_agency_contracts ON assignments.contract_id = employer_agency_contracts.id WHERE employer_agency_contracts.employer_id = :user_employer_id)',
            ]
        ],

        'location' => [
            'description' => 'Access limited to user\'s assigned locations',
            'queries' => [
                'shift' => 'shifts.location_id IN (:user_location_ids)',
                'timesheet' => 'timesheets.shift_id IN (SELECT id FROM shifts WHERE location_id IN (:user_location_ids))',
            ]
        ],

        'own_data' => [
            'description' => 'Access limited to user\'s own data',
            'queries' => [
                'employee_profile' => 'employees.user_id = :user_id',
                'shift' => 'shifts.assignment_id IN (SELECT assignments.id FROM assignments JOIN agency_employees ON assignments.agency_employee_id = agency_employees.id WHERE agency_employees.employee_id IN (SELECT id FROM employees WHERE user_id = :user_id))',
                'timesheet' => 'timesheets.shift_id IN (SELECT shifts.id FROM shifts JOIN assignments ON shifts.assignment_id = assignments.id JOIN agency_employees ON assignments.agency_employee_id = agency_employees.id WHERE agency_employees.employee_id IN (SELECT id FROM employees WHERE user_id = :user_id))',
                'availability' => 'employee_availabilities.employee_id IN (SELECT id FROM employees WHERE user_id = :user_id)',
                'time_off' => 'time_off_requests.employee_id IN (SELECT id FROM employees WHERE user_id = :user_id)',
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PERMISSION VALIDATION RULES
    |--------------------------------------------------------------------------
    |
    | Business rules for permission validation in specific contexts
    |
    */
    'validation_rules' => [

        'agency_response' => [
            'create' => [
                'rule' => 'User must be agent of agency with active contract for the shift_request employer',
                'validation_query' => "
                    SELECT COUNT(*) FROM employer_agency_contracts eac
                    JOIN agents a ON eac.agency_id = a.agency_id
                    WHERE eac.employer_id = (SELECT employer_id FROM shift_requests WHERE id = :shift_request_id)
                    AND eac.status = 'active'
                    AND a.user_id = :user_id
                "
            ],
            'accept' => [
                'rule' => 'User must be employer_admin of the shift_request employer',
                'validation_query' => "
                    SELECT COUNT(*) FROM shift_requests sr
                    JOIN employer_users eu ON sr.employer_id = eu.employer_id
                    WHERE sr.id = (SELECT shift_request_id FROM agency_responses WHERE id = :response_id)
                    AND eu.user_id = :user_id
                    AND eu.role = 'employer_admin'
                "
            ]
        ],

        'assignment' => [
            'create' => [
                'rule' => 'Assignment must be created from accepted agency_response with valid contract',
                'validation_query' => "
                    SELECT COUNT(*) FROM agency_responses ar
                    JOIN employer_agency_contracts eac ON ar.agency_id = eac.agency_id 
                    AND (SELECT employer_id FROM shift_requests WHERE id = ar.shift_request_id) = eac.employer_id
                    WHERE ar.id = :agency_response_id
                    AND ar.status = 'accepted'
                    AND eac.status = 'active'
                "
            ]
        ],

        'timesheet' => [
            'employer_approve' => [
                'rule' => 'User must be contact for the shift location with approval permissions',
                'validation_query' => "
                    SELECT COUNT(*) FROM timesheets t
                    JOIN shifts s ON t.shift_id = s.id
                    JOIN contacts c ON s.location_id = c.location_id
                    WHERE t.id = :timesheet_id
                    AND c.user_id = :user_id
                    AND c.can_approve_timesheets = true
                "
            ]
        ],

        'shift_offer' => [
            'respond' => [
                'rule' => 'User must be the employee being offered the shift',
                'validation_query' => "
                    SELECT COUNT(*) FROM shift_offers so
                    JOIN agency_employees ae ON so.agency_employee_id = ae.id
                    JOIN employees e ON ae.employee_id = e.id
                    WHERE so.id = :shift_offer_id
                    AND e.user_id = :user_id
                "
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ROLE TRANSITIONS & PROMOTIONS
    |--------------------------------------------------------------------------
    |
    | Rules for role changes and promotions within the system
    |
    */
    'role_transitions' => [

        'allowed_transitions' => [
            'employee' => ['agent'], // Employees can become agents (same user)
            'agent' => ['agency_admin'],
            'contact' => ['employer_admin'],
        ],

        'approval_required' => [
            'employee_to_agent' => [
                'approvers' => ['agency_admin'],
                'conditions' => ['agency_invitation' => true]
            ],
            'agent_to_agency_admin' => [
                'approvers' => ['agency_admin', 'super_admin'],
                'conditions' => ['min_tenure_days' => 90]
            ],
        ],

        'automatic_demotions' => [
            'agency_admin_to_agent' => [
                'trigger' => 'agency_ownership_transfer',
                'conditions' => ['new_admin_assigned' => true]
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PERMISSION CACHING
    |--------------------------------------------------------------------------
    */
    'caching' => [
        'enabled' => true,
        'duration' => 3600, // 1 hour
        'clear_on_role_change' => true,
        'clear_on_permission_update' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | AUDIT LOGGING FOR ACCESS CONTROL
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'log_permission_denials' => true,
        'log_role_changes' => true,
        'log_data_access' => false, // Can be enabled for sensitive operations
        'retention_days' => 365,
    ],

];