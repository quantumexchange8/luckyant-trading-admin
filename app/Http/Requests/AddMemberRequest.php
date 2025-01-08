<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class AddMemberRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'regex:/^[a-zA-Z0-9\p{Han}. ]+$/u', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:' . User::class],
            'username' => ['required'],
            'dob' => ['required'],
            'dial_code' => ['required'],
            'phone' => ['required'],
            'gender' => ['required'],
            'address' => ['required'],
            'country' => ['required'],
            'nationality' => ['required'],
            'identification_number' => ['required'],
            'upline_id' => ['nullable'],
            'rank' => ['required'],
            'password' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'name' => trans('public.name'),
            'email' => trans('public.email'),
            'username' => trans('public.username'),
            'dob' => trans('public.dob'),
            'dial_code' => trans('public.phone_number'),
            'phone' => trans('public.phone_number'),
            'gender' => trans('public.gender'),
            'address' => trans('public.address'),
            'country' => trans('public.country'),
            'nationality' => trans('public.nationality'),
            'identification_number' => trans('public.identification_number'),
            'upline_id' => trans('public.upline_id'),
            'rank' => trans('public.rank'),
            'password' => trans('public.password'),
        ];
    }
}
