<?php

use App\Http\Controllers\{
    UserController,
};

use Illuminate\Support\Facades\Route;



Route::get('/healthcheck', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'ShiftPilot API',
        'timestamp' => now()->toDateTimeString(),
        'version' => '1.0.0'
    ]);
});
