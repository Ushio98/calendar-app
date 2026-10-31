<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * Display a listing of the projects with their schedules.
     */
    public function index(Request $request)
    {
        return $request->user()->projects()->with(['schedules.workloads', 'schedules.actuals'])->orderBy('name')->get();
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request)
    {
        $project = $request->user()->projects()->create($this->validated($request));

        return response()->json($project->load(['schedules.workloads', 'schedules.actuals']), 201);
    }

    /**
     * Display the specified project.
     */
    public function show(Request $request, Project $project)
    {
        $this->ensureOwner($request, $project);

        return $project->load(['schedules.workloads', 'schedules.actuals']);
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project)
    {
        $this->ensureOwner($request, $project);

        $project->update($this->validated($request, $project));

        return $project->load(['schedules.workloads', 'schedules.actuals']);
    }

    /**
     * Remove the specified project. Its schedules remain, without a project.
     */
    public function destroy(Request $request, Project $project)
    {
        $this->ensureOwner($request, $project);

        $project->delete();

        return response()->noContent();
    }

    /**
     * Respond with 404 when the project belongs to another user, so its existence is not revealed.
     */
    private function ensureOwner(Request $request, Project $project): void
    {
        abort_unless($project->user_id === $request->user()->id, 404);
    }

    private function validated(Request $request, ?Project $project = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('projects')->where('user_id', $request->user()->id)->ignore($project),
            ],
            // 日付と種類は、両方設定するか両方空にする
            'milestone_type' => ['nullable', 'required_with:milestone_date', Rule::in(Project::MILESTONE_TYPES)],
            'milestone_date' => ['nullable', 'required_with:milestone_type', 'date_format:Y-m-d'],
        ], [], [
            'name' => 'プロジェクト名',
            'milestone_type' => '日付の種類',
            'milestone_date' => '日付',
        ]);
    }
}
