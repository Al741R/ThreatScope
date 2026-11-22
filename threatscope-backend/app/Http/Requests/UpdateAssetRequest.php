<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('vendor')) {
            $data['vendor'] = strtolower(trim((string) $this->vendor));
        }
        if ($this->has('product')) {
            $data['product'] = strtolower(trim((string) $this->product));
        }
        if ($this->has('version')) {
            $data['version'] = trim((string) $this->version);
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        return [
            'vendor' => ['sometimes', 'required', 'string', 'max:100'],
            'product' => ['sometimes', 'required', 'string', 'max:100'],
            'version' => ['sometimes', 'required', 'string', 'max:50'],
            'status' => ['sometimes', 'required', 'in:Active,Retired'],
        ];
    }
}
