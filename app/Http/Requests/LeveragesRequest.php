<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeveragesRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'value' => 'required|unique:setting_leverages,value',
            'status' => 'required|in:Active,Inactive',
        ];

        // If it's an update request, exclude the current record from the unique check for the value
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['value'] .= ',' . $this->route('id');
        }

        return $rules;
    }


    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'value.unique' => 'The value already exists.',
        ];
    }
}
