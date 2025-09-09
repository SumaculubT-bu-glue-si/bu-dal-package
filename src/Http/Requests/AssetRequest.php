<?php

namespace Bu\Server\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssetRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'asset_id' => 'required|string|unique:assets,asset_id,' . ($this->asset ? $this->asset->id : 'NULL') . ',id',
            'type' => 'required|string|in:pc,monitor,smartphones,others',
            'hostname' => 'nullable|string',
            'manufacturer' => 'required|string',
            'model' => 'required|string',
            'serial_number' => 'required|string',
            'form_factor' => 'nullable|string',
            'processor' => 'nullable|string',
            'ram' => 'nullable|string',
            'storage' => 'nullable|string',
            'operating_system' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'warranty_expiry' => 'nullable|date',
            'location_id' => 'required|exists:locations,id',
            'employee_id' => 'nullable|exists:employees,id',
            'status' => 'required|string|in:active,inactive,maintenance,disposed'
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'asset_id.unique' => 'This asset ID is already in use.',
            'type.in' => 'The asset type must be one of: pc, monitor, smartphones, others',
            'status.in' => 'The status must be one of: active, inactive, maintenance, disposed'
        ];
    }
}
