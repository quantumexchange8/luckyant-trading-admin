<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class EditMemberRequest extends FormRequest
{
    public function rules(): array
    {
        $user_id = $this->request->get('user_id');

        return [
            'name' => ['required', 'regex:/^[a-zA-Z0-9\p{Han}. ]+$/u', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'max:255', Rule::unique(User::class)->ignore($user_id)],
            'email' => ['required', 'email', Rule::unique(User::class)->ignore($user_id)],
            'password' => ['nullable', Password::defaults()],
            'dob' => ['required'],
            'country' => ['required'],
            'gender' => ['required'],
            'nationality' => ['required'],
            'identification_number' => ['required'],
            'address' => ['required'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }


    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'username' => trans('public.username'),
            'phone' => 'Phone Number',
            'password' => 'Password',
            'email' => 'Email',
            'dob' => 'Date of Birth',
            'country' => 'Country',
            'gender' => trans('public.gender'),
            'nationality' => trans('public.nationality'),
            'identification_number' => trans('public.identification_number'),
            'address' => trans('public.address'),
        ];
    }
}
