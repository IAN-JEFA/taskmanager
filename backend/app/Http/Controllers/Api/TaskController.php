<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * GET /api/tasks
     * Sorted by priority (high -> low), then due_date ascending.
     * Optional ?status= filter.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['sometimes', Rule::in(['pending', 'in_progress', 'done'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Task::query()
            ->where('user_id', $request->user()->id)
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->orderBy('due_date', 'asc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $tasks = $query->get();

        if ($tasks->isEmpty()) {
            return response()->json([
                'message' => 'No tasks found.',
                'data' => [],
            ]);
        }

        return response()->json([
            'message' => 'Tasks retrieved successfully.',
            'data' => $tasks,
        ]);
    }

    /**
     * POST /api/tasks
     */
    public function store(StoreTaskRequest $request)
    {
        $task = Task::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'due_date' => $request->due_date,
            'priority' => $request->priority,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Task created successfully.',
            'data' => $task,
        ], 201);
    }

    /**
     * PATCH /api/tasks/{id}/status
     * Status can only progress: pending -> in_progress -> done. No skipping or reverting.
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task)
    {
        $this->authorizeOwnership($task, $request);

        $requestedStatus = $request->input('status');

        if (! $task->canTransitionTo($requestedStatus)) {
            return response()->json([
                'message' => "Cannot change status from '{$task->status}' to '{$requestedStatus}'. Status can only move forward: pending -> in_progress -> done.",
            ], 422);
        }

        $task->update(['status' => $requestedStatus]);

        return response()->json([
            'message' => 'Task status updated successfully.',
            'data' => $task,
        ]);
    }

    /**
     * DELETE /api/tasks/{id}
     * Only 'done' tasks can be deleted.
     */
    public function destroy(Request $request, Task $task)
    {
        $this->authorizeOwnership($task, $request);

        if ($task->status !== 'done') {
            return response()->json([
                'message' => 'Only tasks with status "done" can be deleted.',
            ], 403);
        }

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }

    /**
     * GET /api/tasks/report?date=YYYY-MM-DD
     * Counts per priority and status for the given day.
     */
    public function report(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $date = $request->input('date');

        $rows = Task::query()
            ->select('priority', 'status', DB::raw('count(*) as total'))
            ->where('user_id', $request->user()->id)
            ->whereDate('due_date', $date)
            ->groupBy('priority', 'status')
            ->get();

        $priorities = ['high', 'medium', 'low'];
        $statuses = ['pending', 'in_progress', 'done'];

        $summary = [];
        foreach ($priorities as $priority) {
            foreach ($statuses as $status) {
                $summary[$priority][$status] = 0;
            }
        }

        foreach ($rows as $row) {
            $summary[$row->priority][$row->status] = (int) $row->total;
        }

        return response()->json([
            'date' => $date,
            'summary' => $summary,
        ]);
    }

    private function authorizeOwnership(Task $task, Request $request): void
    {
        abort_if($task->user_id !== $request->user()->id, 404, 'Task not found.');
    }
}
