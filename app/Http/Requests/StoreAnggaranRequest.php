<?php

namespace App\Http\Requests;

use App\Models\SubKegiatan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnggaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        if ($this->session()->has('tahun_anggaran')) {
            $this->merge([
                'tahun' => (int) $this->session()->get('tahun_anggaran'),
            ]);
        }

        if ($this->isMethod('post')) {
            $this->merge([
                'is_active' => true,
                'submit_action' => $this->input('submit_action', 'save_back'),
            ]);
        }
    }

    public function rules(): array
    {
        $anggaranId = $this->route('anggaran')?->id;

        return [
            'sub_kegiatan_id' => [
                'required',
                'integer',
                Rule::exists('sub_kegiatan', 'id')
                    ->where('is_active', true)
                    ->where('tahun_anggaran', (int) $this->input('tahun')),
            ],

            'tahun' => [
                'required',
                'integer',
                'min:2020',
                'max:2100',
            ],

            'kode_rekening' => [
                'required',
                'string',
                'max:50',
                Rule::unique('anggaran', 'kode_rekening')
                    ->where('tahun', (int) $this->input('tahun'))
                    ->where('sub_kegiatan_id', (int) $this->input('sub_kegiatan_id'))
                    ->ignore($anggaranId),
            ],

            'nama_rekening' => [
                'required',
                'string',
                'max:200',
            ],

            'uraian' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'pagu' => [
                'required',
                'numeric',
                'min:0',
            ],

            'terpakai' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'is_active' => [
                'boolean',
            ],

            'submit_action' => [
                'nullable',
                Rule::in(['save_add_more', 'save_back']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'unique' => 'Kombinasi sub kegiatan, tahun anggaran, dan kode rekening sudah ada.',
            'numeric' => ':attribute harus berupa angka.',
            'min' => ':attribute minimal :min.',
            'sub_kegiatan_id.exists' => 'Sub kegiatan tidak valid, tidak aktif, atau tidak sesuai tahun anggaran login.',
            'kode_rekening.unique' => 'Kode rekening ini sudah ada pada sub kegiatan dan tahun anggaran yang sama.',
            'submit_action.in' => 'Aksi simpan tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'sub_kegiatan_id' => 'sub kegiatan',
            'tahun' => 'tahun anggaran',
            'kode_rekening' => 'kode rekening',
            'nama_rekening' => 'nama rekening',
            'uraian' => 'uraian',
            'pagu' => 'pagu anggaran',
            'terpakai' => 'terpakai',
            'is_active' => 'status',
            'submit_action' => 'aksi simpan',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tahunAnggaran = (int) $this->input('tahun');
            $subKegiatanId = (int) $this->input('sub_kegiatan_id');

            $subKegiatan = SubKegiatan::query()
                ->whereKey($subKegiatanId)
                ->where('is_active', true)
                ->where('tahun_anggaran', $tahunAnggaran)
                ->first();

            if (! $subKegiatan) {
                $validator->errors()->add(
                    'sub_kegiatan_id',
                    'Sub kegiatan tidak valid, tidak aktif, atau tidak sesuai tahun anggaran login.'
                );
            }

            $pagu = (float) $this->input('pagu', 0);
            $terpakai = (float) $this->input('terpakai', 0);

            if ($terpakai > $pagu) {
                $validator->errors()->add(
                    'terpakai',
                    'Nilai terpakai tidak boleh melebihi pagu.'
                );
            }
        });
    }
}