<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Anggaran;
use Illuminate\Validation\Validator;

class StoreUsulanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('sarana_umum') || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'judul'          => ['required', 'string', 'max:200'],
            'latar_belakang' => ['nullable', 'string', 'max:2000'],
            'keterangan'     => ['nullable', 'string', 'max:2000'],
            'anggaran_id'    => ['required', 'exists:anggaran,id'],

            'items'                              => ['required', 'array', 'min:1'],
            'items.*.kategori_id'                => ['required', 'exists:kategori_barang,id'],
            'items.*.nama_barang'                => ['required', 'string', 'max:200'],
            'items.*.spesifikasi'                => ['nullable', 'string', 'max:1000'],
            'items.*.satuan'                     => ['required', 'string', 'max:30'],
            'items.*.jumlah'                     => ['required', 'integer', 'min:1'],
            'items.*.harga_satuan_estimasi'      => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'required'       => 'Kolom :attribute wajib diisi.',
            'exists'         => 'Pilihan :attribute tidak valid.',
            'numeric'        => 'Kolom :attribute harus berupa angka.',
            'min'            => 'Kolom :attribute minimal :min.',
        ];
    }

    public function attributes(): array
    {
        return [
            'judul'                          => 'judul usulan',
            'latar_belakang'                 => 'latar belakang',
            'anggaran_id'                    => 'anggaran',
            'items'                          => 'rincian barang',
            'items.*.nama_barang'            => 'nama barang',
            'items.*.jumlah'                 => 'jumlah',
            'items.*.harga_satuan_estimasi'  => 'harga satuan',
            'items.*.kategori_id'            => 'kategori',
            'items.*.satuan'                 => 'satuan',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $totalEstimasi = collect($this->input('items', []))
                    ->sum(fn ($item) => ((float) $item['jumlah']) * ((float) $item['harga_satuan_estimasi']));

                $anggaran = Anggaran::query()
                    ->whereKey($this->input('anggaran_id'))
                    ->first();

                if (! $anggaran) {
                    return;
                }

                if ($totalEstimasi > (float) $anggaran->sisa) {
                    $validator->errors()->add(
                        'items',
                        sprintf(
                            'Total estimasi Rp %s melebihi sisa anggaran Rp %s.',
                            number_format($totalEstimasi, 0, ',', '.'),
                            number_format((float) $anggaran->sisa, 0, ',', '.'),
                        )
                    );
                }
            },
        ];
    }
}