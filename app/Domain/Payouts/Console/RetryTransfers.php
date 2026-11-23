<?php

namespace App\Domain\Payouts\Console;

use App\Domain\Payouts\Actions\TransferToSupplier;
use App\Domain\Payouts\Enums\TransferStatus;
use App\Domain\Payouts\Models\Transfer;
use Illuminate\Console\Command;
use Throwable;

class RetryTransfers extends Command
{
    protected $signature = 'payouts:retry';

    protected $description = 'Retry supplier transfers that are blocked or failed';

    public function handle(TransferToSupplier $transferToSupplier): int
    {
        $transfers = Transfer::whereIn('status', [TransferStatus::Blocked, TransferStatus::Failed, TransferStatus::Pending])
            ->with(['supplierOrder', 'payment', 'supplier'])
            ->get();

        foreach ($transfers as $transfer) {
            try {
                $result = $transferToSupplier->handle($transfer->supplierOrder, $transfer->payment);
                $this->line("{$transfer->supplierOrder->number} → {$result->status->label()} {$result->stripe_transfer_id}");
            } catch (Throwable $e) {
                $this->error("{$transfer->supplierOrder->number}: {$e->getMessage()}");
            }
        }

        $this->info("Checked {$transfers->count()} transfer(s).");

        return self::SUCCESS;
    }
}
