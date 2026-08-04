<?php

return [
    'default_currency' => env('LEDGER_DEFAULT_CURRENCY', 'USD'),
    'decimal_places' => 2,
    'idempotency_ttl_minutes' => 60,
    'system_account' => env('LEDGER_SYSTEM_ACCOUNT', 'SYS-001'),
    'revenue_account' => env('LEDGER_REVENUE_ACCOUNT', 'REV-001'),
    'clearing_account' => env('LEDGER_CLEARING_ACCOUNT', 'CLR-001'),
];
