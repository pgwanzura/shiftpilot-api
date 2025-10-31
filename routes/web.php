<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/debug-session', function () {
    \Log::info('Session debug accessed', [
        'session_id' => session()->getId(),
        'session_data' => session()->all(),
        'cookies_received' => request()->cookies->all(),
        'headers' => request()->headers->all(),
    ]);

    // Set some session data
    session(['debug_test' => 'session_is_working_' . now()->format('H:i:s')]);

    $response = response()->json([
        'session' => [
            'id' => session()->getId(),
            'data' => session()->all(),
            'driver' => config('session.driver'),
        ],
        'cookies' => [
            'received' => request()->cookies->all(),
            'should_set' => [
                'name' => config('session.cookie'),
                'domain' => config('session.domain'),
                'secure' => config('session.secure'),
            ],
        ],
        'config' => [
            'session_domain' => config('session.domain'),
            'session_secure' => config('session.secure'),
            'sanctum_domains' => config('sanctum.stateful'),
        ],
    ]);

    \Log::info('Session debug response', [
        'response_cookies' => $response->headers->getCookies(),
    ]);

    return $response;
});

Route::get('/check-session', function () {
    return response()->json([
        'session_persists' => session()->all(),
        'debug_test_value' => session('debug_test', 'NOT_FOUND'),
        'session_id' => session()->getId(),
    ]);
});


// Add this route to check session configuration
Route::get('/session-config', function () {
    return response()->json([
        'session' => [
            'domain' => config('session.domain'),
            'secure' => config('session.secure'),
            'same_site' => config('session.same_site'),
            'cookie_name' => config('session.cookie'),
            'driver' => config('session.driver'),
        ],
        'env' => [
            'SESSION_DOMAIN' => env('SESSION_DOMAIN'),
            'SESSION_SAME_SITE' => env('SESSION_SAME_SITE'),
            'SESSION_SECURE_COOKIE' => env('SESSION_SECURE_COOKIE'),
            'SANCTUM_STATEFUL_DOMAINS' => env('SANCTUM_STATEFUL_DOMAINS'),
        ],
        'cors' => [
            'supports_credentials' => config('cors.supports_credentials'),
        ]
    ]);
});

// Your existing debug route
Route::get('/debug-session', function () {
    \Log::info('Session debug accessed', [
        'session_id' => session()->getId(),
        'session_data' => session()->all(),
        'cookies_received' => request()->cookies->all(),
        'headers' => request()->headers->all(),
    ]);

    session(['debug_test' => 'session_is_working_' . now()->format('H:i:s')]);

    return response()->json([
        'session' => [
            'id' => session()->getId(),
            'data' => session()->all(),
            'driver' => config('session.driver'),
        ],
        'cookies' => [
            'received' => request()->cookies->all(),
            'should_set' => [
                'name' => config('session.cookie'),
                'domain' => config('session.domain'),
                'secure' => config('session.secure'),
                'same_site' => config('session.same_site'),
            ],
        ],
        'config' => [
            'session_domain' => config('session.domain'),
            'session_secure' => config('session.secure'),
            'session_same_site' => config('session.same_site'),
            'sanctum_domains' => config('sanctum.stateful'),
        ],
    ]);
});


require __DIR__ . '/auth.php';
