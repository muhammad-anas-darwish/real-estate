<?php

namespace Modules\ServiceProvider\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\Ledger\Entities\Account;
use Modules\Ledger\Enums\AccountType;
use Modules\ServiceProvider\Entities\ServiceCommissionConfig;
use Tests\TestCase;

class ProviderBillingTest extends TestCase
{
    use RefreshDatabase;

    protected User $client;

    protected User $providerUser;

    protected City $city;

    protected function setUp(): void
    {
        parent::setUp();

        $country = Country::factory()->create();
        $this->city = City::factory()->create(['country_id' => $country->id]);

        $this->client = User::factory()->create();
        Account::create([
            'code' => 'USR-001-BAL',
            'name' => 'Balance: '.$this->client->name,
            'type' => AccountType::USER_BALANCE,
            'currency' => 'USD',
            'current_balance' => 1000.00,
            'held_balance' => 0,
            'is_active' => true,
            'owner_type' => User::class,
            'owner_id' => $this->client->id,
        ]);

        $this->providerUser = User::factory()->create();
        Account::create([
            'code' => 'USR-002-BAL',
            'name' => 'Balance: '.$this->providerUser->name,
            'type' => AccountType::USER_BALANCE,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
            'owner_type' => User::class,
            'owner_id' => $this->providerUser->id,
        ]);

        ServiceCommissionConfig::create([
            'service_type' => 'photography',
            'commission_type' => 'percentage',
            'commission_value' => 20.00,
        ]);

        Account::create([
            'code' => 'PLF-001',
            'name' => 'Platform Fee',
            'type' => AccountType::PLATFORM_FEE,
            'currency' => 'USD',
            'current_balance' => 0,
            'held_balance' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->providerUser)->postJson('/api/service-provider/register', [
            'type' => 'photographer',
            'coverage_city_ids' => [$this->city->id],
        ]);

        \Modules\ServiceProvider\Entities\ServiceProviderProfile::first()->update(['is_verified' => true]);
    }

    public function test_payment_settles_on_service_completion()
    {
        $req = $this->actingAs($this->client)->postJson('/api/service-requests', [
            'service_type' => 'photography',
            'provider_id' => \Modules\ServiceProvider\Entities\ServiceProviderProfile::first()->id,
            'price' => 100.00,
        ]);

        $id = $req->json('data.id');

        $this->actingAs($this->providerUser)->postJson("/api/service-provider/service-requests/{$id}/accept");
        $this->actingAs($this->providerUser)->postJson("/api/service-provider/service-requests/{$id}/start");

        $response = $this->actingAs($this->providerUser)
            ->postJson("/api/service-provider/service-requests/{$id}/complete");

        $response->assertStatus(200);

        $this->assertDatabaseHas('service_requests', [
            'id' => $id,
            'is_paid' => true,
            'platform_fee' => 20.00,
            'provider_earnings' => 80.00,
        ]);

        $clientBalance = Account::where('owner_id', $this->client->id)
            ->where('owner_type', User::class)
            ->first()->current_balance;
        $this->assertEquals(900.00, (float) $clientBalance, 'Client should have been debited 100');

        $providerBalance = Account::where('owner_id', $this->providerUser->id)
            ->where('owner_type', User::class)
            ->first()->current_balance;
        $this->assertEquals(80.00, (float) $providerBalance, 'Provider should have earned 80');
    }

    public function test_can_get_balance()
    {
        $response = $this->actingAs($this->providerUser)
            ->getJson('/api/service-provider/balance');

        $response->assertStatus(200);
        $response->assertJsonPath('data.current_balance', 0);
    }

    public function test_cannot_withdraw_more_than_balance()
    {
        $response = $this->actingAs($this->providerUser)
            ->postJson('/api/service-provider/withdraw', ['amount' => 500]);

        $response->assertStatus(500);
    }

    public function test_can_withdraw_available_balance()
    {
        Account::where('owner_id', $this->providerUser->id)
            ->where('owner_type', User::class)
            ->update(['current_balance' => 200.00]);

        $response = $this->actingAs($this->providerUser)
            ->postJson('/api/service-provider/withdraw', ['amount' => 100.00]);

        $response->assertStatus(200);

        $balance = Account::where('owner_id', $this->providerUser->id)
            ->where('owner_type', User::class)
            ->first()->current_balance;
        $this->assertEquals(100.00, (float) $balance);
    }

    public function test_commission_config_returns_default_rate()
    {
        $rate = ServiceCommissionConfig::getCommissionRate('unknown_type');

        $this->assertEquals(20.0, $rate);
    }
}
