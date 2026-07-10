<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by the auth:sanctum middleware on the route
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                // Unique per user, per due_date (not globally) — see unique_title_per_due_date_per_user index.
                Rule::unique('tasks', 'title')
                    ->where('user_id', $this->user()->id)
                    ->where('due_date', $this->input('due_date')),
            ],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
        ];
    }

    public function messages(): array
    {
        return [
            'title.unique' => 'You already have a task with this title due on this date.',
            'due_date.after_or_equal' => 'Due date must be today or later.',
        ];
    }
}
