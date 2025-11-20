<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('invoices')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $invoices = [];
        $now = Carbon::now();

        for ($month = 1; $month <= 3; $month++) {
            $invoiceDate = $now->copy()->subMonths($month)->startOfMonth();
            $numInvoices = rand(10, 20);

            for ($i = 1; $i <= $numInvoices; $i++) {
                $type = ['employer_to_agency', 'agency_to_platform'][array_rand([0, 1])];
                list($fromType, $fromId, $toType, $toId, $agencyId) = $this->getInvoiceParties($type);

                $subtotal = rand(1500, 25000) + (rand(0, 99) / 100);
                $taxAmount = round($subtotal * 0.2, 2);
                $totalAmount = $subtotal + $taxAmount;

                $invoices[] = [
                    'agency_id' => $agencyId,
                    'type' => $type,
                    'from_type' => $fromType,
                    'from_id' => $fromId,
                    'to_type' => $toType,
                    'to_id' => $toId,
                    'reference' => 'INV-' . $invoiceDate->format('Ym') . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'line_items' => json_encode($this->generateInvoiceLineItems($subtotal)),
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'status' => $this->getInvoiceStatus($invoiceDate),
                    'due_date' => $invoiceDate->copy()->addDays(30)->format('Y-m-d'),
                    'paid_at' => $this->getPaidAtDate($invoiceDate),
                    'payment_reference' => $this->getPaymentReference(),
                    'metadata' => json_encode(['billing_period' => $invoiceDate->format('F Y')]),
                    'created_at' => $invoiceDate,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($invoices, 50) as $chunk) {
            DB::table('invoices')->insert($chunk);
        }

        $this->command->info('Created ' . count($invoices) . ' invoices');
        $this->debugInvoiceDistribution();
    }

    private function getInvoiceParties(string $type): array
    {
        if ($type === 'employer_to_agency') {
            $employerId = rand(1, 8);
            $agencyLink = DB::table('employer_agency_links')
                ->where('employer_id', $employerId)
                ->inRandomOrder()
                ->first();
            return [
                'employer',
                $employerId,
                'agency',
                $agencyLink->agency_id ?? 1,
                $agencyLink->agency_id ?? 1
            ];
        } else {
            $agencyId = rand(1, 5);
            return [
                'agency',
                $agencyId,
                'platform',
                1,
                $agencyId
            ];
        }
    }

    private function generateInvoiceLineItems(float $subtotal): array
    {
        return [
            [
                'description' => 'Temporary staffing services',
                'quantity' => 1,
                'unit_price' => $subtotal,
                'tax_rate' => 20.00,
                'total' => $subtotal
            ]
        ];
    }

    private function getInvoiceStatus(Carbon $invoiceDate): string
    {
        $daysSince = Carbon::now()->diffInDays($invoiceDate);
        if ($daysSince > 60) return 'overdue';
        if ($daysSince > 30) return rand(0, 10) > 6 ? 'overdue' : 'paid';
        return ['paid', 'paid', 'pending'][array_rand([0, 0, 1])];
    }

    private function getPaidAtDate(Carbon $invoiceDate): ?Carbon
    {
        return rand(0, 10) > 3 ? $invoiceDate->copy()->addDays(rand(5, 25)) : null;
    }

    private function getPaymentReference(): ?string
    {
        return rand(0, 10) > 4 ? 'PMT' . strtoupper(\Illuminate\Support\Str::random(10)) : null;
    }

    private function debugInvoiceDistribution(): void
    {
        $statusCounts = DB::table('invoices')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $this->command->info('Invoice status distribution:');
        foreach ($statusCounts as $count) {
            $this->command->info("  {$count->status}: {$count->count}");
        }

        $typeCounts = DB::table('invoices')
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();

        $this->command->info('Invoice type distribution:');
        foreach ($typeCounts as $count) {
            $this->command->info("  {$count->type}: {$count->count}");
        }

        $agencyCounts = DB::table('invoices')
            ->select('agency_id', DB::raw('count(*) as count'))
            ->groupBy('agency_id')
            ->get();

        $this->command->info('Invoices per agency:');
        foreach ($agencyCounts as $count) {
            $this->command->info("  Agency {$count->agency_id}: {$count->count} invoices");
        }

        $amountStats = DB::table('invoices')
            ->selectRaw('SUM(total_amount) as total, AVG(total_amount) as average')
            ->first();

        $this->command->info("Financial summary:");
        $this->command->info("  Total invoice amount: £" . number_format($amountStats->total, 2));
        $this->command->info("  Average invoice: £" . number_format($amountStats->average, 2));
    }
}
