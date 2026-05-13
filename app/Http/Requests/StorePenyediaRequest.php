<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePenyediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin & pejabat pengadaan boleh kelola master vendor
        return $this->user()->isAdmin()
            || $this->user()->hasRole('pejabat_pengadaan');
    }

    public function rules(): array
    {
        $penyediaId = $this->route('penyedia')?->id;

        return [
            'nama'                => ['required', 'string', 'max:200'],
            'jenis_badan'         => ['required', Rule::in(['PT', 'CV', 'UD', 'Firma', 'Koperasi', 'Perorangan'])],
            'npwp'                => ['nullable', 'string', 'max:30'],
            'alamat'              => ['nullable', 'string', 'max:500'],
            'telepon'             => ['nullable', 'string', 'max:30'],
            'email'               => ['nullable', 'email', 'max:150'],
            'nama_pic'            => ['nullable', 'string', 'max:150'],
            'rekening_bank'       => ['nullable', 'string', 'max:50'],
            'nama_bank'           => ['nullable', 'string', 'max:100'],
            'atas_nama_rekening'  => ['nullable', 'string', 'max:150'],
            'is_active'           => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'email'    => 'Format email tidak valid.',
            'in'       => 'Pilihan :attribute tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nama'               => 'nama penyedia',
            'jenis_badan'        => 'jenis badan',
            'nama_pic'           => 'nama PIC',
            'atas_nama_rekening' => 'nama pemilik rekening',
        ];
    }
}