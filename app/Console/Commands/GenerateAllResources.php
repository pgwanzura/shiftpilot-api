<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateAllResources extends Command
{
    protected $signature = 'make:all-resources';
    protected $description = 'Generate all API Resource classes';

    protected $resources = [
        // Core Business Entities
        'UserResource',
        'AgencyResource',
        'EmployerResource',
        'EmployeeResource',
        'LocationResource',
        'ContactResource',
        'ShiftResource',
        'RateCardResource',

        // Workflow Entities
        'ShiftOfferResource',
        'TimeOffRequestResource',
        'EmployeeAvailabilityResource',
        'ShiftTemplateResource',

        // Relationship Entities
        'EmployerAgencyLinkResource',
        'PlacementResource',
        'ShiftApprovalResource',

        // Financial Entities (Read-only)
        'InvoiceResource',
        'PaymentResource',
        'PayrollResource',
        'PayoutResource',

        // System Entities
        'AuditLogResource',
        'PlatformBillingResource',
        'SubscriptionResource',

        // Notification/Webhook Entities
        'NotificationResource',
        'WebhookSubscriptionResource',
    ];

    public function handle()
    {
        foreach ($this->resources as $resource) {
            $this->call('make:resource', ['name' => $resource]);
            $this->info("Created: {$resource}");
        }

        $this->info('All resource classes generated!');
        $this->line('');
        $this->info('Next steps:');
        $this->line('1. Update each resource with proper toArray() method');
        $this->line('2. Include relationships where needed');
        $this->line('3. Add any custom formatting or computed attributes');
    }
}
