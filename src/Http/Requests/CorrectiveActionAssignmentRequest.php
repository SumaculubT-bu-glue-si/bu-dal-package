<?php

namespace Bu\Server\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectiveActionAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'corrective_action_id' => 'required|exists:corrective_actions,id',
            'employee_id' => 'required|exists:employees,id',
            'role' => 'required|string|in:owner,reviewer,implementer',
            'due_date' => 'required|date|after:today',
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|string|in:pending,in_progress,completed',
            'notifications_enabled' => 'boolean'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'corrective_action_id' => 'corrective action',
            'employee_id' => 'employee'
        ];
    }
}
