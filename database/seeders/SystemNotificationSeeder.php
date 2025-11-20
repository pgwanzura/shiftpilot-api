<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SystemNotificationSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('system_notifications')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $notifications = [];
        $now = Carbon::now();

        $users = DB::table('users')->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        $notificationTemplates = [
            [
                'template_key' => 'shift_assigned',
                'title' => 'New Shift Assigned',
                'message' => 'You have been assigned to a new shift starting {date}',
                'priority' => 'medium'
            ],
            [
                'template_key' => 'timesheet_approved',
                'title' => 'Timesheet Approved',
                'message' => 'Your timesheet for {period} has been approved',
                'priority' => 'low'
            ],
            [
                'template_key' => 'payment_processed',
                'title' => 'Payment Processed',
                'message' => 'Your payment of {amount} has been processed',
                'priority' => 'medium'
            ],
            [
                'template_key' => 'shift_reminder',
                'title' => 'Shift Reminder',
                'message' => 'Reminder: You have a shift starting in {hours} hours',
                'priority' => 'high'
            ],
            [
                'template_key' => 'system_update',
                'title' => 'System Update',
                'message' => 'New features are now available in the platform',
                'priority' => 'low'
            ],
            [
                'template_key' => 'profile_required',
                'title' => 'Profile Update Required',
                'message' => 'Please update your profile information to continue',
                'priority' => 'medium'
            ],
            [
                'template_key' => 'availability_request',
                'title' => 'Availability Request',
                'message' => 'New shifts available for your preferred locations',
                'priority' => 'medium'
            ]
        ];

        $channels = ['in_app', 'email', 'sms'];

        foreach ($users as $user) {
            $notificationCount = rand(1, 8);

            for ($i = 0; $i < $notificationCount; $i++) {
                $template = $notificationTemplates[array_rand($notificationTemplates)];
                $sentAt = $now->copy()->subDays(rand(0, 60))->subHours(rand(1, 12));

                $notifications[] = [
                    'user_id' => $user->id,
                    'template_key' => $template['template_key'],
                    'channel' => $channels[array_rand($channels)],
                    'payload' => json_encode([
                        'title' => $template['title'],
                        'message' => $this->replaceTemplatePlaceholders($template['message']),
                        'priority' => $template['priority'],
                        'action_url' => $this->getActionUrl($template['template_key'])
                    ]),
                    'is_read' => rand(0, 1),
                    'sent_at' => $sentAt,
                    'created_at' => $sentAt,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($notifications, 100) as $chunk) {
            DB::table('system_notifications')->insert($chunk);
        }

        $this->command->info('Created ' . count($notifications) . ' system notifications');
        $this->debugNotificationDistribution();
    }

    private function replaceTemplatePlaceholders(string $message): string
    {
        $replacements = [
            '{date}' => Carbon::now()->addDays(rand(1, 7))->format('M j, Y'),
            '{period}' => Carbon::now()->subDays(7)->format('M j') . ' - ' . Carbon::now()->format('M j, Y'),
            '{amount}' => '£' . number_format(rand(5000, 25000) / 100, 2),
            '{hours}' => rand(1, 24)
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    private function getActionUrl(string $templateKey): string
    {
        $urls = [
            'shift_assigned' => '/shifts',
            'timesheet_approved' => '/timesheets',
            'payment_processed' => '/payments',
            'shift_reminder' => '/schedule',
            'system_update' => '/settings',
            'profile_required' => '/profile',
            'availability_request' => '/availability'
        ];

        return $urls[$templateKey] ?? '/dashboard';
    }

    private function debugNotificationDistribution(): void
    {
        $userStats = DB::table('system_notifications')
            ->select('user_id', DB::raw('count(*) as notification_count'))
            ->groupBy('user_id')
            ->get();

        $this->command->info('Notifications per user:');
        foreach ($userStats as $stat) {
            $this->command->info("  User {$stat->user_id}: {$stat->notification_count} notifications");
        }

        $channelCounts = DB::table('system_notifications')
            ->select('channel', DB::raw('count(*) as count'))
            ->groupBy('channel')
            ->get();

        $this->command->info('Notification channel distribution:');
        foreach ($channelCounts as $count) {
            $this->command->info("  {$count->channel}: {$count->count}");
        }

        $templateCounts = DB::table('system_notifications')
            ->select('template_key', DB::raw('count(*) as count'))
            ->groupBy('template_key')
            ->get();

        $this->command->info('Template distribution:');
        foreach ($templateCounts as $count) {
            $this->command->info("  {$count->template_key}: {$count->count}");
        }

        $readStats = DB::table('system_notifications')
            ->selectRaw('COUNT(*) as total, SUM(is_read) as read_count')
            ->first();

        $readPercentage = $readStats->total > 0 ? round(($readStats->read_count / $readStats->total) * 100, 1) : 0;
        $this->command->info("Read status: {$readStats->read_count}/{$readStats->total} ({$readPercentage}%)");

        $recentNotifications = DB::table('system_notifications')
            ->where('sent_at', '>=', Carbon::now()->subDays(7))
            ->count();

        $this->command->info("Recent notifications (last 7 days): {$recentNotifications}");
    }
}