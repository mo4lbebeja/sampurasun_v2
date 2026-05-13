<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKontrakRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('pejabat_pengadaan') || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'penyedia_id'     => ['required', 'exists:penyedia,id'],
            'no_kontrak'      => ['required', 'string', 'max:100'],
            'tanggal_kontrak' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_kontrak'],
            'nilai_kontrak'   => ['required', 'numeric', 'min:0'],
            'catatan'         => ['nullable', 'string', 'max:1000'],

            'file_kontrak'    => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'], // 5MB
            'file_hps'        => ['nullable', 'file', 'mimes:pdf,xls,xlsx', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'required'        => 'Kolom :attribute wajib diisi.',
            'after_or_equal'  => 'Tanggal selesai tidak boleh kurang dari tanggal kontrak.',
            'mimes'           => ':attribute harus berupa file: :values.',
            'max'             => 'Ukuran :attribute maksimal 5MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'penyedia_id'     => 'penyedia',
            'no_kontrak'      => 'nomor kontrak',
            'tanggal_kontrak' => 'tanggal kontrak',
            'tanggal_selesai' => 'tanggal selesai',
            'nilai_kontrak'   => 'nilai kontrak',
            'file_kontrak'    => 'file kontrak',
            'file_hps'        => 'file HPS',
        ];
    }
}