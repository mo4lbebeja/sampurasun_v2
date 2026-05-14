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
            'metode'       => ['required', 'in:pengadaan_langsung,penunjukan_langsung,tender,tender_cepat,e_purchasing,swakelola'],
            'tanggal_mulai' => ['required', 'date'],
            'catatan'      => ['nullable', 'string', 'max:1000'],
 
            // ── Field baru untuk multi-paket ──────────────────────────
            'nama_paket'   => ['nullable', 'string', 'max:200'],
 
            // item_ids: opsional (kosong = tidak pakai item assignment)
            // Jika diisi, setiap ID harus ada di tabel usulan_items
            'item_ids'     => ['nullable', 'array'],
            'item_ids.*'   => ['integer', 'exists:usulan_items,id'],
        ];
    }
 
    public function messages(): array
    {
        return [
            'metode.required'      => 'Metode pengadaan harus dipilih.',
            'metode.in'            => 'Metode pengadaan tidak valid.',
            'tanggal_mulai.required' => 'Tanggal mulai harus diisi.',
            'tanggal_mulai.date'   => 'Format tanggal tidak valid.',
            'item_ids.*.exists'    => 'Satu atau lebih item tidak ditemukan.',
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