<?php

namespace App\Http\Requests;

use App\Models\Anggaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateUsulanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usulan = $this->route('usulan');

        // Hanya pemohon asli atau admin yang boleh update
        return $this->user()->isAdmin()
            || $usulan?->pemohon_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'judul'           => ['required', 'string', 'max:200'],
            'latar_belakang'  => ['nullable', 'string', 'max:2000'],
            'keterangan'      => ['nullable', 'string', 'max:2000'],
            'anggaran_id'     => ['required', 'exists:anggaran,id'],

            'items'                         => ['required', 'array', 'min:1'],
            'items.*.kategori_id'           => ['required', 'exists:kategori_barang,id'],
            'items.*.nama_barang'           => ['required', 'string', 'max:200'],
            'items.*.spesifikasi'           => ['nullable', 'string', 'max:1000'],
            'items.*.satuan'                => ['required', 'string', 'max:30'],
            'items.*.jumlah'                => ['required', 'integer', 'min:1'],
            'items.*.harga_satuan_estimasi' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'exists'   => 'Pilihan :attribute tidak valid.',
            'numeric'  => 'Kolom :attribute harus berupa angka.',
            'min'      => 'Kolom :attribute minimal :min.',
        ];
    }

    public function attributes(): array
    {
        return [
            'judul'                         => 'judul usulan',
            'latar_belakang'                => 'latar belakang',
            'anggaran_id'                   => 'anggaran',
            'items'                         => 'rincian barang',
            'items.*.nama_barang'           => 'nama barang',
            'items.*.jumlah'                => 'jumlah',
            'items.*.harga_satuan_estimasi' => 'harga satuan',
            'items.*.kategori_id'           => 'kategori',
            'items.*.satuan'                => 'satuan',
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
                    ->sum(fn ($item) =>
                        ((float) $item['jumlah']) * ((float) $item['harga_satuan_estimasi'])
                    );

                $anggaran = Anggaran::find($this->input('anggaran_id'));

                if (! $anggaran) return;

                // Kalau anggaran berubah, cek sisa anggaran baru
                // Kalau anggaran sama, sisa + estimasi lama adalah batas atasnya
                $usulan = $this->route('usulan');
                $estimasiLama = (float) ($usulan?->total_estimasi ?? 0);
                $anggaranSama = $usulan?->anggaran_id === (int) $this->input('anggaran_id');

                $sisaEfektif = $anggaranSama
                    ? (float) $anggaran->sisa + $estimasiLama
                    : (float) $anggaran->sisa;

                if ($totalEstimasi > $sisaEfektif) {
                    $validator->errors()->add(
                        'items',
                        sprintf(
                            'Total estimasi Rp %s melebihi sisa anggaran Rp %s.',
                            number_format($totalEstimasi, 0, ',', '.'),
                            number_format($sisaEfektif, 0, ',', '.'),
                        )
                    );
                }
            },
        ];
    }
}