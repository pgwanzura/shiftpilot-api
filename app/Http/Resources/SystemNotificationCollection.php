<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SystemNotificationCollection extends ResourceCollection
{
    public $collects = SystemNotificationResource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'unread_count' => $this->collection->where('is_read', false)->count(),
            ],
        ];
    }
}
