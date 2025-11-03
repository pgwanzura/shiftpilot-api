<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PlacementCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        $total = $this->total();
        if (is_array($total)) {
            $total = $total[0] ?? 0;
        }

        return [
            'data' => PlacementResource::collection($this->collection),
            'meta' => [
                'current_page' => $this->currentPage(),
                'per_page' => $this->perPage(),
                'total' => (int) $total,
                'total_pages' => $this->lastPage(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
        ];
    }

    public function with(Request $request): array
    {
        return [
            'status' => 'success',
            'message' => 'Placements retrieved successfully',
        ];
    }
}
