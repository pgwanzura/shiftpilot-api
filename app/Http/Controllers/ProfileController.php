<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    private ProfileService $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function show(Request $request): JsonResponse
    {
        $profile = $this->profileService->getUserProfile($request->user());

        return response()->json([
            'success' => true,
            'data' => new ProfileResource($profile),
            'message' => 'Profile retrieved successfully'
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $profile = $this->profileService->updateProfile($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'data' => new ProfileResource($profile),
            'message' => 'Profile updated successfully'
        ]);
    }
}
