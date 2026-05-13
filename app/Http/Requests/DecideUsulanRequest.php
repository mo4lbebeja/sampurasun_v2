<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideUsulanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('pptk') || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'keputusan' => ['required', Rule::in(['disetujui', 'ditolak', 'revisi'])],
            'catatan'   => ['nullable', 'string', 'max:1000', 'required_if:keputusan,ditolak,revisi'],
        ];
    }

    public function messages(): array
    {
        return [
            'keputusan.required' => 'Keputusan wajib dipilih.',
            'keputusan.in'       => 'Keputusan tidak valid.',
            'catatan.required_if' => 'Catatan wajib diisi saat menolak atau meminta revisi.',
            'catatan.max'        => 'Catatan maksimal 1000 karakter.',
        ];
    }
}