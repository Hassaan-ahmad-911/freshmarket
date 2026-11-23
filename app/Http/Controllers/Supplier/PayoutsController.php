<?php

namespace App\Http\Controllers\Supplier;

use App\Domain\Payouts\Actions\StartConnectOnboarding;
use App\Domain\Payouts\Actions\SyncConnectedAccount;
use App\Domain\Payouts\Services\EarningsReport;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Stripe Connect status, earnings and transfer history — store owners only. */
class PayoutsController extends Controller
{
    public function index(Request $request, EarningsReport $report): View
    {
        $supplier = $request->user()->supplier;
        Gate::authorize('viewEarnings', $supplier);

        return view('supplier.payouts.index', [
            'supplier' => $supplier,
            'totals' => $report->totals($supplier),
            'transfers' => $report->transfers($supplier)->paginate(15),
        ]);
    }

    /** "Connect with Stripe" / "Continue onboarding" → Stripe's hosted onboarding. */
    public function connect(Request $request, StartConnectOnboarding $onboarding): RedirectResponse
    {
        $supplier = $request->user()->supplier;
        Gate::authorize('managePayouts', $supplier);

        return redirect()->away($onboarding->handle($supplier));
    }

    /** Stripe sends the supplier back here after onboarding. */
    public function return(Request $request, SyncConnectedAccount $sync): RedirectResponse
    {
        $supplier = $request->user()->supplier;
        Gate::authorize('managePayouts', $supplier);

        $sync->handle($supplier);

        return redirect()->route('supplier.payouts.index')->with('status', $supplier->canReceiveTransfers()
            ? 'Your Stripe account is ready to receive payouts.'
            : 'Stripe still needs a few details — click "Continue onboarding".');
    }

    /** The onboarding link expired (they're single-use): make a fresh one. */
    public function refresh(Request $request, StartConnectOnboarding $onboarding): RedirectResponse
    {
        Gate::authorize('managePayouts', $request->user()->supplier);

        return redirect()->away($onboarding->handle($request->user()->supplier));
    }
}
