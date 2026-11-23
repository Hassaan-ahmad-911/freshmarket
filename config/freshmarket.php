<?php

/*
|--------------------------------------------------------------------------
| FreshMarket platform defaults
|--------------------------------------------------------------------------
|
| These are fallback values. From Phase 6 the admin can override them in the
| "Platform settings" screen; per-supplier commission can also be overridden.
|
| All money is in the smallest currency unit (cents). All percentages are in
| basis points: 1000 bps = 10%.
|
*/

return [

    'currency' => env('FRESHMARKET_CURRENCY', 'usd'),

    'default_commission_bps' => (int) env('FRESHMARKET_COMMISSION_BPS', 1000),

    'default_delivery_fee' => (int) env('FRESHMARKET_DELIVERY_FEE', 499),

    // Orders at or above this per-supplier subtotal get free delivery (0 = never free).
    'free_delivery_threshold' => (int) env('FRESHMARKET_FREE_DELIVERY_THRESHOLD', 5000),

];
