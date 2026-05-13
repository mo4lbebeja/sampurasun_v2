<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePengadaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('pejabat_pengadaan') || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'metode' => ['required', Rule::in([
                'pengadaan_langsung',
                'penunjukan_langsung',
                'tender',
                'tender_cepat',
                'e_purchasing',
                'swakelola',
            ])],
            'tanggal_mulai' => ['required', 'date'],
            'catatan'       => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'in'       => 'Pilihan :attribute tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'metode'        => 'metode pengadaan',
            'tanggal_mulai' => 'tanggal mulai',
        ];
    }
}