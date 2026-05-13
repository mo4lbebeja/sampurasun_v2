<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DpaAnggaranSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'tahun_anggaran' => 2026,
                'no_dpa' => 'DPA-2026',
                'tanggal_dpa' => '2026-01-01',
                'nama_dpa' => 'DPA Tahun Anggaran 2026',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tahun_anggaran' => 2027,
                'no_dpa' => 'DPA-2027',
                'tanggal_dpa' => '2027-01-01',
                'nama_dpa' => 'DPA Tahun Anggaran 2027',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($data as $item) {
            DB::table('dpa_anggaran')->updateOrInsert(
                [
                    'tahun_anggaran' => $item['tahun_anggaran'],
                    'no_dpa' => $item['no_dpa'],
                ],
                $item
            );
        }
    }
}