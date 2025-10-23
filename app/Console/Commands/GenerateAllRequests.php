<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateAllRequests extends Command
{
    protected $signature = 'make:all-requests';
    protected $description = 'Generate all Form Request classes';

    protected $requests = [
        'UserRequest',
        'AgencyRequest',
        'EmployerRequest',
        'EmployeeRequest',
        'LocationRequest',
        'ContactRequest',
        'ShiftRequest',
        'RateCardRequest',
        'ShiftOfferRequest',
        'TimeOffRequestRequest',
        'EmployeeAvailabilityRequest',
        'ShiftTemplateRequest',
        'EmployerAgencyLinkRequest',
        'PlacementRequest',
        'ShiftApprovalRequest',
        'PlatformBillingRequest',
        'SubscriptionRequest',
        'WebhookSubscriptionRequest',
    ];

    public function handle()
    {
        foreach ($this->requests as $request) {
            $this->call('make:request', ['name' => $request]);
            $this->info("Created: {$request}");
        }

        $this->info('All request classes generated!');
    }
}
