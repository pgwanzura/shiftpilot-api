<?php

namespace App\Http\Controllers;

use App\Http\Requests\SystemNotification\CreateSystemNotificationRequest;
use App\Http\Requests\SystemNotification\UpdateSystemNotificationRequest;
use App\Http\Resources\SystemNotificationCollection;
use App\Http\Resources\SystemNotificationResource;
use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemNotificationController extends Controller
{
    public function index(Request $request): SystemNotificationCollection
    {
        $query = SystemNotification::forUser(Auth::id())
            ->with('user')
            ->latest();

        if ($request->has('unread_only')) {
            $query->unread();
        }

        if ($request->has('channel')) {
            $query->forChannel($request->channel);
        }

        $notifications = $query->paginate($request->get('per_page', 15));

        return new SystemNotificationCollection($notifications);
    }

    public function store(CreateSystemNotificationRequest $request): SystemNotificationResource
    {
        $notification = SystemNotification::create($request->validated());

        return new SystemNotificationResource($notification->load('user'));
    }

    public function show(SystemNotification $notification): SystemNotificationResource
    {
        $this->authorize('view', $notification);

        return new SystemNotificationResource($notification->load('user'));
    }

    public function update(UpdateSystemNotificationRequest $request, SystemNotification $notification): SystemNotificationResource
    {
        $notification->update($request->validated());

        return new SystemNotificationResource($notification->load('user'));
    }

    public function destroy(SystemNotification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully']);
    }

    public function markAsRead(SystemNotification $notification): SystemNotificationResource
    {
        $this->authorize('markAsRead', $notification);

        $notification->markAsRead();

        return new SystemNotificationResource($notification);
    }

    public function markAllAsRead(): JsonResponse
    {
        SystemNotification::forUser(Auth::id())
            ->unread()
            ->update(['is_read' => true]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function getUnreadCount(): JsonResponse
    {
        $count = SystemNotification::forUser(Auth::id())
            ->unread()
            ->count();

        return response()->json(['unread_count' => $count]);
    }
}
