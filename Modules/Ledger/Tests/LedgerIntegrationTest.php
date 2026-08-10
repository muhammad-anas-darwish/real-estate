<?php

namespace Modules\Ledger\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\Ledger\Entities\Account;
use Modules\Ledger\Enums\AccountCategory;
use Modules\Ledger\Enums\AccountType;
use Modules\Ledger\Enums\PayrollType;
use Modules\ServiceProvider\Entities\ServiceProviderProfile;
use Tests\TestCase;

class LedgerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Account $cashAccount;

    protected Account $revenueAccount;

    protected Account $expenseAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->cashAccount = Account::create([
            'code' => 'ACC-1101',
            'name' => 'Cash',
            'account_number' => '1101',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'current_balance' => 10000.00,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        $this->revenueAccount = Account::create([
            'code' => 'ACC-4001',
            'name' => 'Service Revenue',
            'account_number' => '4001',
            'account_category' => AccountCategory::REVENUE,
            'type' => AccountType::REVENUE,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        $this->expenseAccount = Account::create([
            'code' => 'ACC-5001',
            'name' => 'Salaries',
            'account_number' => '5001',
            'account_category' => AccountCategory::EXPENSE,
            'type' => AccountType::EXPENSE,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);
    }

    public function test_account_resource_returns_correct_data()
    {
        $resource = new \Modules\Ledger\Http\Resources\AccountResource($this->expenseAccount);
        $data = $resource->toArray(request());

        $this->assertEquals('ACC-5001', $data['code']);
        $this->assertEquals('Salaries', $data['name']);
        $this->assertEquals('expense', $data['account_category']);
        $this->assertEquals('Expenses', $data['account_category_label']);
        $this->assertEquals('debit', $data['normal_balance']);
    }

    public function test_account_tree_structure()
    {
        $root = Account::create([
            'code' => 'CAT-TREE-1',
            'name' => 'Assets Tree',
            'account_number' => '91',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
            'sort_order' => 1000,
        ]);

        Account::create([
            'code' => 'ACC-TREE-1101',
            'name' => 'Cash Tree',
            'account_number' => '9101',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'parent_id' => $root->id,
            'current_balance' => 5000,
            'held_balance' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $service = app(\Modules\Ledger\Services\AccountService::class);
        $tree = $service->getTree();

        $this->assertIsArray($tree);
        $this->assertNotEmpty($tree);
    }

    public function test_journal_entry_creation_and_lines()
    {
        $dto = \Modules\Ledger\DTOs\JournalEntryDTO::fromRequest([
            'entry_date' => '2026-06-19',
            'description' => 'Recording service revenue',
            'reference' => 'INV-001',
            'lines' => [
                [
                    'account_id' => $this->cashAccount->id,
                    'debit_amount' => 1000,
                    'credit_amount' => 0,
                ],
                [
                    'account_id' => $this->revenueAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => 1000,
                ],
            ],
        ]);

        $service = app(\Modules\Ledger\Services\JournalEntryService::class);
        $entry = $service->create($dto, $this->user);

        $this->assertEquals('draft', $entry->status->value);
        $this->assertTrue($entry->isBalanced());
        $this->assertCount(2, $entry->lines);
    }

    public function test_journal_entry_update_lines()
    {
        $dto = \Modules\Ledger\DTOs\JournalEntryDTO::fromRequest([
            'entry_date' => '2026-06-19',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit_amount' => 100, 'credit_amount' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit_amount' => 0, 'credit_amount' => 100],
            ],
        ]);

        $service = app(\Modules\Ledger\Services\JournalEntryService::class);
        $entry = $service->create($dto, $this->user);

        $updateDto = \Modules\Ledger\DTOs\JournalEntryDTO::fromRequest([
            'entry_date' => '2026-06-20',
            'description' => 'Updated',
            'lines' => [
                ['account_id' => $this->expenseAccount->id, 'debit_amount' => 500, 'credit_amount' => 0],
                ['account_id' => $this->cashAccount->id, 'debit_amount' => 0, 'credit_amount' => 500],
            ],
        ]);

        $updated = $service->update($entry, $updateDto);

        $this->assertEquals('Updated', $updated->description);
        $this->assertEquals(500.0, $updated->total_debit);
        $this->assertEquals(500.0, $updated->total_credit);
        $this->assertCount(2, $updated->lines);
    }

    public function test_journal_entry_unbalanced_rejected()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not balanced');

        $dto = \Modules\Ledger\DTOs\JournalEntryDTO::fromRequest([
            'entry_date' => '2026-06-19',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit_amount' => 1000, 'credit_amount' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit_amount' => 0, 'credit_amount' => 500],
            ],
        ]);

        $service = app(\Modules\Ledger\Services\JournalEntryService::class);
        $service->create($dto, $this->user);
    }

    public function test_journal_entry_delete_draft()
    {
        $dto = \Modules\Ledger\DTOs\JournalEntryDTO::fromRequest([
            'entry_date' => '2026-06-19',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit_amount' => 100, 'credit_amount' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit_amount' => 0, 'credit_amount' => 100],
            ],
        ]);

        $service = app(\Modules\Ledger\Services\JournalEntryService::class);
        $entry = $service->create($dto, $this->user);

        $service->delete($entry);

        $this->assertSoftDeleted('journal_entries', ['id' => $entry->id]);
    }

    public function test_journal_entry_posting_updates_balances()
    {
        $dto = \Modules\Ledger\DTOs\JournalEntryDTO::fromRequest([
            'entry_date' => '2026-06-19',
            'description' => 'Service revenue',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit_amount' => 1000, 'credit_amount' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit_amount' => 0, 'credit_amount' => 1000],
            ],
        ]);

        $service = app(\Modules\Ledger\Services\JournalEntryService::class);
        $entry = $service->create($dto, $this->user);
        $posted = $service->post($entry, $this->user);

        $this->assertEquals('posted', $posted->status->value);

        $cashAfter = $this->cashAccount->fresh()->current_balance;
        $revenueAfter = $this->revenueAccount->fresh()->current_balance;

        $this->assertEquals(9000.00, (float) $cashAfter);
        $this->assertEquals(1000.00, (float) $revenueAfter);

        $this->assertDatabaseHas('ledger_account_entries', [
            'reference_type' => 'journal_entry',
            'reference_id' => $entry->id,
        ]);
    }

    public function test_trial_balance_returns_report()
    {
        $dto = \Modules\Ledger\DTOs\JournalEntryDTO::fromRequest([
            'entry_date' => '2026-06-19',
            'lines' => [
                ['account_id' => $this->cashAccount->id, 'debit_amount' => 1000, 'credit_amount' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit_amount' => 0, 'credit_amount' => 1000],
            ],
        ]);

        $service = app(\Modules\Ledger\Services\JournalEntryService::class);
        $entry = $service->create($dto, $this->user);
        $service->post($entry, $this->user);

        $report = $service->getTrialBalance('2026-01-01', '2026-12-31');

        $this->assertEquals(1000.0, $report['total_debit']);
        $this->assertEquals(1000.0, $report['total_credit']);
        $this->assertTrue($report['is_balanced']);
        $this->assertNotEmpty($report['categories']);
    }

    public function test_payroll_setup_and_list()
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        $providerUser = User::factory()->create();
        Account::create([
            'code' => 'USR-PROV-001',
            'name' => 'Balance: '.$providerUser->name,
            'type' => AccountType::USER_BALANCE,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
            'owner_type' => User::class,
            'owner_id' => $providerUser->id,
        ]);

        $this->actingAs($providerUser)->postJson('/api/service-provider/register', [
            'type' => 'photographer',
            'coverage_city_ids' => [$city->id],
        ]);

        $profile = ServiceProviderProfile::first();
        $profile->update(['is_verified' => true]);

        $dto = \Modules\Ledger\DTOs\PayrollDTO::fromRequest([
            'type' => PayrollType::MONTHLY->value,
            'service_provider_profile_id' => $profile->id,
            'base_salary' => 3000.00,
            'is_active' => true,
            'start_date' => now()->subDays(40)->format('Y-m-d'),
        ]);

        $payrollService = app(\Modules\Ledger\Services\PayrollService::class);
        $payroll = $payrollService->setup($dto);

        $this->assertEquals('monthly', $payroll->type->value);
        $this->assertEquals(3000.00, (float) $payroll->base_salary);

        $payrolls = $payrollService->listProvidersWithPayroll();
        $this->assertEquals(1, $payrolls->total());
    }

    public function test_payroll_run_creates_payment_and_journal_entry()
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        $providerUser = User::factory()->create();
        Account::create([
            'code' => 'USR-PROV-002',
            'name' => 'Balance: '.$providerUser->name,
            'type' => AccountType::USER_BALANCE,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
            'owner_type' => User::class,
            'owner_id' => $providerUser->id,
        ]);

        $this->actingAs($providerUser)->postJson('/api/service-provider/register', [
            'type' => 'photographer',
            'coverage_city_ids' => [$city->id],
        ]);

        $profile = ServiceProviderProfile::first();
        $profile->update(['is_verified' => true]);

        $dto = \Modules\Ledger\DTOs\PayrollDTO::fromRequest([
            'type' => PayrollType::MONTHLY->value,
            'service_provider_profile_id' => $profile->id,
            'base_salary' => 3000.00,
            'is_active' => true,
            'start_date' => now()->subDays(40)->format('Y-m-d'),
        ]);

        $payrollService = app(\Modules\Ledger\Services\PayrollService::class);
        $payrollService->setup($dto);

        $results = $payrollService->runPayroll($this->user);

        $this->assertCount(1, $results);
        $this->assertEquals('paid', $results[0]['status']);

        $this->assertDatabaseHas('payroll_payments', [
            'service_provider_profile_id' => $profile->id,
            'status' => 'paid',
        ]);

        $providerBalance = Account::where('owner_id', $providerUser->id)
            ->where('owner_type', User::class)
            ->first()->current_balance;
        $this->assertGreaterThan(0, (float) $providerBalance);
    }

    public function test_inactive_payroll_is_skipped()
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        $providerUser = User::factory()->create();
        $this->actingAs($providerUser)->postJson('/api/service-provider/register', [
            'type' => 'photographer',
            'coverage_city_ids' => [$city->id],
        ]);

        $profile = ServiceProviderProfile::first();
        $profile->update(['is_verified' => true]);

        $dto = \Modules\Ledger\DTOs\PayrollDTO::fromRequest([
            'type' => PayrollType::MONTHLY->value,
            'service_provider_profile_id' => $profile->id,
            'base_salary' => 1000.00,
            'is_active' => false,
        ]);

        $payrollService = app(\Modules\Ledger\Services\PayrollService::class);
        $payrollService->setup($dto);

        $results = $payrollService->runPayroll($this->user);

        $this->assertEmpty($results);
    }
}
