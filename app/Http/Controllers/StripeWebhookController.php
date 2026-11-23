<?php

namespace App\Http\Controllers;

use App\Domain\Payments\Webhooks\InvalidWebhookSignature;
use App\Domain\Payments\Webhooks\ProcessWebhookEvent;
use App\Domain\Payments\Webhooks\StripeSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /stripe/webhook — Stripe (or `stripe listen` locally) calls this.
 *
 *   400  bad signature      → not from Stripe, ignored
 *   200  handled/duplicate  → Stripe stops sending it
 *   500  handler crashed    → Stripe retries later (with backoff, for up to 3 days)
 */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeSignatureVerifier $verifier, ProcessWebhookEvent $process): JsonResponse
    {
        try {
            // Must be the RAW body: re-encoded JSON would no longer match the signature.
            $event = $verifier->verify($request->getContent(), $request->header('Stripe-Signature'));
        } catch (InvalidWebhookSignature $e) {
            return response()->json(['error' => 'Invalid signature.'], 400);
        }

        $record = $process->handle($event);

        return response()->json(['received' => true, 'status' => $record->status]);
    }
}
