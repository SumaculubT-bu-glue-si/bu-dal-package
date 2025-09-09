<?php

namespace Bu\Server\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employee_id' => 'required|string|unique:employees,employee_id,' . ($this->employee ? $this->employee->id : 'NULL') . ',id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . ($this->employee ? $this->employee->id : 'NULL') . ',id',
            'position' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'location_id' => 'required|exists:locations,id',
            'supervisor_id' => 'nullable|exists:employees,id',
            'status' => 'required|string|in:active,inactive,on_leave',
            'hire_date' => 'required|date',
            'termination_date' => 'nullable|date|after:hire_date',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20'
        ];
    }

    public function messages()
    {
        return [
            'employee_id.unique' => 'This employee ID is already in use.',
            'email.unique' => 'This email address is already in use.',
            'status.in' => 'The status must be one of: active, inactive, on_leave',
            'termination_date.after' => 'Termination date must be after hire date'
        ];
    }
}
