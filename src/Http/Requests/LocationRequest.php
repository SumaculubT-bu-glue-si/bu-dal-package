<?php

namespace Bu\Server\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:locations,code,' . ($this->location ? $this->location->id : 'NULL') . ',id',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'country' => 'required|string',
            'postal_code' => 'required|string',
            'status' => 'required|string|in:active,inactive',
            'parent_id' => 'nullable|exists:locations,id'
        ];
    }
}
