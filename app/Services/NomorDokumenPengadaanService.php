<?php

namespace App\Services;

use App\Models\DokumenPengadaan;
use App\Models\Pengadaan;
use Illuminate\Support\Facades\DB;

class NomorDokumenPengadaanService
{
    public function generateSet(Pengadaan $pengadaan, int $tahun, int $bulan, int $userId): void
    {
        DB::transaction(function () use ($pengadaan, $tahun, $bulan, $userId) {
            $alreadyExists = DokumenPengadaan::query()
                ->where('pengadaan_id', $pengadaan->id)
                ->whereIn('jenis', [
                    'bast',
                    'bapmhp',
                    'baprhp',
                    'bapp',
                ])
                ->exists();

            if ($alreadyExists) {
                return;
            }

            $documents = [
                [
                    'jenis' => 'bast',
                    'kode_dokumen' => 'BAST',
                    'nama_dokumen' => 'Berita Acara Serah Terima',
                ],
                [
                    'jenis' => 'bapmhp',
                    'kode_dokumen' => 'BAPMHP',
                    'nama_dokumen' => 'Berita Acara Pemeriksaan Mutu Hasil Pekerjaan',
                ],
                [
                    'jenis' => 'baprhp',
                    'kode_dokumen' => 'BAPRHP',
                    'nama_dokumen' => 'Berita Acara Penerimaan Hasil Pekerjaan',
                ],
                [
                    'jenis' => 'bapp',
                    'kode_dokumen' => 'BAPP',
                    'nama_dokumen' => 'Berita Acara Persetujuan Pembayaran',
                ],
            ];

            foreach ($documents as $document) {
                $nomorUrut = $this->nextNumber($tahun);
                $bulanRomawi = $this->bulanRomawi($bulan);

                $nomor = sprintf(
                    '027/%d/%s/%s/%d',
                    $nomorUrut,
                    $document['kode_dokumen'],
                    $bulanRomawi,
                    $tahun
                );

                DokumenPengadaan::create([
                    'pengadaan_id' => $pengadaan->id,
                    'jenis' => $document['jenis'],
                    'nama_dokumen' => $document['nama_dokumen'],
                    'nomor' => $nomor,
                    'kode_surat' => '027',
                    'nomor_urut' => $nomorUrut,
                    'kode_dokumen' => $document['kode_dokumen'],
                    'bulan_romawi' => $bulanRomawi,
                    'tahun' => $tahun,
                    'tanggal' => now()->toDateString(),
                    'created_by' => $userId,
                ]);
            }
        });
    }

    private function nextNumber(int $tahun): int
    {
        $lastNumber = DokumenPengadaan::query()
            ->where('tahun', $tahun)
            ->lockForUpdate()
            ->max('nomor_urut');

        return ((int) $lastNumber) + 1;
    }

    private function bulanRomawi(int $bulan): string
    {
        return [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ][$bulan] ?? 'I';
    }
}