<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDokumenUpbjRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('upbj') || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'no_bast'           => ['nullable', 'string', 'max:100'],
            'tanggal_bast'      => ['nullable', 'date'],
            'keterangan'        => ['nullable', 'string', 'max:1000'],

            // File uploads — semua opsional saat save draft
            'file_bast'         => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,png', 'max:5120'],
            'file_invoice'      => ['nullable', 'file', 'mimes:pdf,jpg,png', 'max:5120'],
            'file_faktur_pajak' => ['nullable', 'file', 'mimes:pdf,jpg,png', 'max:5120'],
            'file_kuitansi'     => ['nullable', 'file', 'mimes:pdf,jpg,png', 'max:5120'],
            'file_spp'          => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'mimes' => ':attribute harus berupa file: :values.',
            'max'   => 'Ukuran :attribute maksimal 5MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'no_bast'           => 'nomor BAST',
            'tanggal_bast'      => 'tanggal BAST',
            'file_bast'         => 'file BAST',
            'file_invoice'      => 'file invoice',
            'file_faktur_pajak' => 'file faktur pajak',
            'file_kuitansi'     => 'file kuitansi',
            'file_spp'          => 'file SPP',
        ];
    }
}