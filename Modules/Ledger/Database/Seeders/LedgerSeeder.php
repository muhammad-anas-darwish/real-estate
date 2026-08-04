<?php

namespace Modules\Ledger\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Ledger\Entities\Account;
use Modules\Ledger\Enums\AccountCategory;
use Modules\Ledger\Enums\AccountType;

class LedgerSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedChartOfAccounts();
        $this->seedSystemAccounts();
        $this->command->info('LedgerSeeder: chart of accounts and system accounts seeded.');
    }

    private function seedChartOfAccounts(): void
    {
        $chart = [
            [
                'name' => 'الأصول',
                'name_en' => 'Assets',
                'category' => AccountCategory::ASSET,
                'number' => '1',
                'children' => [
                    [
                        'name' => 'الأصول المتداولة',
                        'name_en' => 'Current Assets',
                        'number' => '11',
                        'children' => [
                            ['name' => 'النقدية', 'name_en' => 'Cash', 'number' => '1101'],
                            ['name' => 'البنوك - حسابات جارية', 'name_en' => 'Bank - Current Accounts', 'number' => '1102'],
                            ['name' => 'ذمم مدينة - عملاء', 'name_en' => 'Accounts Receivable - Clients', 'number' => '1103'],
                            ['name' => 'ذمم مدينة - مزودي خدمات', 'name_en' => 'Accounts Receivable - Providers', 'number' => '1104'],
                            ['name' => 'عقود تحت التنفيذ', 'name_en' => 'Work in Progress', 'number' => '1105'],
                        ],
                    ],
                    [
                        'name' => 'الأصول الثابتة',
                        'name_en' => 'Fixed Assets',
                        'number' => '12',
                        'children' => [
                            ['name' => 'مباني', 'name_en' => 'Buildings', 'number' => '1201'],
                            ['name' => 'سيارات', 'name_en' => 'Vehicles', 'number' => '1202'],
                            ['name' => 'أثاث ومعدات مكتبية', 'name_en' => 'Furniture & Office Equipment', 'number' => '1203'],
                            ['name' => 'أجهزة حواسيب', 'name_en' => 'Computers', 'number' => '1204'],
                            ['name' => 'مجمع إهلاك الأصول', 'name_en' => 'Accumulated Depreciation', 'number' => '1299'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'الخصوم',
                'name_en' => 'Liabilities',
                'category' => AccountCategory::LIABILITY,
                'number' => '2',
                'children' => [
                    [
                        'name' => 'الخصوم المتداولة',
                        'name_en' => 'Current Liabilities',
                        'number' => '21',
                        'children' => [
                            ['name' => 'ذمم دائنة - موردين', 'name_en' => 'Accounts Payable - Suppliers', 'number' => '2101'],
                            ['name' => 'ذمم دائنة - مزودي خدمات', 'name_en' => 'Accounts Payable - Service Providers', 'number' => '2102'],
                            ['name' => 'رواتب مستحقة', 'name_en' => 'Accrued Salaries', 'number' => '2103'],
                            ['name' => 'مصاريف مستحقة', 'name_en' => 'Accrued Expenses', 'number' => '2104'],
                            ['name' => 'إيرادات مقدمة', 'name_en' => 'Unearned Revenue', 'number' => '2105'],
                            ['name' => 'أرصدة مستخدمين', 'name_en' => 'User Balances', 'number' => '2106'],
                        ],
                    ],
                    [
                        'name' => 'الخصوم طويلة الأجل',
                        'name_en' => 'Long-term Liabilities',
                        'number' => '22',
                        'children' => [
                            ['name' => 'قروض طويلة الأجل', 'name_en' => 'Long-term Loans', 'number' => '2201'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'حقوق الملكية',
                'name_en' => 'Equity',
                'category' => AccountCategory::EQUITY,
                'number' => '3',
                'children' => [
                    ['name' => 'رأس المال', 'name_en' => 'Capital', 'number' => '3001'],
                    ['name' => 'أرباح محتجزة', 'name_en' => 'Retained Earnings', 'number' => '3002'],
                    ['name' => 'صافي الدخل / الخسارة', 'name_en' => 'Net Income / Loss', 'number' => '3003'],
                ],
            ],
            [
                'name' => 'الإيرادات',
                'name_en' => 'Revenue',
                'category' => AccountCategory::REVENUE,
                'number' => '4',
                'children' => [
                    ['name' => 'إيرادات اشتراكات', 'name_en' => 'Subscription Revenue', 'number' => '4001'],
                    ['name' => 'إيرادات إعلانات', 'name_en' => 'Advertising Revenue', 'number' => '4002'],
                    ['name' => 'إيرادات خدمات', 'name_en' => 'Service Revenue', 'number' => '4003'],
                    ['name' => 'إيرادات عمولات', 'name_en' => 'Commission Revenue', 'number' => '4004'],
                    ['name' => 'إيرادات أخرى', 'name_en' => 'Other Revenue', 'number' => '4005'],
                ],
            ],
            [
                'name' => 'المصروفات',
                'name_en' => 'Expenses',
                'category' => AccountCategory::EXPENSE,
                'number' => '5',
                'children' => [
                    ['name' => 'رواتب وأجور', 'name_en' => 'Salaries & Wages', 'number' => '5001'],
                    ['name' => 'إيجار مكتب', 'name_en' => 'Office Rent', 'number' => '5002'],
                    ['name' => 'خدمات عامة', 'name_en' => 'Utilities', 'number' => '5003'],
                    ['name' => 'تسويق وإعلان', 'name_en' => 'Marketing & Advertising', 'number' => '5004'],
                    ['name' => 'رسوم منصة', 'name_en' => 'Platform Fees', 'number' => '5005'],
                    ['name' => 'مصاريف صيانة', 'name_en' => 'Maintenance Expenses', 'number' => '5006'],
                    ['name' => 'رسوم خدمات مصرفية', 'name_en' => 'Bank Service Charges', 'number' => '5007'],
                    ['name' => 'مصاريف متنوعة', 'name_en' => 'Miscellaneous Expenses', 'number' => '5099'],
                ],
            ],
        ];

        foreach ($chart as $catIndex => $categoryAccount) {
            $sortOrder = ($catIndex + 1) * 1000;
            $parent = $this->createAccount(
                code: 'CAT-'.$categoryAccount['number'],
                name: $categoryAccount['name'].' ('.$categoryAccount['name_en'].')',
                accountNumber: $categoryAccount['number'],
                accountCategory: $categoryAccount['category'],
                sortOrder: $sortOrder,
            );

            if (! empty($categoryAccount['children'])) {
                foreach ($categoryAccount['children'] as $groupIndex => $groupAccount) {
                    $groupSort = $sortOrder + ($groupIndex + 1) * 100;

                    if (! empty($groupAccount['children'])) {
                        $group = $this->createAccount(
                            code: 'GRP-'.$groupAccount['number'],
                            name: $groupAccount['name'].' ('.$groupAccount['name_en'].')',
                            accountNumber: $groupAccount['number'],
                            accountCategory: $categoryAccount['category'],
                            parentId: $parent->id,
                            sortOrder: $groupSort,
                        );

                        foreach ($groupAccount['children'] as $leafIndex => $leafAccount) {
                            $this->createAccount(
                                code: 'ACC-'.$leafAccount['number'],
                                name: $leafAccount['name'].' ('.$leafAccount['name_en'].')',
                                accountNumber: $leafAccount['number'],
                                accountCategory: $categoryAccount['category'],
                                parentId: $group->id,
                                sortOrder: $groupSort + $leafIndex + 1,
                            );
                        }
                    } else {
                        $this->createAccount(
                            code: 'ACC-'.$groupAccount['number'],
                            name: $groupAccount['name'].' ('.$groupAccount['name_en'].')',
                            accountNumber: $groupAccount['number'],
                            accountCategory: $categoryAccount['category'],
                            parentId: $parent->id,
                            sortOrder: $groupSort,
                        );
                    }
                }
            }
        }
    }

    private function seedSystemAccounts(): void
    {
        $systemAccounts = [
            [
                'code' => 'SYS-001',
                'name' => 'System Ledger',
                'type' => AccountType::LIABILITY,
                'account_category' => AccountCategory::LIABILITY,
                'account_number' => 'SYS-001',
            ],
            [
                'code' => 'REV-001',
                'name' => 'Revenue — Ads',
                'type' => AccountType::REVENUE,
                'account_category' => AccountCategory::REVENUE,
                'account_number' => 'REV-001',
            ],
            [
                'code' => 'REV-002',
                'name' => 'Revenue — Subscriptions',
                'type' => AccountType::REVENUE,
                'account_category' => AccountCategory::REVENUE,
                'account_number' => 'REV-002',
            ],
            [
                'code' => 'CLR-001',
                'name' => 'Stripe Clearing',
                'type' => AccountType::CLEARING,
                'account_category' => AccountCategory::ASSET,
                'account_number' => 'CLR-001',
            ],
            [
                'code' => 'FEE-001',
                'name' => 'Platform Fees',
                'type' => AccountType::PLATFORM_FEE,
                'account_category' => AccountCategory::EXPENSE,
                'account_number' => 'FEE-001',
            ],
        ];

        foreach ($systemAccounts as $data) {
            Account::firstOrCreate(
                ['code' => $data['code']],
                [
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'account_category' => $data['account_category'] ?? null,
                    'account_number' => $data['account_number'] ?? null,
                    'currency' => 'USD',
                    'current_balance' => 0,
                    'held_balance' => 0,
                    'is_active' => true,
                ]
            );
        }
    }

    private function createAccount(
        string $code,
        string $name,
        string $accountNumber,
        AccountCategory $accountCategory,
        ?int $parentId = null,
        int $sortOrder = 0,
    ): Account {
        return Account::firstOrCreate(
            ['code' => $code],
            [
                'code' => $code,
                'name' => $name,
                'account_number' => $accountNumber,
                'account_category' => $accountCategory,
                'type' => AccountType::LIABILITY,
                'currency' => 'USD',
                'parent_id' => $parentId,
                'sort_order' => $sortOrder,
                'current_balance' => 0,
                'held_balance' => 0,
                'is_active' => true,
            ]
        );
    }
}
