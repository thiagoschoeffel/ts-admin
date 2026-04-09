<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document' => $this->sanitiseDigits($this->input('document')),
            'contact_phone_primary' => $this->sanitiseDigits($this->input('contact_phone_primary')),
            'contact_phone_secondary' => $this->sanitiseDigits($this->input('contact_phone_secondary')),
        ]);
    }

    public function rules(): array
    {
        $clientId = $this->route('client')->id;
        $isCompany = $this->input('person_type') === 'company';

        return [
            'name' => ['required', 'string', 'max:255'],
            'person_type' => ['required', Rule::in(['individual', 'company'])],
            'document' => [
                'required',
                'string',
                $isCompany ? 'digits:14' : 'digits:11',
                Rule::unique('clients', 'document')->ignore($clientId),
            ],
            'observations' => ['nullable', 'string'],
            'contact_name' => [$isCompany ? 'required' : 'nullable', 'string', 'max:255'],
            'contact_phone_primary' => [$isCompany ? 'required' : 'nullable', 'digits_between:10,11'],
            'contact_phone_secondary' => [$isCompany ? 'required' : 'nullable', 'digits_between:10,11'],
            'contact_email' => [$isCompany ? 'required' : 'nullable', 'email', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function sanitiseDigits(?string $value): ?string
    {
        return $value ? preg_replace('/\D+/', '', $value) : null;
    }
}
