<?php

namespace App\Http\Requests;

use App\Models\CoffeeTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCoffeeTableRequest extends FormRequest
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
        /** @var CoffeeTable|null $coffeeTable */
        $coffeeTable = $this->route('coffeeTable');
        $layoutId = $coffeeTable?->floor_layout_id;

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('coffee_tables', 'code')
                    ->where(fn ($query) => $query->where('floor_layout_id', $layoutId))
                    ->ignore($coffeeTable?->getKey()),
            ],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'between:1,20'],
            'shape' => ['required', 'string', Rule::in(CoffeeTable::shapes())],
            'position_x' => ['required', 'numeric', 'between:0,100'],
            'position_y' => ['required', 'numeric', 'between:0,100'],
            'width' => ['required', 'numeric', 'gt:0', 'max:100'],
            'height' => ['required', 'numeric', 'gt:0', 'max:100'],
            'rotation' => ['nullable', 'numeric', 'between:0,359.99'],
            'is_active' => ['required', 'boolean'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ((float) $this->input('position_x') + (float) $this->input('width') > 100) {
                $validator->errors()->add('width', 'Posisi dan lebar meja tidak boleh melewati batas kanan denah.');
            }

            if ((float) $this->input('position_y') + (float) $this->input('height') > 100) {
                $validator->errors()->add('height', 'Posisi dan tinggi meja tidak boleh melewati batas bawah denah.');
            }
        });
    }
}
