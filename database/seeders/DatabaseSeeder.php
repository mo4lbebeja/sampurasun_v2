<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $roles = [
            ['name' => 'admin',             'display_name' => 'Administrator',          'description' => 'Akses penuh sistem'],
            ['name' => 'sarana_umum',       'display_name' => 'Bagian Sarana & Umum',   'description' => 'Pengguna / pemohon usulan pengadaan'],
            ['name' => 'pptk',              'display_name' => 'PPTK',                   'description' => 'Pejabat Pelaksana Teknis Kegiatan – penanggung jawab anggaran'],
            ['name' => 'pejabat_pengadaan', 'display_name' => 'Pejabat Pengadaan',      'description' => 'Pelaksana proses pengadaan'],
            ['name' => 'upbj',              'display_name' => 'Bagian UPBJ',            'description' => 'Administrasi dokumen pengadaan'],
            ['name' => 'keuangan',          'display_name' => 'Bagian Keuangan',        'description' => 'Pengelola pembayaran'],
            ['name' => 'perencanaan',       'display_name' => 'Bagian Perencanaan',     'description' => 'Evaluator & pelaporan pengadaan'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                [
                    'display_name' => $role['display_name'],
                    'description' => $role['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 2. Unit kerja
        DB::table('unit_kerja')->updateOrInsert(
            ['kode' => 'UK-001'],
            [
                'nama' => 'Kantor Pusat',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $unitKerjaId = DB::table('unit_kerja')->where('kode', 'UK-001')->value('id');

        // 3. User default per role (password: password)
        $users = [
            ['nip' => '198001012010011001', 'name' => 'Admin Sistem',    'email' => 'admin@instansi.go.id',       'role' => 'admin',             'jabatan' => 'Administrator'],
            ['nip' => '198202022011022001', 'name' => 'Budi Sarana',     'email' => 'sarana@instansi.go.id',      'role' => 'sarana_umum',       'jabatan' => 'Bagian Sarana & Umum'],
            ['nip' => '197505052005051001', 'name' => 'Citra Pratiwi',   'email' => 'pptk@instansi.go.id',        'role' => 'pptk',              'jabatan' => 'PPTK'],
            ['nip' => '198808082012081001', 'name' => 'Dimas Hartono',   'email' => 'pengadaan@instansi.go.id',   'role' => 'pejabat_pengadaan', 'jabatan' => 'Pejabat Pengadaan'],
            ['nip' => '199003032015031002', 'name' => 'Eka Wijaya',      'email' => 'upbj@instansi.go.id',        'role' => 'upbj',              'jabatan' => 'Bagian UPBJ'],
            ['nip' => '198707072013071001', 'name' => 'Fitri Ananda',    'email' => 'keuangan@instansi.go.id',    'role' => 'keuangan',          'jabatan' => 'Bagian Keuangan'],
            ['nip' => '198404042014041001', 'name' => 'Gunawan Saputra', 'email' => 'perencanaan@instansi.go.id', 'role' => 'perencanaan',       'jabatan' => 'Bagian Perencanaan'],
        ];

        foreach ($users as $user) {
            $roleId = DB::table('roles')->where('name', $user['role'])->value('id');

            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    'role_id' => $roleId,
                    'unit_kerja_id' => $unitKerjaId,
                    'nip' => $user['nip'],
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'jabatan' => $user['jabatan'],
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 4. Kategori barang
        $kategori = [
            ['kode' => 'KAT-01', 'nama' => 'ATK & Cetakan'],
            ['kode' => 'KAT-02', 'nama' => 'Komputer & Elektronik'],
            ['kode' => 'KAT-03', 'nama' => 'Mebel & Furniture'],
            ['kode' => 'KAT-04', 'nama' => 'Kendaraan'],
            ['kode' => 'KAT-05', 'nama' => 'Peralatan Kantor'],
            ['kode' => 'KAT-06', 'nama' => 'Bahan Habis Pakai'],
        ];

        foreach ($kategori as $item) {
            DB::table('kategori_barang')->updateOrInsert(
                ['kode' => $item['kode']],
                [
                    'nama' => $item['nama'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 5. DPA Anggaran
        $dpaList = [
            [
                'tahun_anggaran' => 2026,
                'no_dpa' => 'DPA-2026',
                'tanggal_dpa' => '2026-01-01',
                'nama_dpa' => 'DPA Tahun Anggaran 2026',
            ],
            [
                'tahun_anggaran' => 2027,
                'no_dpa' => 'DPA-2027',
                'tanggal_dpa' => '2027-01-01',
                'nama_dpa' => 'DPA Tahun Anggaran 2027',
            ],
        ];

        foreach ($dpaList as $dpa) {
            DB::table('dpa_anggaran')->updateOrInsert(
                [
                    'tahun_anggaran' => $dpa['tahun_anggaran'],
                    'no_dpa' => $dpa['no_dpa'],
                ],
                [
                    'tanggal_dpa' => $dpa['tanggal_dpa'],
                    'nama_dpa' => $dpa['nama_dpa'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 6. Sub Kegiatan
        foreach ($dpaList as $dpa) {
            $dpaId = DB::table('dpa_anggaran')
                ->where('tahun_anggaran', $dpa['tahun_anggaran'])
                ->where('no_dpa', $dpa['no_dpa'])
                ->value('id');

            DB::table('sub_kegiatan')->updateOrInsert(
                [
                    'dpa_anggaran_id' => $dpaId,
                    'kode_sub_kegiatan' => 'SUB-001',
                    'tahun_anggaran' => $dpa['tahun_anggaran'],
                ],
                [
                    'nama_kegiatan' => 'Pengadaan Sarana dan Prasarana Kantor',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 7. Anggaran contoh per tahun
        foreach ($dpaList as $dpa) {
            $subKegiatanId = DB::table('sub_kegiatan')
                ->where('tahun_anggaran', $dpa['tahun_anggaran'])
                ->where('kode_sub_kegiatan', 'SUB-001')
                ->value('id');

            DB::table('anggaran')->updateOrInsert(
                [
                    'tahun' => $dpa['tahun_anggaran'],
                    'kode_rekening' => '5.1.02.01.01.024',
                ],
                [
                    'sub_kegiatan_id' => $subKegiatanId,
                    'nama_rekening' => 'Belanja Modal Peralatan dan Mesin',
                    'uraian' => 'Belanja kebutuhan peralatan dan mesin kantor',
                    'pagu' => 500000000,
                    'terpakai' => 0,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}