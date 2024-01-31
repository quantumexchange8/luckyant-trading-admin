<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KycApprovalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => 'in:approve,reject',
            'remark' => 'required_if:type,reject',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'remark' => 'Remark'
        ];
    }
}
