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

    public function test_actual_progress_is_recorded_per_day_and_can_differ_from_the_plan(): void
    {
        $user = User::factory()->create();
        $yesterday = now()->subDay()->toDateString();
        $today = now()->toDateString();

        $schedule = $user->schedules()->create([
            'title' => '執筆',
            'start_date' => $yesterday,
            'end_date' => $today,
            'workload_mode' => 'fixed',
            'workload_value' => 2000,
        ]);

        $this->actingAs($user)->putJson("/api/schedules/{$schedule->id}/actual", ['date' => $yesterday, 'value' => 1500])
            ->assertOk()
            ->assertJsonPath('value', 1500);
        $this->actingAs($user)->putJson("/api/schedules/{$schedule->id}/actual", ['date' => $today, 'value' => 0])->assertOk();

        // 同じ日にもう一度入れると上書きされる
        $this->actingAs($user)->putJson("/api/schedules/{$schedule->id}/actual", ['date' => $yesterday, 'value' => 1800])->assertOk();

        $this->actingAs($user)->getJson('/api/schedules')
            ->assertOk()
            ->assertJsonPath('0.workload_value', 2000)
            ->assertJsonPath("0.actual_workloads.{$yesterday}", 1800)
            ->assertJsonPath("0.actual_workloads.{$today}", 0);

        // 空にすると記録が消える
        $this->actingAs($user)->putJson("/api/schedules/{$schedule->id}/actual", ['date' => $yesterday, 'value' => null])->assertOk();
        $this->assertDatabaseCount('schedule_actuals', 1);
    }

    public function test_actual_progress_is_only_accepted_on_valid_days(): void
    {
        $user = User::factory()->create();
        $tomorrow = now()->addDay()->toDateString();

        $schedule = $user->schedules()->create([
            'title' => '執筆',
            'start_date' => now()->toDateString(),
            'end_date' => $tomorrow,
            'workload_mode' => 'fixed',
            'workload_value' => 10,
        ]);

        $this->actingAs($user)->putJson("/api/schedules/{$schedule->id}/actual", ['date' => $tomorrow, 'value' => 1])
            ->assertUnprocessable()
            ->assertJsonPath('errors.date.0', '未来の日付には実績を入力できません。');

        $this->actingAs($user)->putJson("/api/schedules/{$schedule->id}/actual", ['date' => now()->subDay()->toDateString(), 'value' => 1])
            ->assertUnprocessable()
            ->assertJsonPath('errors.date.0', '予定の期間外の日付には実績を入力できません。');

        $this->actingAs($user)->putJson("/api/schedules/{$schedule->id}/actual", ['date' => now()->toDateString(), 'value' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('value');

        $noWorkload = $user->schedules()->create([
            'title' => '会議',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $this->actingAs($user)->putJson("/api/schedules/{$noWorkload->id}/actual", ['date' => now()->toDateString(), 'value' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('value');
    }

    public function test_other_users_cannot_record_actual_progress(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $schedule = $alice->schedules()->create([
            'title' => '執筆',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'workload_mode' => 'fixed',
            'workload_value' => 10,
        ]);

        $this->actingAs($bob)->putJson("/api/schedules/{$schedule->id}/actual", ['date' => now()->toDateString(), 'value' => 1])
            ->assertNotFound();
    }

    public function test_shortening_the_period_hides_actuals_without_deleting_them(): void
    {
        $user = User::factory()->create();
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $schedule = $user->schedules()->create([
            'title' => '執筆',
            'start_date' => $yesterday,
            'end_date' => $today,
            'workload_mode' => 'fixed',
            'workload_value' => 10,
        ]);
        $schedule->actuals()->create(['date' => $yesterday, 'value' => 7]);

        $this->actingAs($user)
            ->putJson("/api/schedules/{$schedule->id}", ['title' => '執筆', 'start_date' => $today, 'end_date' => $today])
            ->assertOk()
            ->assertJsonPath('actual_workloads', []);

        $this->assertDatabaseCount('schedule_actuals', 1);
    }

    public function test_tasks_can_be_chosen_regardless_of_workload(): void
    {
        $user = User::factory()->create();
        $today = now()->toDateString();
        $make = fn (array $body) => $this->actingAs($user)
            ->postJson('/api/schedules', ['start_date' => $today, 'end_date' => $today, ...$body])
            ->assertCreated();

        // 作業量なしでも選べる
        $make(['title' => 'なし・オン', 'workload_mode' => 'none', 'show_in_tasks' => true])
            ->assertJsonPath('show_in_tasks', true);
        // 作業量ありでも外せる
        $make(['title' => 'あり・オフ', 'workload_mode' => 'fixed', 'workload_value' => 5, 'show_in_tasks' => false]);
        // 指定がなければ作業量の有無に従う
        $make(['title' => 'あり・指定なし', 'workload_mode' => 'fixed', 'workload_value' => 5])
            ->assertJsonPath('show_in_tasks', true);
        $make(['title' => 'なし・指定なし'])->assertJsonPath('show_in_tasks', false);
        // 日ごとの設定で今日が空欄なら、オンでも今日は出さない
        $make([
            'title' => '日ごと・今日は空欄',
            'end_date' => now()->addDay()->toDateString(),
            'workload_mode' => 'daily',
            'daily_workloads' => [['date' => now()->addDay()->toDateString(), 'value' => 3]],
            'show_in_tasks' => true,
        ]);

        $tasks = collect($this->actingAs($user)->getJson('/api/schedules/today')->assertOk()->json())
            ->filter(fn (array $schedule) => $schedule['is_today_task'])
            ->pluck('title')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(collect(['なし・オン', 'あり・指定なし'])->sort()->values()->all(), $tasks);
    }

    public function test_updating_without_show_in_tasks_keeps_the_choice(): void
    {
        $user = User::factory()->create();
        $today = now()->toDateString();
        $schedule = $user->schedules()->create([
            'title' => '会議',
            'start_date' => $today,
            'end_date' => $today,
            'show_in_tasks' => true,
        ]);

        $this->actingAs($user)
            ->putJson("/api/schedules/{$schedule->id}", ['title' => '定例会議', 'start_date' => $today, 'end_date' => $today])
            ->assertOk()
            ->assertJsonPath('show_in_tasks', true);
    }

    public function test_tasks_without_a_workload_can_be_checked_off(): void
    {
        $user = User::factory()->create();
        $today = now()->toDateString();
        $schedule = $user->schedules()->create([
            'title' => '会議',
            'start_date' => $today,
            'end_date' => $today,
            'show_in_tasks' => true,
        ]);

        $this->actingAs($user)->putJson("/api/schedules/{$schedule->id}/check", ['checked' => true])->assertOk();

        $this->actingAs($user)->getJson('/api/schedules/today')
            ->assertOk()
            ->assertJsonPath('0.is_today_task', true)
            ->assertJsonPath('0.is_checked', true)
            ->assertJsonPath('0.today_workload', null);
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
