<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKategoriBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $kategoriId = $this->route('kategori')?->id;

        return [
            'kode'      => [
                'required',
                'string',
                'max:20',
                Rule::unique('kategori_barang', 'kode')->ignore($kategoriId),
            ],
            'nama'      => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'unique'   => 'Kode :input sudah digunakan.',
            'max'      => ':attribute maksimal :max karakter.',
        ];
    }
}