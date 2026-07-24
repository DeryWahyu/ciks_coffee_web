<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateFloorLayoutRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'canvas_width' => ['sometimes', 'integer', 'between:400,2400'],
            'canvas_height' => ['sometimes', 'integer', 'between:400,2400'],
            'background_config' => ['sometimes', 'nullable', 'array'],
            'tables' => ['required', 'array', 'min:1', 'max:100'],
            'tables.*.id' => ['required', 'integer', 'distinct', 'exists:coffee_tables,id'],
            'tables.*.position_x' => ['required', 'numeric', 'between:0,100'],
            'tables.*.position_y' => ['required', 'numeric', 'between:0,100'],
            'tables.*.width' => ['required', 'numeric', 'gt:0', 'max:100'],
            'tables.*.height' => ['required', 'numeric', 'gt:0', 'max:100'],
            'tables.*.rotation' => ['nullable', 'numeric', 'between:0,359.99'],
            'tables.*.version' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('tables', []) as $index => $table) {
                if (! is_array($table)) {
                    continue;
                }

                if ((float) ($table['position_x'] ?? 0) + (float) ($table['width'] ?? 0) > 100) {
                    $validator->errors()->add('tables.' . $index . '.width', 'Posisi dan lebar meja tidak boleh melewati batas kanan denah.');
                }

                if ((float) ($table['position_y'] ?? 0) + (float) ($table['height'] ?? 0) > 100) {
                    $validator->errors()->add('tables.' . $index . '.height', 'Posisi dan tinggi meja tidak boleh melewati batas bawah denah.');
                }
            }
        });
    }
}
