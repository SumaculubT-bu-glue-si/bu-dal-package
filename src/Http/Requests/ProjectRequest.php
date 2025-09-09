<?php

namespace Bu\Server\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:projects,code,' . ($this->project ? $this->project->id : 'NULL') . ',id',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|string|in:planning,active,on_hold,completed,cancelled',
            'priority' => 'required|string|in:low,medium,high,critical',
            'manager_id' => 'required|exists:employees,id',
            'location_ids' => 'required|array',
            'location_ids.*' => 'exists:locations,id',
            'budget' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string'
        ];
    }

    public function messages()
    {
        return [
            'code.unique' => 'This project code is already in use.',
            'end_date.after' => 'End date must be after start date',
            'status.in' => 'The status must be one of: planning, active, on_hold, completed, cancelled',
            'priority.in' => 'The priority must be one of: low, medium, high, critical'
        ];
    }
}
