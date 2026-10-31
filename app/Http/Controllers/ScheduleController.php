<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the schedules.
     */
    public function index(Request $request)
    {
        return $request->user()->schedules()->with('workloads')->orderBy('start_date')->get();
    }

    /**
     * Display the schedules that include today.
     */
    public function today(Request $request)
    {
        $today = now()->toDateString();

        return $request->user()->schedules()
            ->with(['workloads', 'project:id,name'])
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->withExists(['checks as is_checked' => fn ($query) => $query->whereDate('date', $today)])
            ->orderBy('start_date')
            ->get()
            // 今日の作業量（「毎日同じ」ならその量、「日ごとに設定」なら今日の分）
            ->each(fn (Schedule $schedule) => $schedule->setAttribute('today_workload', $schedule->workloadOn($today)));
    }

    /**
     * Mark the schedule as done (or not done) for today.
     */
    public function check(Request $request, Schedule $schedule)
    {
        $this->ensureOwner($request, $schedule);

        $checked = $request->validate(['checked' => ['required', 'boolean']])['checked'];
        $today = now()->toDateString();

        if ($checked) {
            $schedule->checks()->firstOrCreate(['date' => $today]);
        } else {
            $schedule->checks()->whereDate('date', $today)->delete();
        }

        return response()->json(['id' => $schedule->id, 'is_checked' => $checked]);
    }

    /**
     * Store a newly created schedule.
     */
    public function store(Request $request)
    {
        [$attributes, $dailyWorkloads] = $this->validated($request, null);

        $schedule = DB::transaction(function () use ($request, $attributes, $dailyWorkloads) {
            $schedule = $request->user()->schedules()->create($attributes);
            $this->syncDailyWorkloads($schedule, $dailyWorkloads);

            return $schedule;
        });

        return response()->json($schedule->load('workloads'), 201);
    }

    /**
     * Display the specified schedule.
     */
    public function show(Request $request, Schedule $schedule)
    {
        $this->ensureOwner($request, $schedule);

        return $schedule->load('workloads');
    }

    /**
     * Update the specified schedule.
     */
    public function update(Request $request, Schedule $schedule)
    {
        $this->ensureOwner($request, $schedule);

        [$attributes, $dailyWorkloads] = $this->validated($request, $schedule);

        DB::transaction(function () use ($schedule, $attributes, $dailyWorkloads) {
            $schedule->update($attributes);
            $this->syncDailyWorkloads($schedule, $dailyWorkloads);
        });

        return $schedule->load('workloads');
    }

    /**
     * Remove the specified schedule.
     */
    public function destroy(Request $request, Schedule $schedule)
    {
        $this->ensureOwner($request, $schedule);

        $schedule->delete();

        return response()->noContent();
    }

    /**
     * Respond with 404 when the schedule belongs to another user, so its existence is not revealed.
     */
    private function ensureOwner(Request $request, Schedule $schedule): void
    {
        abort_unless($schedule->user_id === $request->user()->id, 404);
    }

    /**
     * Validate the request.
     *
     * Returns the schedule attributes and the per-day workloads ({ 'YYYY-MM-DD' => value }).
     * The per-day workloads are null when they should be left as they are.
     *
     * @return array{0: array<string, mixed>, 1: array<string, int>|null}
     */
    private function validated(Request $request, ?Schedule $schedule): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            // 自分のプロジェクトだけを指定できる
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')->where('user_id', $request->user()->id)],
            'color' => ['nullable', 'string', 'max:20'],
            'memo' => ['nullable', 'string'],
            'workload_mode' => ['nullable', Rule::in([Schedule::WORKLOAD_NONE, Schedule::WORKLOAD_FIXED, Schedule::WORKLOAD_DAILY])],
            'workload_value' => ['nullable', 'integer', 'min:0'],
            'workload_unit' => ['nullable', 'string', 'max:50'],
            'daily_workloads' => ['nullable', 'array', 'max:366'],
            'daily_workloads.*.date' => ['required', 'date_format:Y-m-d', 'distinct'],
            'daily_workloads.*.value' => ['required', 'integer', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'all_day' => ['boolean'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $data['end_date'] = $data['end_date'] ?? $data['start_date'];

        // workload_mode が無いリクエスト（以前の形式）は workload_value から判断する
        $mode = $data['workload_mode'] ?? match (true) {
            array_key_exists('workload_value', $data) => $data['workload_value'] === null ? Schedule::WORKLOAD_NONE : Schedule::WORKLOAD_FIXED,
            $schedule !== null => null, // 更新で作業量に触れていない場合はそのまま
            default => Schedule::WORKLOAD_NONE,
        };
        $entries = $data['daily_workloads'] ?? [];
        unset($data['daily_workloads']);

        if ($mode === null) {
            unset($data['workload_mode'], $data['workload_value'], $data['workload_unit']);

            return [$data, null];
        }

        $data['workload_mode'] = $mode;
        $dailyWorkloads = [];

        if ($mode === Schedule::WORKLOAD_NONE) {
            $data['workload_value'] = null;
            $data['workload_unit'] = null;
        } elseif ($mode === Schedule::WORKLOAD_FIXED) {
            if (($data['workload_value'] ?? null) === null) {
                throw ValidationException::withMessages(['workload_value' => '作業量を入力してください。']);
            }
        } else {
            $data['workload_value'] = null;
            $start = substr($data['start_date'], 0, 10);
            $end = substr($data['end_date'], 0, 10);

            foreach ($entries as $entry) {
                if ($entry['date'] < $start || $entry['date'] > $end) {
                    throw ValidationException::withMessages(['daily_workloads' => '作業量を入れた日が、予定の期間から外れています。']);
                }
                $dailyWorkloads[$entry['date']] = (int) $entry['value'];
            }

            if ($dailyWorkloads === []) {
                throw ValidationException::withMessages(['daily_workloads' => '作業量を1日以上入力してください。']);
            }
        }

        return [$data, $dailyWorkloads];
    }

    /**
     * Replace the schedule's per-day workloads. When $dailyWorkloads is null, only drops days outside the period.
     *
     * @param  array<string, int>|null  $dailyWorkloads
     */
    private function syncDailyWorkloads(Schedule $schedule, ?array $dailyWorkloads): void
    {
        if ($dailyWorkloads === null) {
            // 作業量はそのままでも、期間が短くなった場合は期間外の日の分を消す
            $schedule->workloads()
                ->where(fn ($query) => $query
                    ->whereDate('date', '<', $schedule->start_date)
                    ->orWhereDate('date', '>', $schedule->end_date))
                ->delete();

            return;
        }

        $schedule->workloads()->delete();

        foreach ($dailyWorkloads as $date => $value) {
            $schedule->workloads()->create(['date' => $date, 'value' => $value]);
        }
    }
}
