<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;

class AuditLogListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(object $event)
    {
        // We need a more sophisticated way to map events to audit log entries.
        // For now, this is a placeholder.

        $actorType = null;
        $actorId = null;
        $action = class_basename($event);
        $targetType = null;
        $targetId = null;
        $payload = method_exists($event, 'toArray') ? $event->toArray() : (array) $event;

        if (Auth::check()) {
            $user = Auth::user();
            $actorType = class_basename($user);
            $actorId = $user->id;
        }

        // Example mapping for some events (this would need to be comprehensive)
        if (property_exists($event, 'shiftRequest') && $event->shiftRequest) {
            $targetType = class_basename($event->shiftRequest);
            $targetId = $event->shiftRequest->id;
        } elseif (property_exists($event, 'assignment') && $event->assignment) {
            $targetType = class_basename($event->assignment);
            $targetId = $event->assignment->id;
        } elseif (property_exists($event, 'timeOffRequest') && $event->timeOffRequest) {
            $targetType = class_basename($event->timeOffRequest);
            $targetId = $event->timeOffRequest->id;
        }

        AuditLog::create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'payload' => $payload,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
