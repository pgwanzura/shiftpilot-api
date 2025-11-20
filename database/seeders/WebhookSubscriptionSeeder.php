<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WebhookSubscriptionSeeder extends Seeder
{
    public function run()
    {
        DB::table('webhook_subscriptions')->truncate();

        $subscriptions = [];
        $now = Carbon::now();

        $agencies = DB::table('agencies')->get();
        $employers = DB::table('employers')->get();

        $webhookEvents = [
            'shift.created',
            'shift.updated',
            'shift.cancelled',
            'timesheet.submitted',
            'timesheet.approved',
            'timesheet.rejected',
            'payment.processed',
            'invoice.created',
            'invoice.paid',
            'employee.registered',
            'employee.updated',
            'assignment.created',
            'assignment.completed'
        ];

        $statuses = ['active', 'inactive', 'paused'];

        // Agency webhooks
        foreach ($agencies as $agency) {
            $subscriptionCount = rand(1, 4);

            for ($i = 0; $i < $subscriptionCount; $i++) {
                $events = array_rand($webhookEvents, rand(2, 6));
                if (!is_array($events)) {
                    $events = [$events];
                }

                $selectedEvents = array_map(function ($index) use ($webhookEvents) {
                    return $webhookEvents[$index];
                }, $events);

                $subscriptions[] = [
                    'owner_type' => 'agency',
                    'owner_id' => $agency->id,
                    'url' => 'https://webhook.agency' . $agency->id . '.com/' . \Illuminate\Support\Str::random(10),
                    'events' => json_encode($selectedEvents),
                    'secret' => \Illuminate\Support\Str::random(32),
                    'status' => $statuses[array_rand($statuses)],
                    'last_delivery_at' => rand(0, 10) > 3 ? $now->copy()->subDays(rand(1, 30)) : null,
                    'created_at' => $now->copy()->subDays(rand(1, 90)),
                    'updated_at' => $now,
                ];
            }
        }

        // Employer webhooks
        foreach ($employers as $employer) {
            $subscriptionCount = rand(1, 3);

            for ($i = 0; $i < $subscriptionCount; $i++) {
                $events = array_rand($webhookEvents, rand(1, 4));
                if (!is_array($events)) {
                    $events = [$events];
                }

                $selectedEvents = array_map(function ($index) use ($webhookEvents) {
                    return $webhookEvents[$index];
                }, $events);

                $subscriptions[] = [
                    'owner_type' => 'employer',
                    'owner_id' => $employer->id,
                    'url' => 'https://webhook.employer' . $employer->id . '.com/' . \Illuminate\Support\Str::random(10),
                    'events' => json_encode($selectedEvents),
                    'secret' => \Illuminate\Support\Str::random(32),
                    'status' => $statuses[array_rand($statuses)],
                    'last_delivery_at' => rand(0, 10) > 5 ? $now->copy()->subDays(rand(1, 30)) : null,
                    'created_at' => $now->copy()->subDays(rand(1, 90)),
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($subscriptions, 50) as $chunk) {
            DB::table('webhook_subscriptions')->insert($chunk);
        }

        $this->command->info('Created ' . count($subscriptions) . ' webhook subscriptions');
        $this->debugWebhookDistribution();
    }

    private function debugWebhookDistribution(): void
    {
        $typeCounts = DB::table('webhook_subscriptions')
            ->select('owner_type', DB::raw('count(*) as count'))
            ->groupBy('owner_type')
            ->get();

        $this->command->info('Webhook subscription distribution:');
        foreach ($typeCounts as $count) {
            $this->command->info("  {$count->owner_type}: {$count->count}");
        }

        $statusCounts = DB::table('webhook_subscriptions')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $this->command->info('Status distribution:');
        foreach ($statusCounts as $count) {
            $this->command->info("  {$count->status}: {$count->count}");
        }

        $deliveryStats = DB::table('webhook_subscriptions')
            ->selectRaw('COUNT(last_delivery_at) as delivered_count, COUNT(*) as total_count')
            ->first();

        $deliveryPercentage = $deliveryStats->total_count > 0 ?
            round(($deliveryStats->delivered_count / $deliveryStats->total_count) * 100, 1) : 0;

        $this->command->info("Delivery statistics:");
        $this->command->info("  Has deliveries: {$deliveryStats->delivered_count}/{$deliveryStats->total_count} ({$deliveryPercentage}%)");

        $eventStats = DB::table('webhook_subscriptions')
            ->get()
            ->flatMap(function ($subscription) {
                return json_decode($subscription->events, true) ?? [];
            })
            ->countBy()
            ->sortDesc()
            ->take(5);

        $this->command->info('Top 5 webhook events:');
        foreach ($eventStats as $event => $count) {
            $this->command->info("  {$event}: {$count}");
        }
    }
}
