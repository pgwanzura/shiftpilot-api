<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateAllEntities extends Command
{
    protected $signature = 'make:all-entities';
    protected $description = 'Generate appropriate resources for ALL entities automatically';

    protected $entityResources = [
        // Full CRUD entities - generate everything
        'full_crud' => [
            'user', 'agency', 'employer', 'employee', 'location',
            'contact', 'shift', 'rate_card', 'webhook_subscription'
        ],

        // CRU entities - no delete functionality
        'cru' => [
            'shift_offer', 'time_off_request', 'shift_approval',
            'employer_agency_link', 'placement', 'subscription'
        ],

        // Read/Update entities - system creates, users can update
        'ru' => [
            'timesheet', 'payroll', 'payout', 'employee_availability'
        ],

        // Read-only entities - immutable records
        'ro' => [
            'invoice', 'payment', 'audit_log'
        ],

        // System entities - minimal access
        'system' => [
            'notification', 'platform_billing', 'shift_template'
        ]
    ];

    public function handle()
    {
        $schema = config('schema');

        if (!$schema || !isset($schema['entities'])) {
            $this->error('No entities found in config/schema.php');
            return 1;
        }

        $entities = array_keys($schema['entities']);
        $this->info("Generating appropriate resources for " . count($entities) . " entities...");

        foreach ($entities as $entity) {
            $this->generateAppropriateResources($entity);
        }

        $this->info("✅ All done! Generated resources for " . count($entities) . " entities.");
        return 0;
    }

    protected function generateAppropriateResources($entityName)
    {
        $modelName = Str::studly(Str::singular($entityName));
        $resourceType = $this->getResourceType($entityName);

        $this->info("Creating {$resourceType} resources for: {$modelName}");

        // Always generate migration and model
        $this->generateMigration($entityName);
        $this->generateModel($modelName);

        // Generate additional resources based on type
        switch ($resourceType) {
            case 'full_crud':
                $this->generateFullCrudResources($modelName);
                break;
            case 'cru':
                $this->generateCruResources($modelName);
                break;
            case 'ru':
                $this->generateRuResources($modelName);
                break;
            case 'ro':
                $this->generateRoResources($modelName);
                break;
            case 'system':
                $this->generateSystemResources($modelName);
                break;
        }
    }

    protected function getResourceType($entityName)
    {
        foreach ($this->entityResources as $type => $entities) {
            if (in_array($entityName, $entities)) {
                return $type;
            }
        }
        return 'full_crud'; // Default fallback
    }

    protected function generateMigration($entityName)
    {
        $tableName = Str::snake(Str::plural($entityName));
        $this->callSilent('make:migration', [
            'name' => "create_{$tableName}_table",
            '--create' => $tableName,
        ]);
    }

    protected function generateModel($modelName)
    {
        $this->callSilent('make:model', [
            'name' => $modelName,
        ]);
    }

    protected function generateFullCrudResources($modelName)
    {
        // Full CRUD - generate everything
        $this->callSilent('make:controller', [
            'name' => "{$modelName}Controller",
            '--model' => $modelName,
            '--api' => true,
        ]);

        $this->callSilent('make:resource', [
            'name' => "{$modelName}Resource",
        ]);

        $this->callSilent('make:policy', [
            'name' => "{$modelName}Policy",
            '--model' => $modelName,
        ]);

        $this->callSilent('make:factory', [
            'name' => "{$modelName}Factory",
            '--model' => $modelName,
        ]);
    }

    protected function generateCruResources($modelName)
    {
        // CRU - no delete functionality
        $this->callSilent('make:controller', [
            'name' => "{$modelName}Controller",
            '--api' => true,
        ]);

        $this->callSilent('make:resource', [
            'name' => "{$modelName}Resource",
        ]);

        $this->callSilent('make:policy', [
            'name' => "{$modelName}Policy",
            '--model' => $modelName,
        ]);

        $this->callSilent('make:factory', [
            'name' => "{$modelName}Factory",
            '--model' => $modelName,
        ]);
    }

    protected function generateRuResources($modelName)
    {
        // Read/Update - no create/delete from users
        $this->callSilent('make:controller', [
            'name' => "{$modelName}Controller",
            '--api' => true,
        ]);

        $this->callSilent('make:resource', [
            'name' => "{$modelName}Resource",
        ]);

        $this->callSilent('make:policy', [
            'name' => "{$modelName}Policy",
            '--model' => $modelName,
        ]);
        // No factory - system generates these
    }

    protected function generateRoResources($modelName)
    {
        // Read-only - immutable records
        $this->callSilent('make:controller', [
            'name' => "{$modelName}Controller",
            '--api' => true,
        ]);

        $this->callSilent('make:resource', [
            'name' => "{$modelName}Resource",
        ]);

        $this->callSilent('make:policy', [
            'name' => "{$modelName}Policy",
            '--model' => $modelName,
        ]);
        // No factory - system generates these
    }

    protected function generateSystemResources($modelName)
    {
        // System entities - minimal resources
        $this->callSilent('make:controller', [
            'name' => "{$modelName}Controller",
            '--api' => true,
        ]);

        $this->callSilent('make:policy', [
            'name' => "{$modelName}Policy",
            '--model' => $modelName,
        ]);
        // No resource, no factory - system managed
    }
}
