<?php

namespace App\Domain\Payouts\Console;

use App\Domain\Payments\Models\Refund;
use App\Domain\Payouts\Actions\ReverseTransferForRefund;
use App\Domain\Payouts\Actions\TransferToSupplier;
use App\Domain\Payouts\Enums\TransferStatus;
use App\Domain\Payouts\Models\Transfer;
use Illuminate\Console\Command;
use Throwable;

class RetryTransfers extends Command
{
    protected $signature = 'payouts:retry';

    protected $description = 'Retry supplier transfers and refund reversals that are blocked or failed';

    public function handle(TransferToSupplier $transferToSupplier, ReverseTransferForRefund $reverse): int
    {
        $transfers = Transfer::whereIn('status', [TransferStatus::Blocked, TransferStatus::Failed, TransferStatus::Pending])
            ->with(['supplierOrder', 'payment', 'supplier'])
            ->get();

        foreach ($transfers as $transfer) {
            try {
                $result = $transferToSupplier->handle($transfer->supplierOrder, $transfer->payment);
                $this->line("Transfer {$transfer->supplierOrder->number} → {$result->status->label()} {$result->stripe_transfer_id}");
            } catch (Throwable $e) {
                $this->error("Transfer {$transfer->supplierOrder->number}: {$e->getMessage()}");
            }
        }

        $reversals = Refund::where('reversal_status', 'failed')->get();

        foreach ($reversals as $refund) {
            $result = $reverse->handle($refund);
            $this->line("Reversal for refund #{$refund->id} → {$result->reversal_status}");
        }

        $this->info("Checked {$transfers->count()} transfer(s) and {$reversals->count()} reversal(s).");

        return self::SUCCESS;
    }
}
