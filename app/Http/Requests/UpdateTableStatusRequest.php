<?php

namespace App\Http\Requests;

use App\Models\CoffeeTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTableStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(CoffeeTable::statuses())],
            'note' => ['nullable', 'string', 'max:500'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status meja wajib dipilih.',
            'status.in' => 'Status meja tidak valid.',
            'version.required' => 'Versi data meja wajib dikirim.',
        ];
    }
}
