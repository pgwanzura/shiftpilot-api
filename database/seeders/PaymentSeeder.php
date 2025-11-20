<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('payments')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $payments = [];
        $now = Carbon::now();

        $paidInvoices = DB::table('invoices')
            ->whereNotNull('paid_at')
            ->get();

        foreach ($paidInvoices as $invoice) {
            $method = ['stripe', 'bacs', 'direct_debit'][array_rand([0, 1, 2])];
            $feeAmount = $invoice->total_amount * 0.029 + 0.30;
            $netAmount = $invoice->total_amount - $feeAmount;

            $payments[] = [
                'invoice_id' => $invoice->id,
                'payer_type' => $invoice->from_type,
                'payer_id' => $invoice->from_id,
                'amount' => $invoice->total_amount,
                'method' => $method,
                'processor_id' => 'pay_' . strtoupper(\Illuminate\Support\Str::random(14)),
                'status' => 'completed',
                'fee_amount' => $feeAmount,
                'net_amount' => $netAmount,
                'metadata' => json_encode([
                    'payment_method' => $method,
                    'processor_fee' => $feeAmount
                ]),
                'created_at' => $invoice->paid_at,
                'updated_at' => $now,
            ];
        }

        DB::table('payments')->insert($payments);
        $this->command->info('Created ' . count($payments) . ' payments');
    }
}
