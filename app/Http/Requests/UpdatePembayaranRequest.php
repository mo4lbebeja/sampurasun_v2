<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePembayaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('keuangan') || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'no_spm'        => ['nullable', 'string', 'max:50'],
            'no_sp2d'       => ['nullable', 'string', 'max:50'],
            'tanggal_bayar' => ['nullable', 'date'],
            'metode_bayar'  => ['required', Rule::in(['transfer', 'cek', 'tunai', 'giro'])],
            'nilai_bayar'   => ['required', 'numeric', 'min:0'],
            'pajak_pph'     => ['nullable', 'numeric', 'min:0'],
            'pajak_ppn'     => ['nullable', 'numeric', 'min:0'],
            'catatan'       => ['nullable', 'string', 'max:1000'],
            'bukti_bayar'   => ['nullable', 'file', 'mimes:pdf,jpg,png', 'max:5120'],
            'file_spp' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'in'       => 'Pilihan :attribute tidak valid.',
            'mimes'    => ':attribute harus berupa file: :values.',
            'max'      => 'Ukuran :attribute maksimal 5MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'no_spm'        => 'nomor SPM',
            'no_sp2d'       => 'nomor SP2D',
            'tanggal_bayar' => 'tanggal bayar',
            'metode_bayar'  => 'metode bayar',
            'nilai_bayar'   => 'nilai bayar',
            'pajak_pph'     => 'PPh',
            'pajak_ppn'     => 'PPN',
            'bukti_bayar'   => 'bukti bayar',
        ];
    }
}