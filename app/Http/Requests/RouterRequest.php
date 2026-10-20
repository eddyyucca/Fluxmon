<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RouterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $router = $this->route('router');

        return [
            'name' => ['required', 'string', 'max:60', Rule::unique('routers', 'name')->ignore($router)],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'use_ssl' => ['boolean'],
            'username' => ['required', 'string', 'max:100'],
            // blank on edit = keep the stored password
            'password' => [$router ? 'nullable' : 'required', 'string', 'max:255'],
            'hotspot_name' => ['nullable', 'string', 'max:100'],
            'dns_name' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:8'],
            'traffic_interface' => ['required', 'string', 'max:60'],
            'timeout' => ['required', 'integer', 'between:1,60'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['use_ssl' => $this->boolean('use_ssl')]);
    }
}
