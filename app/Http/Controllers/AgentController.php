<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Http\Requests\Agent\StoreAgentRequest;
use App\Http\Requests\Agent\UpdateAgentRequest;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    protected $agentService;

    public function __construct(AgentService $agentService)
    {
        $this->agentService = $agentService;
    }

    public function index(): JsonResponse
    {
        $agents = $this->agentService->getAllAgents();
        return response()->json($agents);
    }

    public function store(StoreAgentRequest $request): JsonResponse
    {
        $agent = $this->agentService->createAgent($request->validated());
        return response()->json($agent, 201);
    }

    public function show(Agent $agent): JsonResponse
    {
        return response()->json($agent);
    }

    public function update(UpdateAgentRequest $request, Agent $agent): JsonResponse
    {
        $agent = $this->agentService->updateAgent($agent, $request->validated());
        return response()->json($agent);
    }

    public function destroy(Agent $agent): JsonResponse
    {
        $this->agentService->deleteAgent($agent);
        return response()->json(null, 204);
    }
}
