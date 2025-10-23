<?php

namespace App\Events;

use App\Models\PaymentLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentLogged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public PaymentLog $paymentLog)
    {
    }
}
