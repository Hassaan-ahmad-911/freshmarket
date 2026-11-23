# FreshMarket client demo: video script (2–3 minutes)

The demo shows three things:

1. One basket with items from two suppliers, paid with a single Stripe test payment.
2. Each supplier, logged in, sees only its own sub-order.
3. A partial refund of one item from one supplier. It shows the updated sub-order totals,
   the refund on the customer's payment, and the reversed transfer in that supplier's
   Stripe Connect balance.

---

## Before recording (about 10 minutes, one time)

1. **Stripe test account set up for Connect** (Dashboard in **test mode**):
   - **Connect → Get started**: enable Connect for the platform.
   - **Settings → Connect → Platform profile**: complete it and accept responsibility
     for losses on accounts you manage. The seeder creates platform-managed test
     accounts, and Stripe requires this first.
2. **Keys in `.env`** (Developers → API keys, test mode):
   ```
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   ```
3. **Webhook forwarding** (terminal 1, keep it open and visible):
   ```
   stripe login                                   # once, opens the browser
   stripe listen --forward-to localhost:8000/stripe/webhook
   ```
   Copy the `whsec_...` it prints into `.env` as `STRIPE_WEBHOOK_SECRET`.
4. **Fresh data with real connected accounts:**
   ```
   php artisan config:clear
   php artisan migrate:fresh --seed
   ```
   The output must show two lines like `Stripe → Green Valley Farm: acct_... (transfers active)`.
   If you see "NOT active yet", wait 30 seconds and run `php artisan stripe:demo-accounts`.
5. **Start the app** (terminal 2): `composer run dev` (web server, queue worker, logs).
6. **Open four browser windows** and log in (password `password` for all):
   | Window | Who | Start page |
   |---|---|---|
   | A (normal) | `customer@freshmarket.test` | http://localhost:8000 |
   | B (incognito) | `owner@freshmarket.test` (Green Valley Farm) | /supplier/orders |
   | C (other browser/profile) | `baker@freshmarket.test` (Sunrise Bakery) | /supplier/orders |
   | D | Stripe Dashboard, **test mode** | Payments |
7. Do a full dry run once, then `php artisan migrate:fresh --seed` again so the
   recording starts from order **FM-000001**.

---

## The script (target 2:45)

| Time | Screen | Do | Say |
|---|---|---|---|
| 0:00 | A: home page | Scroll past the two stores | "FreshMarket is a multi-vendor food marketplace: independent suppliers, one basket, one payment." |
| 0:10 | A: product pages | Add **2 × Heirloom Tomatoes** and **2 × Rainbow Carrots** (Green Valley Farm), then **1 × Country Sourdough** (Sunrise Bakery) | "I'm buying from two different suppliers." |
| 0:25 | A: Basket | Show the two supplier groups | "The basket is grouped by supplier. Each one has its own delivery fee, but it's one checkout." |
| 0:35 | A: Checkout | Area **Camden**, click **Continue to payment** | — |
| 0:40 | A: Stripe Checkout | Card **4242 4242 4242 4242**, any future date, any CVC/ZIP, then **Pay $30.98** | "This is one Stripe payment for the whole order." |
| 0:55 | A: Order FM-000001 | "Confirming…" switches to **Paid**. Point at both sub-orders | "The order is only marked paid when Stripe's webhook confirms it, never on the redirect." |
| 1:00 | Terminal 1 | Point at `payment_intent.succeeded [200]` and two `transfer.created [200]` | "Stripe told us the payment succeeded, and we immediately transferred each supplier their share." |
| 1:10 | D: **Payments** → the $30.98 payment | Show the amount and the metadata (`order_number FM-000001`); the transfer group `order_1` is in the payment details (check where during the dry run) | "One charge on the platform account, linked to its transfers by the transfer group." |
| 1:20 | D: **Connect → Connected accounts** → **Green Valley Farm** | Show the incoming transfer **$18.04** in its balance/activity | "The farm received its $19.49 minus the 10% commission on its goods: $18.04." |
| 1:30 | D: **Sunrise Bakery** account | Show **$10.71** | "The bakery has a negotiated 12% commission and received $10.71." |
| 1:40 | B: Farm → Orders | Only **FM-000001-1** is listed; open it | "Logged in as the farm, I see only my part: tomatoes and carrots." |
| 1:50 | C: Bakery → Orders | Only **FM-000001-2**. Then type `/supplier/orders/1` in the address bar → **403** | "The bakery sees only its sourdough, and opening the farm's order is forbidden." |
| 2:00 | B: FM-000001-1 | On **Rainbow Carrots**: choose **1 × $2.75**, reason "Sold out", click **Refund** and confirm | "The farm ran out of one bunch of carrots, so it refunds just that one item." |
| 2:10 | B: same page | Point at **After refunds**: refunded −$2.75, payout (net) **$15.57**; the Refunds table (`re_…`, `trr_…`); transfer **Reversed −$2.47** | "The customer gets $2.75 back. The platform gives up its 28¢ commission, and the farm's transfer is reversed by $2.47." |
| 2:25 | D: the payment | Refresh: **Partially refunded $2.75** | "On the customer's payment: a partial refund of exactly that item." |
| 2:32 | D: Green Valley Farm account | Refresh: the **−$2.47 transfer reversal**; balance now $15.57 | "And here's the reversal in the farm's connected account." |
| 2:38 | D: Sunrise Bakery account | Unchanged **$10.71** | "The bakery is untouched." |
| 2:42 | A: Order page | "1 of 2 refunded", "Refunded to your card −$2.75" | "The customer sees the refund and gets an email. One payment, per-supplier payouts, and refunds that only touch the supplier involved." |

### Shortcut for opening the right Stripe pages

```
php artisan orders:stripe-links FM-000001
```
This prints the direct Dashboard link for the payment and for each connected account,
plus the transfer, refund and reversal IDs. Paste any ID (`tr_…`, `re_…`) into the
Dashboard search bar to open it.

### The numbers (so nothing surprises you on camera)

| | Green Valley Farm (10%) | Sunrise Bakery (12%) | Order |
|---|---|---|---|
| Items | 2 × $4.50 + 2 × $2.75 = $14.50 | 1 × $6.50 | $21.00 |
| Delivery | $4.99 | $4.99 | $9.98 |
| Customer pays | $19.49 | $11.49 | **$30.98** |
| Commission (goods only) | $1.45 | $0.78 | $2.23 |
| Transferred | **$18.04** | **$10.71** | |
| Refund 1 carrot | −$2.75 to customer, −$2.47 reversed, 28¢ commission returned | — | |
| After refund | net payout **$15.57** | **$10.71** | refunded $2.75 |

---

## If something goes wrong while recording

| Symptom | Fix |
|---|---|
| Order page stays on "Confirming…" | Terminal 1 isn't running, or `STRIPE_WEBHOOK_SECRET` doesn't match: restart `stripe listen`, update `.env`, run `php artisan config:clear` |
| Transfer shows **Blocked** | The supplier's account isn't transfer-enabled yet: `php artisan stripe:demo-accounts`, then `php artisan payouts:retry` |
| Transfer shows **Failed** | Read the message on the supplier order page, fix it, then `php artisan payouts:retry` |
| Seeder says it can't create a Stripe account | Finish step 1 (Connect + platform profile), then run `php artisan stripe:demo-accounts` |
| Emails | They're written to `storage/logs/laravel.log` (MAIL_MAILER=log) |
