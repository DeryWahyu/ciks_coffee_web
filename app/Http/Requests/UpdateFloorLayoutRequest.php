<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'canvas_width' => ['sometimes', 'integer', 'between:400,8192'],
            'canvas_height' => ['sometimes', 'integer', 'between:400,8192'],
            'background_config' => ['sometimes', 'nullable', 'array'],
            'background_config.show_grid' => ['sometimes', 'boolean'],
            'background_config.table_seating' => ['sometimes', 'array', 'max:100'],
            'background_config.table_seating.*' => ['string', Rule::in(['standard', 'round_pair', 'bench', 'sofa_pair'])],
            'background_config.elements' => ['sometimes', 'array', 'max:100'],
            'background_config.elements.*' => ['required', 'array:type,label,position_x,position_y,width,height,rotation,fill,font_size'],
            'background_config.elements.*.type' => ['required', 'string', Rule::in(['counter', 'window', 'entrance', 'rectangle', 'ellipse', 'line', 'wall', 'text', 'area', 'parking', 'toilet', 'plant', 'stairs'])],
            'background_config.elements.*.label' => ['present', 'nullable', 'string', 'max:80'],
            'background_config.elements.*.position_x' => ['required', 'numeric', 'between:0,100'],
            'background_config.elements.*.position_y' => ['required', 'numeric', 'between:0,100'],
            'background_config.elements.*.width' => ['sometimes', 'numeric', 'between:0.5,100'],
            'background_config.elements.*.height' => ['sometimes', 'numeric', 'between:0.5,100'],
            'background_config.elements.*.rotation' => ['sometimes', 'numeric', 'between:0,359.99'],
            'background_config.elements.*.fill' => ['sometimes', Rule::in(['none', 'latte', 'cream', 'espresso', 'stone', 'sand', 'sage'])],
            'background_config.elements.*.font_size' => ['sometimes', 'integer', 'between:8,32'],
            'tables' => ['present', 'array', 'max:100'],
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
            foreach ((array) $this->input('tables', []) as $index => $table) {
                if (! is_array($table)) {
                    continue;
                }

                if ((float) ($table['position_x'] ?? 0) + (float) ($table['width'] ?? 0) > 100) {
                    $validator->errors()->add('tables.'.$index.'.width', 'Posisi dan lebar meja tidak boleh melewati batas kanan denah.');
                }

                if ((float) ($table['position_y'] ?? 0) + (float) ($table['height'] ?? 0) > 100) {
                    $validator->errors()->add('tables.'.$index.'.height', 'Posisi dan tinggi meja tidak boleh melewati batas bawah denah.');
                }
            }
        });
    }
}
