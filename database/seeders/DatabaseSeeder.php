<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DatabaseSeeder extends Seeder
{
    private array $seeders = [
        'core_entities' => [
            UserSeeder::class,
            AgencySeeder::class,
            EmployerSeeder::class,
            LocationSeeder::class,
        ],
        'agency_structure' => [
            AgencyBranchSeeder::class,
        ],
        'contracts_relationships' => [
            EmployerAgencyLinkSeeder::class,
            EmployerAgencyContractSeeder::class,
        ],
        'employees' => [
            EmployeeSeeder::class,
            AgencyEmployeeSeeder::class,
            EmployeePreferenceSeeder::class,
            EmployeeAvailabilitySeeder::class,
        ],
        'user_roles' => [
            AgentSeeder::class,
            ContactSeeder::class,
        ],
        'assignments' => [
            AssignmentSeeder::class,
            AgencyAssignmentResponseSeeder::class,
        ],
        'shifts_templates' => [
            ShiftTemplateSeeder::class,
        ],
        'shifts' => [
            ShiftSeeder::class,
            ShiftOfferSeeder::class,
            TimeOffRequestSeeder::class,
        ],
        'time_tracking' => [
            TimesheetSeeder::class,
            ShiftApprovalSeeder::class,
        ],
        'billing_payments' => [
            PricePlanSeeder::class,
            SubscriptionSeeder::class,
            PlatformBillingSeeder::class,
            InvoiceSeeder::class,
            PaymentSeeder::class,
            PayrollSeeder::class,
            PayoutSeeder::class,
        ],
        'communication' => [
            ConversationSeeder::class,
            ConversationParticipantSeeder::class,
            MessageSeeder::class,
            MessageRecipientSeeder::class,
        ],
        'notifications_logging' => [
            AuditLogSeeder::class,
            SystemNotificationSeeder::class,
            WebhookSubscriptionSeeder::class,
        ],
    ];

    public function run(): void
    {
        // Disable foreign key checks
        Schema::disableForeignKeyConstraints();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Truncate all tables in correct order (child tables first)
            $this->truncateAllTables();

            foreach ($this->seeders as $category => $seederClasses) {
                $this->command->info("=== Seeding {$category} ===");

                foreach ($seederClasses as $seederClass) {
                    if (class_exists($seederClass)) {
                        $this->command->info("Running {$seederClass}...");
                        $this->call($seederClass);
                        $this->command->info("✓ Completed {$seederClass}");
                    } else {
                        $this->command->error("✗ Seeder class {$seederClass} not found.");
                    }
                }
            }

            $this->command->info('🎉 Database seeding completed successfully!');
        } catch (Throwable $e) {
            $this->command->error('💥 Database seeding failed: ' . $e->getMessage());
            $this->command->error('📄 Error details: ' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        } finally {
            // Re-enable foreign key checks
            Schema::enableForeignKeyConstraints();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function truncateAllTables(): void
    {
        $tables = [
            // Child tables first (those with foreign keys)
            'message_recipients',
            'messages',
            'conversation_participants',
            'conversations',
            'payouts',
            'payroll',
            'payments',
            'invoices',
            'platform_billings',
            'subscriptions',
            'shift_approvals',
            'timesheets',
            'time_off_requests',
            'shift_offers',
            'shifts',
            'shift_templates',
            'agency_assignment_responses',
            'assignments',
            'employee_availabilities',
            'employee_preferences',
            'agency_employees',
            'contacts',
            'agents',
            'employees',
            'employer_agency_contracts',
            'employer_agency_links',
            'agency_branches',

            // Parent tables last (those referenced by foreign keys)
            'locations',
            'employers',
            'agencies',
            'users',
            'price_plans',
            'webhook_subscriptions',
            'system_notifications',
            'audit_logs',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
                $this->command->info("Truncated table: {$table}");
            }
        }
    }
}
