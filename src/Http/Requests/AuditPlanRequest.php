<?php

namespace Bu\Server\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuditPlanRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after:start_date',
            'status' => 'required|in:Planning,In Progress,Completed,Cancelled',
            'locations' => 'sometimes|required|array',
            'locations.*' => 'exists:locations,id',
            'auditors' => 'sometimes|required|array',
            'auditors.*' => 'exists:employees,id',
            'frequency' => 'nullable|in:once,weekly,monthly,quarterly,annually',
            'checklist_template' => 'nullable|array',
            'checklist_template.*.title' => 'required|string',
            'checklist_template.*.description' => 'nullable|string',
            'checklist_template.*.type' => 'required|in:text,checkbox,select',
            'checklist_template.*.required' => 'boolean',
            'checklist_template.*.options' => 'required_if:checklist_template.*.type,select|array',
            'notification_settings' => 'nullable|array',
            'notification_settings.reminder_days' => 'nullable|integer|min:1',
            'notification_settings.notify_on_completion' => 'boolean',
            'notification_settings.cc_emails' => 'nullable|array',
            'notification_settings.cc_emails.*' => 'email'
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
            'name' => 'audit plan name',
            'due_date' => 'due date',
            'locations.*' => 'location',
            'auditors.*' => 'auditor'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'due_date.after' => 'The due date must be after the start date.',
            'checklist_template.*.type.in' => 'The checklist item type must be text, checkbox, or select.',
            'notification_settings.reminder_days.min' => 'Reminder days must be at least 1.'
        ];
    }
}
