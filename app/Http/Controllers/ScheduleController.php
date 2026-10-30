<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the schedules.
     */
    public function index()
    {
        return Schedule::orderBy('start_date')->get();
    }

    /**
     * Display the schedules that include today.
     */
    public function today()
    {
        $today = now()->toDateString();

        return Schedule::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->withExists(['checks as is_checked' => fn ($query) => $query->whereDate('date', $today)])
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Mark the schedule as done (or not done) for today.
     */
    public function check(Request $request, Schedule $schedule)
    {
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
        $schedule = Schedule::create($this->validated($request));

        return response()->json($schedule, 201);
    }

    /**
     * Display the specified schedule.
     */
    public function show(Schedule $schedule)
    {
        return $schedule;
    }

    /**
     * Update the specified schedule.
     */
    public function update(Request $request, Schedule $schedule)
    {
        $schedule->update($this->validated($request));

        return $schedule;
    }

    /**
     * Remove the specified schedule.
     */
    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        return response()->noContent();
    }

    /**
     * Validate the request and fill in the end date when it is omitted.
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'memo' => ['nullable', 'string'],
            'workload_value' => ['nullable', 'integer', 'min:0'],
            'workload_unit' => ['nullable', 'string', 'max:50'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'all_day' => ['boolean'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $data['end_date'] = $data['end_date'] ?? $data['start_date'];

        return $data;
    }
}
