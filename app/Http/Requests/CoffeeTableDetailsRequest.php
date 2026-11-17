<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

abstract class CoffeeTableDetailsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => Str::upper(trim($this->input('code')))]);
        }
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Kode meja sudah digunakan pada denah ini, termasuk meja yang diarsipkan. Gunakan kode lain atau ubah meja yang sudah ada.',
            'code.regex' => 'Kode meja hanya boleh berisi huruf, angka, tanda hubung (-), dan garis bawah (_).',
            'code.max' => 'Kode meja maksimal :max karakter.',
            'name.max' => 'Nama meja maksimal :max karakter.',
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'numeric' => ':attribute harus berupa angka.',
            'boolean' => ':attribute harus berupa pilihan aktif atau nonaktif.',
            'array' => ':attribute harus berupa daftar.',
            'list' => ':attribute harus berupa daftar berurutan.',
            'exists' => ':attribute tidak ditemukan. Muat ulang denah dan coba lagi.',
            'in' => 'Pilihan :attribute tidak valid.',
            'between' => ':attribute harus di antara :min dan :max.',
            'min' => ':attribute minimal :min.',
            'max' => ':attribute maksimal :max.',
            'size' => 'Jumlah posisi kursi harus sesuai kapasitas meja (:size kursi).',
            'gt' => ':attribute harus lebih besar dari :value.',
        ];
    }

    public function attributes(): array
    {
        return ['floor_layout_id' => 'Denah', 'code' => 'Kode meja', 'name' => 'Nama meja',
            'capacity' => 'Kapasitas meja', 'shape' => 'Bentuk meja', 'position_x' => 'Posisi X', 'position_y' => 'Posisi Y',
            'width' => 'Lebar meja', 'height' => 'Tinggi meja', 'rotation' => 'Rotasi meja',
            'is_active' => 'Status tampilan', 'version' => 'Versi meja', 'chair_positions' => 'Posisi kursi', 'chair_positions.*' => 'Posisi kursi'];
    }
}
