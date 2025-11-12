<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuditLogResource;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\AuditLog\GetAuditLogsRequest;

class AuditLogController extends Controller
{
    private AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(GetAuditLogsRequest $request): JsonResponse
    {
        $auditLogs = $this->auditLogService->getAuditLogs($request->validated(), $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => AuditLogResource::collection($auditLogs),
            'meta' => [
                'total' => $auditLogs->total(),
                'per_page' => $auditLogs->perPage(),
                'current_page' => $auditLogs->currentPage(),
                'last_page' => $auditLogs->lastPage(),
            ],
            'message' => 'Audit logs retrieved successfully'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $auditLog = $this->auditLogService->getAuditLogById($id);

        if (!$auditLog) {
            return response()->json([
                'success' => false,
                'message' => 'Audit log not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AuditLogResource($auditLog),
            'message' => 'Audit log retrieved successfully'
        ]);
    }

    // Store, Update, and Destroy methods are not applicable for Audit Logs.
    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Audit logs cannot be created via API.'], 405);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Audit logs cannot be updated via API.'], 405);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Audit logs cannot be deleted via API.'], 405);
    }
}
