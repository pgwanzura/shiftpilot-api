<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShiftTemplate\CreateShiftTemplateRequest;
use App\Http\Requests\ShiftTemplate\UpdateShiftTemplateRequest;
use App\Http\Resources\ShiftTemplateResource;
use App\Models\ShiftTemplate;
use App\Services\ShiftTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftTemplateController extends Controller
{
    public function __construct(
        private ShiftTemplateService $shiftTemplateService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $templates = $this->shiftTemplateService->getShiftTemplates($request->all());
        return response()->json([
            'success' => true,
            'data' => $templates,
            'message' => 'Shift templates retrieved successfully'
        ]);
    }

    public function store(CreateShiftTemplateRequest $request): JsonResponse
    {
        $template = $this->shiftTemplateService->createShiftTemplate($request->validated());
        return response()->json([
            'success' => true,
            'data' => new ShiftTemplateResource($template),
            'message' => 'Shift template created successfully'
        ]);
    }

    public function show(ShiftTemplate $template): JsonResponse
    {
        $template->load(['employer', 'location', 'shifts']);
        return response()->json([
            'success' => true,
            'data' => new ShiftTemplateResource($template),
            'message' => 'Shift template retrieved successfully'
        ]);
    }

    public function update(UpdateShiftTemplateRequest $request, ShiftTemplate $template): JsonResponse
    {
        $template = $this->shiftTemplateService->updateShiftTemplate($template, $request->validated());
        return response()->json([
            'success' => true,
            'data' => new ShiftTemplateResource($template),
            'message' => 'Shift template updated successfully'
        ]);
    }

    public function destroy(ShiftTemplate $template): JsonResponse
    {
        $this->shiftTemplateService->deleteShiftTemplate($template);
        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Shift template deleted successfully'
        ]);
    }

    public function generateShifts(ShiftTemplate $template, Request $request): JsonResponse
    {
        $this->authorize('use', $template);
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $shifts = $this->shiftTemplateService->generateShiftsFromTemplate($template, $request->all());

        return response()->json([
            'success' => true,
            'data' => $shifts,
            'message' => 'Shifts generated from template successfully'
        ]);
    }

    public function deactivate(ShiftTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);
        $template = $this->shiftTemplateService->deactivateTemplate($template);

        return response()->json([
            'success' => true,
            'data' => new ShiftTemplateResource($template),
            'message' => 'Shift template deactivated successfully'
        ]);
    }

    public function activate(ShiftTemplate $template): JsonResponse
    {
        $this->authorize('update', $template);
        $template = $this->shiftTemplateService->activateTemplate($template);

        return response()->json([
            'success' => true,
            'data' => new ShiftTemplateResource($template),
            'message' => 'Shift template activated successfully'
        ]);
    }
}
