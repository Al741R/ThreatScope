<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'vendor' => strtolower(trim((string) $this->vendor)),
            'product' => strtolower(trim((string) $this->product)),
            'version' => trim((string) $this->version),
        ]);
    }

    public function rules(): array
    {
        return [
            'vendor' => ['required', 'string', 'max:100'],
            'product' => ['required', 'string', 'max:100'],
            'version' => ['required', 'string', 'max:50'],
            'status' => ['sometimes', 'in:Active,Retired'],
        ];
    }
}
