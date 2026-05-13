<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { FileText, Printer, Lock, Sparkles } from 'lucide-vue-next';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

type DokumenPengadaan = {
    id: number;
    jenis: string;
    nama_dokumen: string;
    nomor: string;
    tanggal?: string | null;
};

const props = defineProps<{
    pengadaanId: number;
    dokumenPengadaan?: DokumenPengadaan[];
}>();

const dokumenList = computed(() => props.dokumenPengadaan ?? []);

const dokumenMap = computed(() => {
    const map: Record<string, DokumenPengadaan> = {};

    dokumenList.value.forEach((item) => {
        map[item.jenis] = item;
    });

    return map;
});

const hasGenerated = computed(() => {
    return ['bast', 'bapmhp', 'baprhp', 'bapp'].every((jenis) => dokumenMap.value[jenis]);
});

const rows = computed(() => [
    {
        jenis: 'bast',
        kode: 'BAST',
        nama: 'Berita Acara Serah Terima',
        placeholder: '027/____/BAST/__/____',
    },
    {
        jenis: 'bapmhp',
        kode: 'BAPMHP',
        nama: 'Berita Acara Pemeriksaan Mutu Hasil Pekerjaan',
        placeholder: '027/____/BAPMHP/__/____',
    },
    {
        jenis: 'baprhp',
        kode: 'BAPRHP',
        nama: 'Berita Acara Penerimaan Hasil Pekerjaan',
        placeholder: '027/____/BAPRHP/__/____',
    },
    {
        jenis: 'bapp',
        kode: 'BAPP',
        nama: 'Berita Acara Persetujuan Pembayaran',
        placeholder: '027/____/BAPP/__/____',
    },
]);

const confirmGenerate = () => {
    return confirm(
        [
            'Generate nomor dokumen pengadaan?',
            '',
            'Sistem akan membuat nomor berurutan untuk:',
            '- BAST',
            '- BAPMHP',
            '- BAPRHP',
            '- BAPP',
            '',
            'Nomor yang sudah dibuat akan dikunci untuk menjaga kredibilitas dokumen.',
            'Lanjutkan?',
        ].join('\n'),
    );
};
</script>

<template>
    <div class="rounded-lg border border-border bg-card p-5">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <div class="text-eyebrow">Dokumen Pengadaan</div>
                <h3 class="font-display text-lg font-semibold">
                    Nomor Dokumen
                </h3>
                <p class="mt-1 text-xs text-muted-foreground">
                    Nomor dibuat otomatis dan berurutan.
                </p>
            </div>

            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md"
                :class="hasGenerated ? 'bg-primary/10 text-primary' : 'bg-secondary text-muted-foreground'"
            >
                <Lock v-if="hasGenerated" class="h-4 w-4" />
                <FileText v-else class="h-4 w-4" />
            </div>
        </div>

        <div class="space-y-3">
            <div
                v-for="row in rows"
                :key="row.jenis"
                class="rounded-md border border-border bg-background p-3"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                            {{ row.kode }}
                        </div>

                        <div class="mt-0.5 text-xs text-muted-foreground">
                            {{ row.nama }}
                        </div>

                        <div
                            class="mt-1 font-mono text-sm font-semibold"
                            :class="dokumenMap[row.jenis] ? 'text-primary' : 'text-muted-foreground/60'"
                        >
                            {{ dokumenMap[row.jenis]?.nomor ?? row.placeholder }}
                        </div>
                    </div>

                    <a
                        v-if="dokumenMap[row.jenis]"
                        :href="`/dokumen/${pengadaanId}/cetak/${row.jenis}`"
                        target="_blank"
                        class="inline-flex h-8 items-center gap-1 rounded-md border border-border px-2 text-xs font-semibold hover:bg-muted"
                    >
                        <Printer class="h-3.5 w-3.5" />
                        Cetak
                    </a>
                </div>
            </div>

            <div class="rounded-md border border-border bg-background p-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                            Ringkasan Kontrak
                        </div>
                        <div class="mt-0.5 text-xs text-muted-foreground">
                            Dicetak dari data kontrak.
                        </div>
                    </div>

                    <a
                        :href="`/dokumen/${pengadaanId}/cetak/ringkasan-kontrak`"
                        target="_blank"
                        class="inline-flex h-8 items-center gap-1 rounded-md border border-border px-2 text-xs font-semibold hover:bg-muted"
                    >
                        <Printer class="h-3.5 w-3.5" />
                        Cetak
                    </a>
                </div>
            </div>
            <!-- Surat Pesanan -->
            <div class="rounded-md border border-border bg-background p-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                            Surat Pesanan
                        </div>
                        <div class="mt-0.5 text-xs text-muted-foreground">
                            Surat Pesanan (SP)
                        </div>
                    </div>

                    <a
                        :href="`/dokumen/${pengadaanId}/cetak/surat-pesanan`"
                        target="_blank"
                        class="inline-flex h-8 items-center gap-1 rounded-md border border-border px-2 text-xs font-semibold hover:bg-muted"
                    >
                        <Printer class="h-3.5 w-3.5" />
                        Cetak
                    </a>
                </div>
            </div>
            <!-- SPP/SPTJ -->
            <div class="rounded-md border border-border bg-background p-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                            SPP/SPTJ
                        </div>
                        <div class="mt-0.5 text-xs text-muted-foreground">
                            SPP-GU & Tanggung Jawab Belanja
                        </div>
                    </div>

                    <a
                        :href="`/dokumen/${pengadaanId}/cetak/spp-sptj`"
                        target="_blank"
                        class="inline-flex h-8 items-center gap-1 rounded-md border border-border px-2 text-xs font-semibold hover:bg-muted"
                    >
                        <Printer class="h-3.5 w-3.5" />
                        Cetak
                    </a>
                </div>
            </div>
        </div>

        <div v-if="!hasGenerated" class="mt-4">
            <Link
                :href="`/dokumen/${pengadaanId}/generate-dokumen`"
                method="post"
                as="button"
                preserve-scroll
                class="w-full"
                @before="confirmGenerate"
            >
                <PrimaryButton type="button" variant="primary" class="w-full justify-center">
                    <Sparkles class="h-4 w-4" />
                    Generate Nomor Dokumen
                </PrimaryButton>
            </Link>

            <p class="mt-2 text-xs text-muted-foreground">
                Nomor belum dibuat. Yang tampil saat ini hanya format bayangan.
            </p>
        </div>

        <div
            v-else
            class="mt-4 rounded-md border border-primary/20 bg-primary/5 p-3 text-xs text-primary"
        >
            Nomor dokumen sudah dibuat dan dikunci untuk menjaga kredibilitas dokumen.
        </div>
    </div>
</template>