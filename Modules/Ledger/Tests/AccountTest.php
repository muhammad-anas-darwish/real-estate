<?php

namespace Modules\Ledger\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Ledger\Entities\Account;
use Modules\Ledger\Entities\AccountEntry;
use Modules\Ledger\Enums\AccountCategory;
use Modules\Ledger\Enums\AccountType;
use Modules\Ledger\Enums\EntryType;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'accountant']);
        $permissions = [
            'accounts.list',
            'accounts.show',
            'accounts.create',
            'accounts.edit',
            'accounts.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
            $role->givePermissionTo($permission);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($role);
    }

    public function test_can_get_tree()
    {
        $root = Account::create([
            'code' => 'CAT-1',
            'name' => 'Assets',
            'account_number' => '1',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
            'sort_order' => 1000,
        ]);

        Account::create([
            'code' => 'ACC-1101',
            'name' => 'Cash',
            'account_number' => '1101',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'parent_id' => $root->id,
            'current_balance' => 5000.00,
            'held_balance' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/accounts/tree');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.code', 'CAT-1');
        $response->assertJsonPath('data.0.account_category', 'asset');
        $response->assertJsonPath('data.0.normal_balance', 'debit');
    }

    public function test_can_filter_tree_by_category()
    {
        Account::create([
            'code' => 'CAT-1',
            'name' => 'Assets',
            'account_number' => '1',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
            'sort_order' => 1000,
        ]);

        Account::create([
            'code' => 'CAT-4',
            'name' => 'Revenue',
            'account_number' => '4',
            'account_category' => AccountCategory::REVENUE,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
            'sort_order' => 4000,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/accounts/tree?category=revenue');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.account_category', 'revenue');
    }

    public function test_can_list_accounts()
    {
        Account::create([
            'code' => 'ACC-1101',
            'name' => 'Cash',
            'account_number' => '1101',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        Account::create([
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

        $response = $this->actingAs($this->user)->getJson('/api/accounts');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_can_filter_accounts_by_category()
    {
        Account::create([
            'code' => 'ACC-1101',
            'name' => 'Cash',
            'account_number' => '1101',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        Account::create([
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

        $response = $this->actingAs($this->user)->getJson('/api/accounts?account_category=expense');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.account_category', 'expense');
    }

    public function test_can_show_account()
    {
        $account = Account::create([
            'code' => 'ACC-1101',
            'name' => 'Cash',
            'account_number' => '1101',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        $resource = new \Modules\Ledger\Http\Resources\AccountResource($account);
        $data = $resource->toArray(request());

        $this->assertEquals('ACC-1101', $data['code']);
        $this->assertEquals('Assets', $data['account_category_label']);
        $this->assertEquals('الأصول', $data['account_category_label_ar']);
    }

    public function test_can_update_account()
    {
        $account = Account::create([
            'code' => 'ACC-1101',
            'name' => 'Cash',
            'account_number' => '1101',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        $service = app(\Modules\Ledger\Services\AccountService::class);
        $dto = \Modules\Ledger\DTOs\AccountDTO::fromRequest([
            'name' => 'Cash on Hand',
            'description' => 'Updated description',
        ]);
        $updated = $service->update($account, $dto);

        $this->assertEquals('Cash on Hand', $updated->name);
        $this->assertDatabaseHas('ledger_accounts', [
            'id' => $account->id,
            'name' => 'Cash on Hand',
        ]);
    }

    public function test_can_delete_account_without_entries()
    {
        $account = Account::create([
            'code' => 'ACC-TEMP',
            'name' => 'Temporary',
            'account_number' => '9999',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        $service = app(\Modules\Ledger\Services\AccountService::class);
        $service->delete($account);

        $this->assertSoftDeleted('ledger_accounts', ['id' => $account->id]);
    }

    public function test_cannot_delete_account_with_entries()
    {
        $account = Account::create([
            'code' => 'ACC-WITH',
            'name' => 'With Entries',
            'account_number' => '8888',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        AccountEntry::create([
            'account_id' => $account->id,
            'batch_id' => 'batch-1',
            'entry_type' => EntryType::DEBIT,
            'amount' => 100,
            'currency' => 'USD',
            'balance_after' => 100,
            'posted_at' => now(),
        ]);

        $service = app(\Modules\Ledger\Services\AccountService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete account with existing entries');

        $service->delete($account);
    }

    public function test_cannot_delete_account_with_children()
    {
        $parent = Account::create([
            'code' => 'GRP-11',
            'name' => 'Current Assets',
            'account_number' => '11',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        Account::create([
            'code' => 'ACC-1101',
            'name' => 'Cash',
            'account_number' => '1101',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'parent_id' => $parent->id,
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        $service = app(\Modules\Ledger\Services\AccountService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete account with child accounts');

        $service->delete($parent);
    }

    public function test_account_resource_has_correct_structure()
    {
        $account = Account::create([
            'code' => 'CAT-5',
            'name' => 'Expenses',
            'account_number' => '5',
            'account_category' => AccountCategory::EXPENSE,
            'type' => AccountType::EXPENSE,
            'currency' => 'USD',
            'current_balance' => 1500.00,
            'held_balance' => 100.00,
            'is_active' => true,
        ]);

        $resource = new \Modules\Ledger\Http\Resources\AccountResource($account);
        $data = $resource->toArray(request());

        $this->assertEquals('CAT-5', $data['code']);
        $this->assertEquals('Expenses', $data['name']);
        $this->assertEquals('expense', $data['account_category']);
        $this->assertEquals('Expenses', $data['account_category_label']);
        $this->assertEquals(1500.0, $data['current_balance']);
        $this->assertEquals(100.0, $data['held_balance']);
        $this->assertEquals(1400.0, $data['available_balance']);
        $this->assertEquals('debit', $data['normal_balance']);
    }

    public function test_can_create_account()
    {
        $payload = [
            'code' => 'ACC-1102',
            'name' => 'Bank Account',
            'account_number' => '1102',
            'account_category' => 'asset',
            'description' => 'Main bank account',
            'currency' => 'USD',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/accounts', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('ledger_accounts', [
            'code' => 'ACC-1102',
            'name' => 'Bank Account',
            'account_category' => 'asset',
            'account_number' => '1102',
        ]);
    }

    public function test_cannot_create_account_with_duplicate_code()
    {
        Account::create([
            'code' => 'ACC-1101',
            'name' => 'Cash',
            'account_number' => '1101',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        $payload = [
            'code' => 'ACC-1101',
            'name' => 'Duplicate',
            'account_number' => '9999',
            'account_category' => 'asset',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/accounts', $payload);

        $response->assertStatus(422);
    }

    public function test_can_create_child_account()
    {
        $parent = Account::create([
            'code' => 'CAT-1',
            'name' => 'Assets',
            'account_number' => '1',
            'account_category' => AccountCategory::ASSET,
            'type' => AccountType::LIABILITY,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        $payload = [
            'code' => 'ACC-1103',
            'name' => 'Petty Cash',
            'account_number' => '1103',
            'account_category' => 'asset',
            'parent_id' => $parent->id,
            'sort_order' => 5,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/accounts', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.parent_id', $parent->id);
        $this->assertDatabaseHas('ledger_accounts', [
            'code' => 'ACC-1103',
            'parent_id' => $parent->id,
        ]);
    }

    public function test_unauthorized_user_cannot_access_accounts()
    {
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)->getJson('/api/accounts');

        $this->assertNotEquals(200, $response->status());
    }
}
