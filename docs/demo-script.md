# FreshMarket client demo: video script (2–3 minutes)

The demo shows three things:

1. One basket with items from two suppliers, paid with a single Stripe test payment.
2. Each supplier, logged in, sees only its own sub-order.
3. A partial refund of one item from one supplier. It shows the updated sub-order totals,
   the refund on the customer's payment, and the reversed transfer in that supplier's
   Stripe Connect balance.

The full flow has been verified end to end against Stripe test mode: Checkout Session,
`payment_intent.succeeded` webhook, both transfers, refund, transfer reversal, and
connected-account balances.

---

## Before recording

You need `STRIPE_KEY` and `STRIPE_SECRET` (test mode) in `.env` and Connect enabled
on your Stripe account. Open a new terminal so the `stripe` and `php` commands are on PATH.

1. **Terminal 1, webhooks** (keep it visible, it's in the video):
   ```
   php artisan stripe:listen
   ```
   This runs the Stripe CLI with your key from `.env`, so no `stripe login` is needed.
   It forwards exactly the events the app handles to `http://localhost:8080/stripe/webhook`.
   If it prints a warning that the signing secret differs from `.env`, copy the
   `whsec_…` into `STRIPE_WEBHOOK_SECRET` and run `php artisan config:clear`.
2. **Terminal 2, the app:** `composer run dev` (web server on **http://localhost:8080**,
   queue worker, logs). The port is set by `SERVER_PORT=8080` in `.env`, because another
   dev server on this machine already uses 8000.
3. **Do one dry run** of the whole script, including typing the test card into Stripe Checkout.
4. **Reset for the real take** (terminal 3, with terminal 2 stopped):
   ```
   php artisan migrate:fresh --seed
   php artisan stripe:demo-accounts --fresh
   ```
   The first command resets the app to order **FM-000001**. The second gives both
   suppliers brand-new connected accounts, so their Stripe balances start at $0.00.
   Both lines must end in `(transfers active)`. Then start terminal 2 again.
5. **Open four browser windows** and log in (password `password` for all):
   | Window | Who | Start page |
   |---|---|---|
   | A (normal) | `customer@freshmarket.test` | http://localhost:8080 |
   | B (incognito) | `owner@freshmarket.test` (Green Valley Farm) | http://localhost:8080/supplier/orders |
   | C (other browser or profile) | `baker@freshmarket.test` (Sunrise Bakery) | http://localhost:8080/supplier/orders |
   | D | Stripe Dashboard in **test mode** | Payments |

---

## The script (target 2:45)

| Time | Screen | Do | Say |
|---|---|---|---|
| 0:00 | A: home page | Scroll past the two stores | "FreshMarket is a multi-vendor food marketplace: independent suppliers, one basket, one payment." |
| 0:10 | A: product pages | Add **2 × Heirloom Tomatoes** and **2 × Rainbow Carrots** (Green Valley Farm), then **1 × Country Sourdough** (Sunrise Bakery) | "I'm buying from two different suppliers." |
| 0:25 | A: Basket | Show the two supplier groups | "The basket is grouped by supplier. Each one has its own delivery fee, but it's one checkout." |
| 0:35 | A: Checkout | Area **Camden**, then **Continue to payment** | — |
| 0:40 | A: Stripe Checkout | Card **4242 4242 4242 4242**, any future date, any CVC/ZIP, then **Pay $30.98** | "This is one Stripe payment for the whole order." |
| 0:55 | A: Order FM-000001 | "Confirming…" switches to **Paid**. Point at both sub-orders | "The order is only marked paid when Stripe's webhook confirms it, never on the redirect." |
| 1:00 | Terminal 1 | Point at `payment_intent.succeeded … [200]` and two `transfer.created … [200]` | "Stripe confirmed the payment, and we immediately transferred each supplier their share." |
| 1:10 | D: **Payments** → the $30.98 payment | Show the amount and the description "FreshMarket order FM-000001" | "One charge on the platform account. It's linked to the suppliers' transfers by its transfer group, `order_1`." |
| 1:20 | D: **Connect → Connected accounts → Green Valley Farm** | Show the incoming **$18.04** transfer and the balance | "The farm received its $19.49 minus the 10% commission on its goods: $18.04." |
| 1:30 | D: **Sunrise Bakery** account | Show **$10.71** | "The bakery has a negotiated 12% commission and received $10.71." |
| 1:40 | B: Farm → Orders | Only **FM-000001-1** is listed; open it | "Logged in as the farm, I see only my part: tomatoes and carrots." |
| 1:50 | C: Bakery → Orders | Only **FM-000001-2**. Then type `/supplier/orders/1` in the address bar → **403** | "The bakery sees only its sourdough, and opening the farm's order is forbidden." |
| 2:00 | B: FM-000001-1 | On **Rainbow Carrots**: choose **1 × $2.75**, reason "Sold out", click **Refund** and confirm | "The farm ran out of one bunch of carrots, so it refunds just that one item." |
| 2:10 | B: same page | Point at **After refunds**: refunded −$2.75, payout (net) **$15.57**; the Refunds table (`re_…`, `trr_…`); transfer **Reversed −$2.47** | "The customer gets $2.75 back. The platform gives up its 28¢ commission, and the farm's transfer is reversed by $2.47." |
| 2:25 | D: the payment | Refresh: **$2.75 refunded** | "On the customer's payment: a partial refund of exactly that item." |
| 2:32 | D: Green Valley Farm account | Refresh: the **−$2.47 reversal**; balance **$15.57** | "And here's the reversal in the farm's connected account." |
| 2:38 | D: Sunrise Bakery account | Still **$10.71** | "The bakery is untouched." |
| 2:42 | A: Order page | "1 of 2 refunded", "Refunded to your card −$2.75" | "The customer sees the refund and gets an email. One payment, per-supplier payouts, and refunds that only touch the supplier involved." |

### Shortcut for opening the right Stripe pages

```
php artisan orders:stripe-links FM-000001
```
This prints the direct Dashboard link for the payment and for each connected account,
plus the transfer, refund and reversal IDs. Paste any ID (`tr_…`, `re_…`, `acct_…`) into
the Dashboard search bar to open it.

### The numbers (as verified in Stripe test mode)

| | Green Valley Farm (10%) | Sunrise Bakery (12%) | Order |
|---|---|---|---|
| Items | 2 × $4.50 + 2 × $2.75 = $14.50 | 1 × $6.50 | $21.00 |
| Delivery | $4.99 | $4.99 | $9.98 |
| Customer pays | $19.49 | $11.49 | **$30.98** |
| Commission (goods only) | $1.45 | $0.78 | $2.23 |
| Transferred | **$18.04** | **$10.71** | |
| Refund 1 carrot | −$2.75 to customer, **−$2.47 reversed**, 28¢ commission returned | — | |
| Connected-account balance after | **$15.57** | **$10.71** | $2.75 refunded |

---

## If something goes wrong while recording

| Symptom | Fix |
|---|---|
| Order page stays on "Confirming…" | Terminal 1 isn't running, or it warned about a different signing secret: update `STRIPE_WEBHOOK_SECRET`, run `php artisan config:clear`, restart terminal 2 |
| Transfers stay **Pending** | The queue worker isn't running. `composer run dev` starts it; or run `php artisan queue:work` |
| Transfer shows **Blocked** or **Failed** | Read the message on the supplier order page, then `php artisan payouts:retry` |
| Seeder can't create a Stripe account | Make sure Connect is enabled in the Dashboard (test mode), then `php artisan stripe:demo-accounts` |
| http://localhost:8080 shows another app | Something else took port 8080: change `SERVER_PORT` and `APP_URL` in `.env`, then `php artisan config:clear` |
| Emails | They're written to `storage/logs/laravel.log` (MAIL_MAILER=log) |
