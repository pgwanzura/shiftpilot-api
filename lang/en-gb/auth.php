<?php

// lang/en/auth.php
return [
    'registration_success' => 'Registration successful. Please check your email for verification.',
    'login_success' => 'Welcome back!',
    'logout_success' => 'Successfully logged out.',
];

// lang/en/shifts.php
return [
    'created_success' => 'Shift created successfully.',
    'updated_success' => 'Shift updated successfully.',
    'cancelled_success' => 'Shift cancelled successfully.',
    'validation' => [
        'overlap' => 'This shift overlaps with an existing shift at this location.',
        'insufficient_notice' => 'Shifts must be created at least 24 hours in advance.',
    ],
];

// lang/en/timesheet.php
return [
    'clock_in_success' => 'Clocked in successfully.',
    'clock_out_success' => 'Clocked out successfully.',
    'already_clocked_in' => 'You are already clocked in.',
    'not_clocked_in' => 'You must clock in before clocking out.',
    'approved_success' => 'Timesheet approved successfully.',
];
