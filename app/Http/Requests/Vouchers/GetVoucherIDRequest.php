<?php

namespace App\Http\Requests\Vouchers;

use Illuminate\Foundation\Http\FormRequest;

class GetVoucherIDRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required']
        ];
    }
}
