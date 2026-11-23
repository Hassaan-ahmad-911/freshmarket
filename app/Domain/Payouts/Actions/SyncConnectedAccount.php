<?php

namespace App\Domain\Payouts\Actions;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Gateways\ConnectedAccountStatus;
use App\Domain\Payouts\Enums\TransferStatus;
use App\Domain\Payouts\Models\Transfer;
use App\Domain\Suppliers\Models\Supplier;
use Throwable;

/**
 * Copies a connected account's capabilities from Stripe onto the supplier,
 * and — if the account can now receive money — sends any transfers that
 * were waiting for it.
 */
class SyncConnectedAccount
{
    public function __construct(
        private PaymentGateway $gateway,
        private TransferToSupplier $transferToSupplier,
    ) {}

    public function handle(Supplier $supplier, ?ConnectedAccountStatus $status = null): Supplier
    {
        if ($supplier->stripe_account_id === null) {
            return $supplier;
        }

        $status ??= $this->gateway->retrieveAccount($supplier->stripe_account_id);

        $supplier->update([
            'stripe_details_submitted' => $status->detailsSubmitted,
            'stripe_transfers_enabled' => $status->transfersEnabled,
            'stripe_payouts_enabled' => $status->payoutsEnabled,
            'stripe_synced_at' => now(),
        ]);

        if ($supplier->canReceiveTransfers()) {
            $this->releaseWaitingTransfers($supplier);
        }

        return $supplier;
    }

    private function releaseWaitingTransfers(Supplier $supplier): void
    {
        Transfer::query()
            ->whereBelongsTo($supplier)
            ->whereIn('status', [TransferStatus::Blocked, TransferStatus::Failed])
            ->with(['supplierOrder', 'payment'])
            ->get()
            ->each(function (Transfer $transfer) {
                try {
                    $this->transferToSupplier->handle($transfer->supplierOrder, $transfer->payment);
                } catch (Throwable $e) {
                    report($e); // stays "failed"; `payouts:retry` or the next account.updated tries again
                }
            });
    }
}
