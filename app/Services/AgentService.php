<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Collection;

class AgentService
{
    public function getAllAgents(): Collection
    {
        return Agent::all();
    }

    public function createAgent(array $data): Agent
    {
        return Agent::create($data);
    }

    public function getAgentById(string $id): ?Agent
    {
        return Agent::find($id);
    }

    public function updateAgent(Agent $agent, array $data): Agent
    {
        $agent->update($data);
        return $agent;
    }

    public function deleteAgent(Agent $agent): ?bool
    {
        return $agent->delete();
    }
}
