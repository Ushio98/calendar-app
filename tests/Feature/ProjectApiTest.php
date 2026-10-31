<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_projects(): void
    {
        $this->getJson('/api/projects')->assertUnauthorized();
    }

    public function test_project_can_hold_multiple_schedules_and_one_dated_milestone(): void
    {
        $user = User::factory()->create();

        $projectId = $this->actingAs($user)->postJson('/api/projects', [
            'name' => '新商品',
            'milestone_type' => 'release',
            'milestone_date' => '2026-12-01',
        ])
            ->assertCreated()
            ->assertJsonPath('milestone_type', 'release')
            ->assertJsonPath('milestone_date', '2026-12-01')
            ->json('id');

        foreach (['設計', '実装'] as $title) {
            $this->actingAs($user)
                ->postJson('/api/schedules', ['title' => $title, 'start_date' => '2026-11-01', 'project_id' => $projectId])
                ->assertCreated()
                ->assertJsonPath('project_id', $projectId);
        }

        $this->actingAs($user)->getJson('/api/projects')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonCount(2, '0.schedules');
    }

    public function test_every_milestone_type_can_be_saved(): void
    {
        $user = User::factory()->create();

        foreach (['delivery', 'release', 'sale', 'event'] as $type) {
            $this->actingAs($user)
                ->postJson('/api/projects', ['name' => $type, 'milestone_type' => $type, 'milestone_date' => '2026-12-01'])
                ->assertCreated()
                ->assertJsonPath('milestone_type', $type);
        }
    }

    public function test_milestone_type_and_date_must_be_set_together(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/projects', ['name' => '日付だけ', 'milestone_date' => '2026-12-01'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('milestone_type');

        $this->actingAs($user)
            ->postJson('/api/projects', ['name' => '種類だけ', 'milestone_type' => 'sale'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('milestone_date');

        $this->actingAs($user)
            ->postJson('/api/projects', ['name' => '不正な種類', 'milestone_type' => 'other', 'milestone_date' => '2026-12-01'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('milestone_type');

        $this->actingAs($user)
            ->postJson('/api/projects', ['name' => '日付なし'])
            ->assertCreated();
    }

    public function test_project_names_are_unique_per_user(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->actingAs($alice)->postJson('/api/projects', ['name' => '共通名'])->assertCreated();
        $this->actingAs($alice)->postJson('/api/projects', ['name' => '共通名'])->assertUnprocessable();
        $this->actingAs($bob)->postJson('/api/projects', ['name' => '共通名'])->assertCreated();
    }

    public function test_users_cannot_touch_or_use_other_users_projects(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $project = $alice->projects()->create(['name' => 'Aliceのプロジェクト']);

        $this->actingAs($bob)->getJson('/api/projects')->assertOk()->assertJsonCount(0);
        $this->actingAs($bob)->getJson("/api/projects/{$project->id}")->assertNotFound();
        $this->actingAs($bob)->putJson("/api/projects/{$project->id}", ['name' => '乗っ取り'])->assertNotFound();
        $this->actingAs($bob)->deleteJson("/api/projects/{$project->id}")->assertNotFound();

        $this->actingAs($bob)
            ->postJson('/api/schedules', ['title' => '混入', 'start_date' => '2026-11-01', 'project_id' => $project->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('project_id');
    }

    public function test_deleting_a_project_keeps_its_schedules(): void
    {
        $user = User::factory()->create();
        $project = $user->projects()->create(['name' => '削除する']);
        $schedule = $user->schedules()->create([
            'project_id' => $project->id,
            'title' => '残る予定',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-01',
        ]);

        $this->actingAs($user)->deleteJson("/api/projects/{$project->id}")->assertNoContent();

        $this->assertNull($schedule->fresh()->project_id);
    }
}
