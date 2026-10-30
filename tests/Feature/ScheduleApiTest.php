<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_schedules(): void
    {
        $this->getJson('/api/schedules')->assertUnauthorized();
        $this->postJson('/api/schedules', ['title' => 'x', 'start_date' => '2026-10-30'])->assertUnauthorized();
    }

    public function test_users_only_see_and_change_their_own_schedules(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $schedule = $alice->schedules()->create([
            'title' => 'Aliceの予定',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'workload_value' => 3,
        ]);

        $this->actingAs($alice)->getJson('/api/schedules')->assertOk()->assertJsonCount(1);
        $this->actingAs($alice)->getJson('/api/schedules/today')->assertOk()->assertJsonCount(1);

        $this->actingAs($bob)->getJson('/api/schedules')->assertOk()->assertJsonCount(0);
        $this->actingAs($bob)->getJson('/api/schedules/today')->assertOk()->assertJsonCount(0);
        $this->actingAs($bob)->getJson("/api/schedules/{$schedule->id}")->assertNotFound();
        $this->actingAs($bob)->putJson("/api/schedules/{$schedule->id}", ['title' => '乗っ取り', 'start_date' => '2026-10-30'])->assertNotFound();
        $this->actingAs($bob)->putJson("/api/schedules/{$schedule->id}/check", ['checked' => true])->assertNotFound();
        $this->actingAs($bob)->deleteJson("/api/schedules/{$schedule->id}")->assertNotFound();

        $this->assertSame('Aliceの予定', $schedule->fresh()->title);
    }

    public function test_created_schedules_belong_to_the_current_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/schedules', ['title' => '新しい予定', 'start_date' => '2026-10-30'])
            ->assertCreated()
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('end_date', '2026-10-30');
    }
}
