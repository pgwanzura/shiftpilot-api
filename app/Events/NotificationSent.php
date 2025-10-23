<?php

// app/Events/NotificationSent.php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Notification $notification)
    {
    }
}
