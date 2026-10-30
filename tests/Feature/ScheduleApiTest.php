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

    public function test_fixed_workload_applies_to_every_day(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/schedules', [
                'title' => '毎日の読書',
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
                'workload_mode' => 'fixed',
                'workload_value' => 10,
                'workload_unit' => '1',
            ])
            ->assertCreated()
            ->assertJsonPath('workload_mode', 'fixed')
            ->assertJsonPath('workload_value', 10);

        $this->actingAs($user)->getJson('/api/schedules/today')
            ->assertOk()
            ->assertJsonPath('0.today_workload', 10);
    }

    public function test_daily_workloads_can_differ_per_day(): void
    {
        $user = User::factory()->create();
        $yesterday = now()->subDay()->toDateString();
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $response = $this->actingAs($user)
            ->postJson('/api/schedules', [
                'title' => '企画書',
                'start_date' => $yesterday,
                'end_date' => $tomorrow,
                'workload_mode' => 'daily',
                'workload_value' => 99, // 日ごとに設定する場合は無視される
                'workload_unit' => '1',
                'daily_workloads' => [
                    ['date' => $yesterday, 'value' => 5],
                    ['date' => $today, 'value' => 12],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('workload_mode', 'daily')
            ->assertJsonPath('workload_value', null)
            ->assertJsonPath("daily_workloads.{$yesterday}", 5)
            ->assertJsonPath("daily_workloads.{$today}", 12);

        $this->actingAs($user)->getJson('/api/schedules/today')
            ->assertOk()
            ->assertJsonPath('0.today_workload', 12);

        // 期間を明日だけに縮めると、期間外の日の作業量は消える
        $id = $response->json('id');
        $this->actingAs($user)
            ->putJson("/api/schedules/{$id}", ['title' => '企画書', 'start_date' => $tomorrow, 'end_date' => $tomorrow])
            ->assertOk()
            ->assertJsonPath('workload_mode', 'daily')
            ->assertJsonPath('daily_workloads', []);
    }

    public function test_days_without_a_daily_workload_have_no_work_today(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/schedules', [
            'title' => '明日だけ作業',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'workload_mode' => 'daily',
            'daily_workloads' => [['date' => now()->addDay()->toDateString(), 'value' => 3]],
        ])->assertCreated();

        $this->actingAs($user)->getJson('/api/schedules/today')
            ->assertOk()
            ->assertJsonPath('0.today_workload', null);
    }

    public function test_daily_workloads_must_be_inside_the_period_and_not_empty(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/schedules', [
            'title' => '期間外',
            'start_date' => '2026-10-30',
            'end_date' => '2026-10-31',
            'workload_mode' => 'daily',
            'daily_workloads' => [['date' => '2026-11-01', 'value' => 3]],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.daily_workloads.0', '作業量を入れた日が、予定の期間から外れています。');

        $this->actingAs($user)->postJson('/api/schedules', [
            'title' => '空',
            'start_date' => '2026-10-30',
            'workload_mode' => 'daily',
            'daily_workloads' => [],
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.daily_workloads.0', '作業量を1日以上入力してください。');
    }

    public function test_switching_from_daily_to_none_removes_daily_workloads(): void
    {
        $user = User::factory()->create();

        $id = $this->actingAs($user)->postJson('/api/schedules', [
            'title' => '切り替え',
            'start_date' => '2026-10-30',
            'workload_mode' => 'daily',
            'daily_workloads' => [['date' => '2026-10-30', 'value' => 3]],
        ])->json('id');

        $this->actingAs($user)
            ->putJson("/api/schedules/{$id}", ['title' => '切り替え', 'start_date' => '2026-10-30', 'workload_mode' => 'none'])
            ->assertOk()
            ->assertJsonPath('workload_mode', 'none')
            ->assertJsonPath('daily_workloads', []);

        $this->assertDatabaseCount('schedule_workloads', 0);
    }

    public function test_requests_without_workload_mode_keep_working(): void
    {
        $user = User::factory()->create();

        // 以前の形式（workload_value だけ）は「毎日同じ」として扱う
        $this->actingAs($user)
            ->postJson('/api/schedules', ['title' => '旧形式', 'start_date' => '2026-10-30', 'workload_value' => 7])
            ->assertCreated()
            ->assertJsonPath('workload_mode', 'fixed');

        $this->actingAs($user)
            ->postJson('/api/schedules', ['title' => '作業量なし', 'start_date' => '2026-10-30'])
            ->assertCreated()
            ->assertJsonPath('workload_mode', 'none');
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
