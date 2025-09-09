<?php

namespace Bu\Server\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuditAssetRequest extends FormRequest
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
        $rules = [
            'audit_plan_id' => 'required|exists:audit_plans,id',
            'asset_id' => 'required|exists:assets,id',
            'original_location' => 'required|string',
            'original_user' => 'nullable|string',
            'current_status' => 'nullable|string',
            'current_location' => 'nullable|string',
            'current_user' => 'nullable|string',
            'auditor_notes' => 'nullable|string|max:1000',
            'audit_status' => 'boolean',
            'resolved' => 'boolean'
        ];

        // Add additional validation for status updates
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['current_status'] = 'required|string|in:欠落,返却済,廃止,保管(使用無),利用中,保管中,貸出中,故障中,利用予約,Missing,Returned,Abolished,Stored - Not in Use,In Use,In Storage,On Loan,Broken,Reserved for Use';
        }

        return $rules;
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
            'asset_id' => 'asset',
            'current_status' => 'status',
            'auditor_notes' => 'notes'
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
            'current_status.in' => 'The selected status is invalid. Please choose a valid status from the list.'
        ];
    }
}
