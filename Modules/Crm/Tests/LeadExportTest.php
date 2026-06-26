<?php

namespace Modules\Crm\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Entities\User;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\LeadSource;
use Modules\Crm\Enums\LeadStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;

    protected User $otherTrader;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'leads.list', 'leads.show', 'leads.create', 'leads.edit', 'leads.delete',
            'leads.change-status', 'leads.archive', 'leads.restore', 'leads.export',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $traderRole = Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $traderRole->givePermissionTo($permissions);

        $this->trader = User::factory()->create();
        $this->trader->assignRole('trader');

        $this->otherTrader = User::factory()->create();
        $this->otherTrader->assignRole('trader');
    }

    public function test_export_with_no_leads_returns_header_only(): void
    {
        $response = $this->actingAs($this->trader)
            ->get('/api/dashboard/crm/leads/export');

        $response->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('leads-export-', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));

        $body = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($body)));
        $this->assertCount(1, $lines);
        $this->assertStringContainsString('ID', $lines[0]);
        $this->assertStringContainsString('Name', $lines[0]);
    }

    public function test_export_with_5_leads_returns_6_rows(): void
    {
        Lead::factory()->count(5)->create(['trader_id' => $this->trader->id]);

        $response = $this->actingAs($this->trader)
            ->get('/api/dashboard/crm/leads/export');

        $response->assertStatus(200);
        $body = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($body)));
        $this->assertCount(6, $lines);
    }

    public function test_other_trader_leads_not_in_export(): void
    {
        Lead::factory()->count(3)->create(['trader_id' => $this->trader->id]);
        Lead::factory()->count(2)->create(['trader_id' => $this->otherTrader->id]);

        $response = $this->actingAs($this->trader)
            ->get('/api/dashboard/crm/leads/export');

        $response->assertStatus(200);
        $body = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($body)));
        $this->assertCount(4, $lines);
    }

    public function test_filter_by_status_reflects_in_export(): void
    {
        Lead::factory()->count(3)->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::NEW->value,
        ]);
        Lead::factory()->count(2)->create([
            'trader_id' => $this->trader->id,
            'status' => LeadStatus::CONTACTED->value,
        ]);

        $response = $this->actingAs($this->trader)
            ->get('/api/dashboard/crm/leads/export?status=new');

        $response->assertStatus(200);
        $body = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($body)));
        $this->assertCount(4, $lines);
    }

    public function test_content_disposition_header_has_correct_filename_pattern(): void
    {
        $response = $this->actingAs($this->trader)
            ->get('/api/dashboard/crm/leads/export');

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertMatchesRegularExpression(
            '/attachment; filename="leads-export-\d{8}-\d{6}\.csv"/',
            $disposition
        );
    }

    public function test_archived_leads_are_excluded_from_export(): void
    {
        Lead::factory()->count(2)->create([
            'trader_id' => $this->trader->id,
            'archived_at' => null,
        ]);
        Lead::factory()->create([
            'trader_id' => $this->trader->id,
            'archived_at' => now(),
        ]);

        $response = $this->actingAs($this->trader)
            ->get('/api/dashboard/crm/leads/export');

        $response->assertStatus(200);
        $body = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($body)));
        $this->assertCount(3, $lines);
    }
}
