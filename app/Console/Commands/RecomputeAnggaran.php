<?php

namespace App\Console\Commands;

use App\Models\Anggaran;
use Illuminate\Console\Command;

class RecomputeAnggaran extends Command
{
    protected $signature = 'anggaran:recompute {--id= : ID anggaran spesifik (opsional, default semua)}';

    protected $description = 'Recompute nilai terpakai untuk semua anggaran (atau anggaran tertentu)';

    public function handle(): int
    {
        $query = Anggaran::query();

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $anggaranList = $query->get();

        if ($anggaranList->isEmpty()) {
            $this->warn('Tidak ada anggaran yang ditemukan.');
            return self::SUCCESS;
        }

        $this->info("Recomputing {$anggaranList->count()} anggaran...");
        $bar = $this->output->createProgressBar($anggaranList->count());

        foreach ($anggaranList as $a) {
            $a->recompute();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Selesai. Detail:');

        $this->table(
            ['Kode', 'Pagu', 'Terpakai', 'Sisa'],
            $anggaranList->fresh()->map(fn ($a) => [
                $a->kode_rekening,
                'Rp ' . number_format($a->pagu),
                'Rp ' . number_format($a->terpakai),
                'Rp ' . number_format($a->sisa),
            ])->toArray(),
        );

        return self::SUCCESS;
    }
}