<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('perencanaan') || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'tanggal_evaluasi'         => ['required', 'date'],
            'nilai_kinerja_penyedia'   => ['required', 'integer', 'min:1', 'max:5'],
            'ketepatan_waktu'          => ['required', 'integer', 'min:1', 'max:5'],
            'kesesuaian_spesifikasi'   => ['required', 'integer', 'min:1', 'max:5'],
            'kualitas_barang'          => ['required', 'integer', 'min:1', 'max:5'],
            'rekomendasi'              => ['required', Rule::in([
                'sangat_baik', 'baik', 'cukup', 'kurang', 'tidak_direkomendasikan',
            ])],
            'catatan_evaluasi'         => ['nullable', 'string', 'max:2000'],
            'rekomendasi_perbaikan'    => ['nullable', 'string', 'max:2000'],
            'file_laporan'             => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'integer'  => ':attribute harus berupa angka.',
            'min'      => ':attribute minimal :min.',
            'max'      => ':attribute maksimal :max.',
            'in'       => 'Pilihan :attribute tidak valid.',
            'mimes'    => ':attribute harus berupa file: :values.',
        ];
    }

    public function attributes(): array
    {
        return [
            'tanggal_evaluasi'        => 'tanggal evaluasi',
            'nilai_kinerja_penyedia'  => 'nilai kinerja penyedia',
            'ketepatan_waktu'         => 'ketepatan waktu',
            'kesesuaian_spesifikasi'  => 'kesesuaian spesifikasi',
            'kualitas_barang'         => 'kualitas barang',
            'rekomendasi'             => 'rekomendasi',
            'file_laporan'            => 'file laporan',
        ];
    }
}