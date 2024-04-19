<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required'],
            'role' => ['required', 'exists:roles,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'user_id' => trans('public.user'),
            'role' => trans('public.role'),
        ];
    }
}
