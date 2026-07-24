<?php

namespace Modules\Deposit\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\Deposit\Entities\Deposit;
use Modules\Deposit\Enums\DepositStatus;
use Modules\Ledger\Entities\Account;
use Modules\Ledger\Enums\AccountType;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepositTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private User $seller;
    private User $otherUser;
    private User $admin;
    private Property $property;
    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);

        $this->role = Role::create(['name' => 'publisher', 'guard_name' => 'web']);
        $depositPerms = [
            'deposits.list', 'deposits.show', 'deposits.create',
            'deposits.edit', 'deposits.delete', 'deposits.release', 'deposits.refund',
        ];
        foreach ($depositPerms as $p) {
            Permission::create(['name' => $p, 'guard_name' => 'web']);
            $this->role->givePermissionTo($p);
        }

        $this->admin = User::factory()->create(['name' => 'Admin User']);
        $adminRole = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());
        $this->admin->assignRole('super-admin');

        $this->buyer = User::factory()->create(['name' => 'Buyer User']);
        $this->buyer->assignRole($this->role);
        $this->seller = User::factory()->create(['name' => 'Seller User']);
        $this->seller->assignRole($this->role);
        $this->otherUser = User::factory()->create(['name' => 'Other User']);
        $this->otherUser->assignRole($this->role);

        $this->property = Property::factory()->create([
            'publisher_id' => $this->seller->id,
            'status' => PropertyStatus::APPROVED,
            'country_id' => $country->id,
            'city_id' => City::first()->id,
        ]);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'property_id' => $this->property->id,
            'seller_id' => $this->seller->id,
            'amount' => 5000.00,
            'currency' => 'SAR',
            'terms' => 'Earnest money for property purchase',
        ], $overrides);
    }

    private function createDeposit(array $attributes = []): Deposit
    {
        return Deposit::factory()->create(array_merge([
            'property_id' => $this->property->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'amount' => 5000.00,
            'currency' => 'SAR',
            'status' => DepositStatus::PENDING,
        ], $attributes));
    }

    private function fundBuyerAccount(float $amount = 10000.00): Account
    {
        return Account::create([
            'code' => 'USR-001-BAL',
            'name' => 'Balance: Buyer User',
            'type' => AccountType::USER_BALANCE,
            'currency' => 'SAR',
            'owner_type' => User::class,
            'owner_id' => $this->buyer->id,
            'current_balance' => $amount,
            'held_balance' => 0,
            'is_active' => true,
        ]);
    }

    private function createEscrowAccount(): Account
    {
        return Account::create([
            'code' => 'LIA-001',
            'name' => 'Liability',
            'type' => AccountType::LIABILITY,
            'currency' => 'SAR',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);
    }

    // -----------------------------------------------------------------
    // CREATE
    // -----------------------------------------------------------------

    public function test_buyer_can_create_deposit(): void
    {
        $response = $this->actingAs($this->buyer)
            ->postJson('/api/dashboard/deposits', $this->validPayload());

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'pending');
        $this->assertEquals(5000.00, (float) $response->json('data.amount'));
        $response->assertJsonPath('data.buyer_id', $this->buyer->id);

        $this->assertDatabaseHas('deposits', [
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'status' => 'pending',
        ]);
    }

    public function test_create_deposit_validates_required_fields(): void
    {
        $response = $this->actingAs($this->buyer)
            ->postJson('/api/dashboard/deposits', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['property_id', 'seller_id', 'amount']);
    }

    public function test_create_deposit_validates_minimum_amount(): void
    {
        $payload = $this->validPayload(['amount' => 0]);

        $response = $this->actingAs($this->buyer)
            ->postJson('/api/dashboard/deposits', $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['amount']);
    }

    public function test_create_deposit_generates_reference_number(): void
    {
        $response = $this->actingAs($this->buyer)
            ->postJson('/api/dashboard/deposits', $this->validPayload());

        $response->assertStatus(201);
        $this->assertNotEmpty($response->json('data.reference_number'));
        $this->assertStringStartsWith('DEP-', $response->json('data.reference_number'));
    }

    // -----------------------------------------------------------------
    // LIST
    // -----------------------------------------------------------------

    public function test_user_lists_their_deposits(): void
    {
        $this->createDeposit(['buyer_id' => $this->buyer->id, 'status' => DepositStatus::PENDING]);
        $this->createDeposit(['buyer_id' => $this->buyer->id, 'status' => DepositStatus::HELD, 'held_at' => now()]);

        $response = $this->actingAs($this->buyer)
            ->getJson('/api/dashboard/deposits');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_buyer_sees_only_their_deposits(): void
    {
        $this->createDeposit(['buyer_id' => $this->buyer->id, 'status' => DepositStatus::PENDING]);
        $this->createDeposit(['buyer_id' => $this->otherUser->id, 'status' => DepositStatus::PENDING]);

        $response = $this->actingAs($this->buyer)
            ->getJson('/api/dashboard/deposits');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('buyer_id');
        $this->assertCount(1, $ids);
        $this->assertEquals($this->buyer->id, $ids->first());
    }

    public function test_user_filters_deposits_by_status(): void
    {
        $this->createDeposit(['buyer_id' => $this->buyer->id, 'status' => DepositStatus::PENDING]);
        $this->createDeposit(['buyer_id' => $this->buyer->id, 'status' => DepositStatus::HELD, 'held_at' => now()]);

        $response = $this->actingAs($this->buyer)
            ->getJson('/api/dashboard/deposits?status=held');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('held', $response->json('data.0.status'));
    }

    // -----------------------------------------------------------------
    // SHOW
    // -----------------------------------------------------------------

    public function test_user_can_view_single_deposit(): void
    {
        $deposit = $this->createDeposit();

        $response = $this->actingAs($this->buyer)
            ->getJson("/api/dashboard/deposits/{$deposit->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.reference_number', $deposit->reference_number);
        $response->assertJsonPath('data.property_id', $this->property->id);
    }

    // -----------------------------------------------------------------
    // UPDATE
    // -----------------------------------------------------------------

    public function test_buyer_can_update_pending_deposit(): void
    {
        $deposit = $this->createDeposit();

        $response = $this->actingAs($this->buyer)
            ->patchJson("/api/dashboard/deposits/{$deposit->id}", [
                'amount' => 7500.00,
                'notes' => 'Updated notes',
            ]);

        $response->assertStatus(200);
        $this->assertEquals(7500.00, $deposit->fresh()->amount);
        $this->assertEquals('Updated notes', $deposit->fresh()->notes);
    }

    public function test_cannot_update_held_deposit(): void
    {
        $deposit = $this->createDeposit(['status' => DepositStatus::HELD, 'held_at' => now()]);

        $response = $this->actingAs($this->buyer)
            ->patchJson("/api/dashboard/deposits/{$deposit->id}", [
                'amount' => 9999.00,
            ]);

        $response->assertStatus(500);
    }

    // -----------------------------------------------------------------
    // PAY (deposit funding & escrow holding)
    // -----------------------------------------------------------------

    public function test_buyer_can_pay_deposit_from_balance(): void
    {
        $deposit = $this->createDeposit(['amount' => 3000.00]);
        $this->fundBuyerAccount(10000.00);
        $this->createEscrowAccount();

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/dashboard/deposits/{$deposit->id}/pay", [
                'payment_method' => 'balance',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'held');
        $this->assertNotNull($response->json('data.held_at'));

        $deposit->refresh();
        $this->assertEquals(DepositStatus::HELD, $deposit->status);
        $this->assertNotNull($deposit->held_at);

        $buyerAccount = Account::where('owner_id', $this->buyer->id)
            ->where('type', AccountType::USER_BALANCE->value)
            ->first();
        $escrowAccount = Account::where('code', 'LIA-001')->first();

        $this->assertEquals(7000.00, (float) $buyerAccount->current_balance);
        $this->assertEquals(3000.00, (float) $escrowAccount->current_balance);
    }

    public function test_pay_fails_with_insufficient_balance(): void
    {
        $deposit = $this->createDeposit(['amount' => 50000.00]);
        $this->fundBuyerAccount(1000.00);
        $this->createEscrowAccount();

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/dashboard/deposits/{$deposit->id}/pay", [
                'payment_method' => 'balance',
            ]);

        $response->assertStatus(500);
        $this->assertEquals(DepositStatus::PENDING, $deposit->fresh()->status);
    }

    public function test_cannot_pay_already_paid_deposit(): void
    {
        $deposit = $this->createDeposit(['status' => DepositStatus::HELD, 'held_at' => now()]);
        $this->fundBuyerAccount(10000.00);
        $this->createEscrowAccount();

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/dashboard/deposits/{$deposit->id}/pay", [
                'payment_method' => 'balance',
            ]);

        $response->assertStatus(500);
    }

    // -----------------------------------------------------------------
    // RELEASE (to seller)
    // -----------------------------------------------------------------

    public function test_admin_can_release_deposit_to_seller(): void
    {
        $this->fundBuyerAccount(10000.00);
        $this->createEscrowAccount();
        $deposit = $this->createDeposit(['amount' => 3000.00]);
        app(\Modules\Deposit\Services\DepositService::class)->pay($deposit->id, 'balance');

        $sellerAccount = Account::create([
            'code' => 'USR-SEL-BAL',
            'name' => 'Balance: Seller',
            'type' => AccountType::USER_BALANCE,
            'currency' => 'SAR',
            'owner_type' => User::class,
            'owner_id' => $this->seller->id,
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/dashboard/deposits/{$deposit->id}/release");

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'released');

        $this->assertEquals(DepositStatus::RELEASED, $deposit->fresh()->status);
        $this->assertEquals(3000.00, (float) $sellerAccount->fresh()->current_balance);
    }

    public function test_cannot_release_non_held_deposit(): void
    {
        $deposit = $this->createDeposit();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/dashboard/deposits/{$deposit->id}/release");

        $response->assertStatus(500);
    }

    // -----------------------------------------------------------------
    // REFUND (back to buyer)
    // -----------------------------------------------------------------

    public function test_admin_can_refund_deposit_to_buyer(): void
    {
        $this->fundBuyerAccount(10000.00);
        $this->createEscrowAccount();
        $deposit = $this->createDeposit(['amount' => 3000.00]);
        app(\Modules\Deposit\Services\DepositService::class)->pay($deposit->id, 'balance');

        $response = $this->actingAs($this->admin)
            ->postJson("/api/dashboard/deposits/{$deposit->id}/refund");

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'refunded');

        $this->assertEquals(DepositStatus::REFUNDED, $deposit->fresh()->status);

        $buyerAccount = Account::where('owner_id', $this->buyer->id)
            ->where('type', AccountType::USER_BALANCE->value)
            ->first();
        $this->assertEquals(10000.00, (float) $buyerAccount->current_balance);
    }

    // -----------------------------------------------------------------
    // CANCEL
    // -----------------------------------------------------------------

    public function test_buyer_can_cancel_pending_deposit(): void
    {
        $deposit = $this->createDeposit();

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/dashboard/deposits/{$deposit->id}/cancel", [
                'reason' => 'Changed my mind',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'cancelled');
        $response->assertJsonPath('data.cancellation_reason', 'Changed my mind');

        $this->assertEquals(DepositStatus::CANCELLED, $deposit->fresh()->status);
    }

    public function test_cannot_cancel_held_deposit(): void
    {
        $deposit = $this->createDeposit(['status' => DepositStatus::HELD, 'held_at' => now()]);

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/dashboard/deposits/{$deposit->id}/cancel", [
                'reason' => 'Trying to cancel',
            ]);

        $response->assertStatus(500);
        $this->assertEquals(DepositStatus::HELD, $deposit->fresh()->status);
    }

    public function test_cancel_requires_reason(): void
    {
        $deposit = $this->createDeposit();

        $response = $this->actingAs($this->buyer)
            ->postJson("/api/dashboard/deposits/{$deposit->id}/cancel", []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['reason']);
    }

    // -----------------------------------------------------------------
    // DELETE (soft delete)
    // -----------------------------------------------------------------

    public function test_cannot_delete_held_deposit(): void
    {
        $deposit = $this->createDeposit(['status' => DepositStatus::HELD, 'held_at' => now()]);

        $response = $this->actingAs($this->buyer)
            ->deleteJson("/api/dashboard/deposits/{$deposit->id}");

        $response->assertStatus(500);
        $this->assertNotSoftDeleted($deposit);
    }

    public function test_can_delete_cancelled_deposit(): void
    {
        $deposit = $this->createDeposit([
            'status' => DepositStatus::CANCELLED,
            'cancelled_at' => now(),
        ]);

        $response = $this->actingAs($this->buyer)
            ->deleteJson("/api/dashboard/deposits/{$deposit->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted($deposit);
    }

    public function test_can_delete_refunded_deposit(): void
    {
        $deposit = $this->createDeposit(['status' => DepositStatus::REFUNDED, 'refunded_at' => now()]);

        $response = $this->actingAs($this->buyer)
            ->deleteJson("/api/dashboard/deposits/{$deposit->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted($deposit);
    }

    // -----------------------------------------------------------------
    // MY DEPOSITS / MY SALES
    // -----------------------------------------------------------------

    public function test_my_deposits_returns_only_buyer_deposits(): void
    {
        $this->createDeposit(['buyer_id' => $this->buyer->id, 'status' => DepositStatus::PENDING]);
        $this->createDeposit(['buyer_id' => $this->otherUser->id, 'status' => DepositStatus::PENDING]);

        $response = $this->actingAs($this->buyer)
            ->getJson('/api/dashboard/my-deposits');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->buyer->id, $response->json('data.0.buyer_id'));
    }

    public function test_my_sales_returns_only_seller_deposits(): void
    {
        $this->createDeposit(['seller_id' => $this->seller->id, 'status' => DepositStatus::PENDING]);
        $this->createDeposit(['seller_id' => $this->otherUser->id, 'status' => DepositStatus::PENDING]);

        $response = $this->actingAs($this->seller)
            ->getJson('/api/dashboard/my-sales');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->seller->id, $response->json('data.0.seller_id'));
    }

    // -----------------------------------------------------------------
    // STATUS LIFECYCLE
    // -----------------------------------------------------------------

    public function test_full_deposit_lifecycle(): void
    {
        $this->fundBuyerAccount(10000.00);
        $this->createEscrowAccount();

        $response = $this->actingAs($this->buyer)
            ->postJson('/api/dashboard/deposits', $this->validPayload(['amount' => 5000]));
        $response->assertStatus(201);
        $depositId = $response->json('data.id');
        $this->assertEquals('pending', $response->json('data.status'));

        $payResponse = $this->actingAs($this->buyer)
            ->postJson("/api/dashboard/deposits/{$depositId}/pay", ['payment_method' => 'balance']);
        $payResponse->assertStatus(200);
        $this->assertEquals('held', $payResponse->json('data.status'));

        Account::create([
            'code' => 'USR-SEL-BAL',
            'name' => 'Balance: Seller',
            'type' => AccountType::USER_BALANCE,
            'currency' => 'SAR',
            'owner_type' => User::class,
            'owner_id' => $this->seller->id,
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        $releaseResponse = $this->actingAs($this->admin)
            ->postJson("/api/dashboard/deposits/{$depositId}/release");
        $releaseResponse->assertStatus(200);
        $this->assertEquals('released', $releaseResponse->json('data.status'));

        $this->assertDatabaseHas('deposits', [
            'id' => $depositId,
            'status' => 'released',
        ]);
    }
}
