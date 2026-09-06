<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_reference' => ['required', 'string'],
            'customer_name' => ['required', 'string'],
            'customer_email' => ['required', 'email'],
        ];
    }
}
