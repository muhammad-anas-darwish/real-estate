<?php

namespace Modules\Ledger\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ledger\Entities\Account;
use Modules\Ledger\Enums\AccountType;

class LedgerSeeder extends Seeder
{
    public function run(): void
    {
        $systemAccounts = [
            [
                'code' => 'SYS-001',
                'name' => 'System Ledger',
                'type' => AccountType::LIABILITY,
            ],
            [
                'code' => 'REV-001',
                'name' => 'Revenue — Ads',
                'type' => AccountType::REVENUE,
            ],
            [
                'code' => 'REV-002',
                'name' => 'Revenue — Subscriptions',
                'type' => AccountType::REVENUE,
            ],
            [
                'code' => 'CLR-001',
                'name' => 'Stripe Clearing',
                'type' => AccountType::CLEARING,
            ],
            [
                'code' => 'FEE-001',
                'name' => 'Platform Fees',
                'type' => AccountType::PLATFORM_FEE,
            ],
        ];

        foreach ($systemAccounts as $data) {
            Account::firstOrCreate(
                ['code' => $data['code']],
                [
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'currency' => 'USD',
                    'current_balance' => 0,
                    'held_balance' => 0,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('LedgerSeeder: system accounts seeded.');
    }
}
