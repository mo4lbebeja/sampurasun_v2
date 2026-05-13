<?php

namespace App\Services;

use App\Models\DocumentSequence;
use App\Models\Pengadaan;
use App\Models\UsulanPengadaan;
use Illuminate\Database\QueryException;

class DocumentNumberService
{
    public function generateInsideTransaction(
        string $type,
        string $prefix,
        int $tahunAnggaran,
        ?int $bulan = null
    ): string {
        $bulan ??= (int) now()->format('m');

        $tahun = (string) $tahunAnggaran;
        $bulanFormatted = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

        try {
            DocumentSequence::firstOrCreate(
                [
                    'type' => $type,
                    'year' => $tahunAnggaran,
                    'month' => $bulan,
                ],
                [
                    'last_number' => $this->getLastExistingNumber(
                        type: $type,
                        prefix: $prefix,
                        year: $tahun,
                        month: $bulanFormatted,
                    ),
                ]
            );
        } catch (QueryException $e) {
            // Jika request lain sudah membuat sequence yang sama,
            // lanjutkan lalu ambil row tersebut dengan lockForUpdate().
        }

        $sequence = DocumentSequence::where('type', $type)
            ->where('year', $tahunAnggaran)
            ->where('month', $bulan)
            ->lockForUpdate()
            ->firstOrFail();

        $sequence->last_number++;
        $sequence->save();

        $number = str_pad($sequence->last_number, 3, '0', STR_PAD_LEFT);

        return sprintf(
            '%s/%s/%s/%s',
            $prefix,
            $tahun,
            $bulanFormatted,
            $number
        );
    }

    private function getLastExistingNumber(string $type, string $prefix, string $year, string $month): int
    {
        $pattern = "{$prefix}/{$year}/{$month}/%";

        $lastNumber = match ($type) {
            'usulan' => UsulanPengadaan::withTrashed()
                ->where('no_usulan', 'like', $pattern)
                ->get()
                ->map(fn (UsulanPengadaan $usulan) => $this->extractSequenceNumber($usulan->no_usulan))
                ->max(),

            'pengadaan' => Pengadaan::where('no_pengadaan', 'like', $pattern)
                ->get()
                ->map(fn (Pengadaan $pengadaan) => $this->extractSequenceNumber($pengadaan->no_pengadaan))
                ->max(),

            default => 0,
        };

        return (int) ($lastNumber ?? 0);
    }

    private function extractSequenceNumber(string $documentNumber): int
    {
        $parts = explode('/', $documentNumber);

        return (int) end($parts);
    }
}