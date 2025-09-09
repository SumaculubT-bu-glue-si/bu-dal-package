<?php

namespace Bu\Server\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuditAssignmentRequest extends FormRequest
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
            'audit_plan_id' => 'required|exists:audit_plans,id',
            'auditor_id' => 'required|exists:employees,id',
            'location_id' => 'required|exists:locations,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:pending,in_progress,completed',
            'notes' => 'nullable|string|max:1000',
            'checklist' => 'nullable|array',
            'checklist.*.item' => 'required|string',
            'checklist.*.completed' => 'boolean'
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
            'audit_plan_id' => 'audit plan',
            'auditor_id' => 'auditor',
            'location_id' => 'location'
        ];
    }
}
