<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuditLogSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('audit_logs')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $logs = [];
        $now = Carbon::now();

        $actions = [
            'user.login',
            'user.logout',
            'shift.created',
            'shift.updated',
            'shift.deleted',
            'timesheet.submitted',
            'timesheet.approved',
            'timesheet.rejected',
            'invoice.generated',
            'payment.processed',
            'employee.registered'
        ];

        $userTypes = ['user', 'system'];
        $targetTypes = ['user', 'shift', 'timesheet', 'invoice', 'employee'];

        for ($i = 1; $i <= 200; $i++) {
            $action = $actions[array_rand($actions)];
            $actorType = $userTypes[array_rand($userTypes)];

            $logs[] = [
                'actor_type' => $actorType,
                'actor_id' => $actorType === 'user' ? rand(1, 161) : null,
                'action' => $action,
                'target_type' => rand(0, 10) > 2 ? $targetTypes[array_rand($targetTypes)] : null,
                'target_id' => rand(0, 10) > 2 ? rand(1, 100) : null,
                'payload' => json_encode([
                    'ip' => '192.168.1.' . rand(1, 255),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'additional_data' => ['reason' => 'automatic_action']
                ]),
                'ip_address' => '192.168.1.' . rand(1, 255),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => $now->copy()->subDays(rand(1, 90))->subHours(rand(1, 23)),
            ];
        }

        DB::table('audit_logs')->insert($logs);
        $this->command->info('Created ' . count($logs) . ' audit logs');
    }
}
