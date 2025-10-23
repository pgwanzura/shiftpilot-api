<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserManagement\CreateUserRequest;
use App\Http\Requests\UserManagement\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function __construct(
        private UserManagementService $userManagementService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $users = $this->userManagementService->getUsers($request->all());
        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Users retrieved successfully'
        ]);
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = $this->userManagementService->createUser($request->validated());
        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'message' => 'User created successfully'
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['agency', 'agent', 'employer', 'employee', 'contact', 'shiftOffersMade', 'timeOffApprovals', 'agencyApprovedTimesheets']);
        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'message' => 'User retrieved successfully'
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->userManagementService->updateUser($user, $request->validated());
        return response()->json([
            'success' => true,
            'data' => new UserResource($user->load(['agency', 'agent', 'employer', 'employee', 'contact'])),
            'message' => 'User updated successfully'
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userManagementService->deleteUser($user);
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'User deleted successfully'
        ]);
    }

    public function suspend(User $user): JsonResponse
    {
        $this->authorize('suspend', $user);
        $user = $this->userManagementService->suspendUser($user);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'message' => 'User suspended successfully'
        ]);
    }

    public function activate(User $user): JsonResponse
    {
        $this->authorize('activate', $user);
        $user = $this->userManagementService->activateUser($user);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'message' => 'User activated successfully'
        ]);
    }

    public function changeRole(User $user, Request $request): JsonResponse
    {
        $this->authorize('changeRole', $user);
        $request->validate([
            'role' => 'required|in:super_admin,agency_admin,agent,employer_admin,manager,contact,employee'
        ]);

        $user = $this->userManagementService->changeRole($user, $request->role);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'message' => 'User role changed successfully'
        ]);
    }

    public function resetPassword(User $user): JsonResponse
    {
        $this->authorize('resetPassword', $user);
        $user = $this->userManagementService->resetPassword($user);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
            'message' => 'Password reset successfully'
        ]);
    }
}
