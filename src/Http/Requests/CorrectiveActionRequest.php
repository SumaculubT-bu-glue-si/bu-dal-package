<?php

namespace Bu\Server\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CorrectiveActionRequest extends FormRequest
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
            'audit_id' => 'required|exists:audits,id',
            'finding_id' => 'required|exists:audit_findings,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'root_cause' => 'required|string',
            'proposed_solution' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:open,in_progress,completed,verified',
            'due_date' => 'required|date|after:today',
            'assigned_to' => 'required|exists:employees,id',
            'reviewers' => 'nullable|array',
            'reviewers.*' => 'exists:employees,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
            'notification_settings' => 'nullable|array',
            'notification_settings.reminder_days' => 'nullable|integer|min:1',
            'notification_settings.escalation_after' => 'nullable|integer|min:1',
            'notification_settings.cc_emails' => 'nullable|array',
            'notification_settings.cc_emails.*' => 'email',
            'cost_impact' => 'nullable|numeric|min:0',
            'timeline_impact' => 'nullable|integer|min:0',
            'implementation_plan' => 'nullable|string',
            'verification_criteria' => 'nullable|string',
            'related_documents' => 'nullable|array',
            'related_documents.*' => 'string'
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
            'audit_id' => 'audit',
            'finding_id' => 'audit finding',
            'assigned_to' => 'assigned employee',
            'reviewers.*' => 'reviewer'
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
            'due_date.after' => 'The due date must be after today.',
            'attachments.*.mimes' => 'The attachment must be a file of type: pdf, doc, docx, xls, xlsx, jpg, jpeg, png.',
            'attachments.*.max' => 'The attachment may not be greater than 10MB.'
        ];
    }
}
