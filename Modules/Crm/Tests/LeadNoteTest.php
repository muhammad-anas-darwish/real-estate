<?php

namespace Modules\Crm\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Auth\Entities\User;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Entities\LeadNote;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeadNoteTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;

    protected User $otherTrader;

    protected Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'leads.list', 'leads.show', 'leads.create', 'leads.edit', 'leads.delete',
            'leads.change-status', 'leads.archive', 'leads.restore', 'leads.export',
            'lead_notes.list', 'lead_notes.show', 'lead_notes.create', 'lead_notes.edit', 'lead_notes.delete',
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

        $this->lead = Lead::factory()->create(['trader_id' => $this->trader->id]);
    }

    public function test_trader_can_create_note_with_valid_body(): void
    {
        $payload = ['body' => 'Customer asked for a follow-up call tomorrow.'];

        $response = $this->actingAs($this->trader)
            ->postJson("/api/dashboard/crm/leads/{$this->lead->id}/notes", $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.body', $payload['body']);
        $response->assertJsonPath('data.is_editable', true);
        $response->assertJsonPath('data.is_deletable', true);
        $response->assertJsonPath('data.author.id', $this->trader->id);
        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => $this->lead->id,
            'author_id' => $this->trader->id,
            'body' => $payload['body'],
        ]);
    }

    public function test_create_note_with_empty_body_returns_422(): void
    {
        $response = $this->actingAs($this->trader)
            ->postJson("/api/dashboard/crm/leads/{$this->lead->id}/notes", ['body' => '']);

        $response->assertStatus(422);
    }

    public function test_can_list_notes_for_lead_with_author_included(): void
    {
        LeadNote::factory()->count(2)->create([
            'lead_id' => $this->lead->id,
            'author_id' => $this->trader->id,
            'body' => 'Note 1',
        ]);
        LeadNote::factory()->create([
            'lead_id' => $this->lead->id,
            'author_id' => $this->trader->id,
            'body' => 'Note 2',
        ]);

        $response = $this->actingAs($this->trader)
            ->getJson("/api/dashboard/crm/leads/{$this->lead->id}/notes");

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.author.id', $this->trader->id);
        $response->assertJsonPath('data.0.author.name', $this->trader->name);
    }

    public function test_author_can_update_own_note_within_24_hours(): void
    {
        $note = LeadNote::factory()->create([
            'lead_id' => $this->lead->id,
            'author_id' => $this->trader->id,
            'body' => 'Original',
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($this->trader)
            ->patchJson("/api/dashboard/crm/notes/{$note->id}", ['body' => 'Updated']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('lead_notes', [
            'id' => $note->id,
            'body' => 'Updated',
        ]);
    }

    public function test_other_user_cannot_update_note_returns_403(): void
    {
        $note = LeadNote::factory()->create([
            'lead_id' => $this->lead->id,
            'author_id' => $this->trader->id,
            'body' => 'Original',
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($this->otherTrader)
            ->patchJson("/api/dashboard/crm/notes/{$note->id}", ['body' => 'Hacked']);

        $response->assertStatus(403);
        $this->assertDatabaseHas('lead_notes', ['id' => $note->id, 'body' => 'Original']);
    }

    public function test_note_older_than_24_hours_cannot_be_updated_returns_422(): void
    {
        $note = LeadNote::factory()->create([
            'lead_id' => $this->lead->id,
            'author_id' => $this->trader->id,
            'body' => 'Original',
            'created_at' => now()->subHours(25),
        ]);

        $response = $this->actingAs($this->trader)
            ->patchJson("/api/dashboard/crm/notes/{$note->id}", ['body' => 'Updated']);

        $response->assertStatus(422);
        $this->assertDatabaseHas('lead_notes', ['id' => $note->id, 'body' => 'Original']);
    }

    public function test_author_can_delete_own_note(): void
    {
        $note = LeadNote::factory()->create([
            'lead_id' => $this->lead->id,
            'author_id' => $this->trader->id,
        ]);

        $response = $this->actingAs($this->trader)
            ->deleteJson("/api/dashboard/crm/notes/{$note->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('lead_notes', ['id' => $note->id]);
    }

    public function test_other_user_cannot_delete_note_returns_403(): void
    {
        $note = LeadNote::factory()->create([
            'lead_id' => $this->lead->id,
            'author_id' => $this->trader->id,
        ]);

        $response = $this->actingAs($this->otherTrader)
            ->deleteJson("/api/dashboard/crm/notes/{$note->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('lead_notes', ['id' => $note->id, 'deleted_at' => null]);
    }

    public function test_other_trader_cannot_access_lead_notes_returns_403(): void
    {
        $response = $this->actingAs($this->otherTrader)
            ->getJson("/api/dashboard/crm/leads/{$this->lead->id}/notes");

        $response->assertStatus(403);
    }

    public function test_lead_last_activity_at_updates_on_note_add_edit_delete(): void
    {
        $initial = now()->subDays(2);
        $this->lead->update(['last_activity_at' => $initial]);

        Carbon::setTestNow(now()->addHour());

        $createResponse = $this->actingAs($this->trader)
            ->postJson("/api/dashboard/crm/leads/{$this->lead->id}/notes", ['body' => 'A new note']);
        $createResponse->assertStatus(201);
        $this->assertNotEquals(
            $initial->toDateTimeString(),
            Lead::find($this->lead->id)->last_activity_at->toDateTimeString()
        );

        $note = LeadNote::where('lead_id', $this->lead->id)->first();

        Carbon::setTestNow(now()->addHour());

        $updateResponse = $this->actingAs($this->trader)
            ->patchJson("/api/dashboard/crm/notes/{$note->id}", ['body' => 'Updated note']);
        $updateResponse->assertStatus(200);
        $this->assertNotEquals(
            $initial->toDateTimeString(),
            Lead::find($this->lead->id)->last_activity_at->toDateTimeString()
        );

        Carbon::setTestNow(now()->addHour());

        $deleteResponse = $this->actingAs($this->trader)
            ->deleteJson("/api/dashboard/crm/notes/{$note->id}");
        $deleteResponse->assertStatus(200);
        $this->assertNotEquals(
            $initial->toDateTimeString(),
            Lead::find($this->lead->id)->last_activity_at->toDateTimeString()
        );
    }
}
