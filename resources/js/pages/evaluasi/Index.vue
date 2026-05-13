<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Star, FileText, ArrowRight, Inbox, Award, History } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import EmptyState from '@/components/ev/EmptyState.vue';

type SiapEvaluasi = {
    id: number;
    no_pengadaan: string;
    no_kontrak: string | null;
    nilai_kontrak: string;
    metode: string;
    usulan: {
        id: number;
        no_usulan: string;
        judul: string;
        total_estimasi: string;
        pemohon: {
            id: number;
            name: string;
            unit_kerja: { id: number; nama: string } | null;
        } | null;
    } | null;
    penyedia: { id: number; nama: string } | null;
};

type SudahDievaluasi = {
    id: number;
    tanggal_evaluasi: string;
    nilai_rata_rata: string;
    rekomendasi: string;
    pengadaan: {
        id: number;
        no_pengadaan: string;
        nilai_kontrak: string;
        penyedia: { id: number; nama: string } | null;
        usulan: { id: number; no_usulan: string; judul: string } | null;
    } | null;
    evaluator: { id: number; name: string } | null;
};

const props = withDefaults(
    defineProps<{
        siapEvaluasi?: SiapEvaluasi[];
        sudahDievaluasi?: SudahDievaluasi[];
    }>(),
    {
        siapEvaluasi: () => [],
        sudahDievaluasi: () => [],
    },
);

const formatRupiah = (val: string | number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(val));

const formatDate = (val: string) =>
    new Date(val).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

const formatRekomendasi = (val: string) =>
    val.split('_').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');

// Mapping rekomendasi ke variant badge
const rekomendasiVariant = (val: string): string => {
    if (val === 'sangat_baik') return 'badge-success';
    if (val === 'baik') return 'badge-info';
    if (val === 'cukup') return 'badge-warning';
    return 'badge-danger'; // kurang & tidak_direkomendasikan
};

// Stats
const avgRating = computed(() => {
    if (props.sudahDievaluasi.length === 0) return 0;
    const sum = props.sudahDievaluasi.reduce(
        (acc, e) => acc + Number(e.nilai_rata_rata ?? 0),
        0,
    );
    return Number((sum / props.sudahDievaluasi.length).toFixed(2));
});
</script>

<template>
    <Head title="Evaluasi" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <PageHeader
            title="Evaluasi Penyedia"
            :subtitle="`${siapEvaluasi.length} menunggu evaluasi · ${sudahDievaluasi.length} sudah dievaluasi`"
            eyebrow="Bagian Perencanaan"
        />

        <!-- Stats banner -->
        <div
            v-if="sudahDievaluasi.length > 0"
            class="rounded-md border border-border bg-card p-5"
        >
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-accent/10 text-accent">
                        <Award class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-eyebrow">Rata-rata Nilai Penyedia</div>
                        <div class="font-display text-2xl font-semibold text-foreground">
                            {{ avgRating }} <span class="text-base text-muted-foreground">/ 5.00</span>
                        </div>
                    </div>
                </div>
                <div class="hidden text-right text-xs text-muted-foreground sm:block">
                    Berdasarkan {{ sudahDievaluasi.length }} evaluasi terakhir
                </div>
            </div>
        </div>

        <!-- Empty global state -->
        <EmptyState
            v-if="siapEvaluasi.length === 0 && sudahDievaluasi.length === 0"
            title="Belum ada pengadaan untuk dievaluasi"
            description="Pengadaan dengan pembayaran yang sudah lunas akan otomatis muncul di sini untuk dievaluasi kinerjanya."
            :icon="Inbox"
        />

        <!-- Section: Siap Dievaluasi -->
        <Section
            v-if="siapEvaluasi.length > 0"
            title="Menunggu Evaluasi"
            eyebrow="Antrean"
            :description="`${siapEvaluasi.length} pengadaan siap dievaluasi`"
        >
            <div class="-mx-6 -my-6 divide-y divide-border">
                <Link
                    v-for="row in siapEvaluasi"
                    :key="row.id"
                    :href="`/evaluasi/${row.id}`"
                    class="flex items-center gap-4 px-6 py-4 transition hover:bg-muted/30"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-warning);"
                    >
                        <Star class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs text-muted-foreground">
                                {{ row.no_pengadaan }}
                            </span>
                            <span class="badge-status badge-warning">Belum Dievaluasi</span>
                        </div>
                        <div class="mt-1 font-medium">{{ row.usulan?.judul }}</div>
                        <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-muted-foreground">
                            <span v-if="row.penyedia">{{ row.penyedia.nama }}</span>
                            <span v-if="row.usulan?.pemohon?.unit_kerja">
                                · {{ row.usulan.pemohon.unit_kerja.nama }}
                            </span>
                        </div>
                    </div>
                    <div class="hidden shrink-0 text-right sm:block">
                        <div class="text-eyebrow">Nilai Kontrak</div>
                        <div class="mt-0.5 font-mono text-sm font-semibold">
                            {{ formatRupiah(row.nilai_kontrak) }}
                        </div>
                    </div>
                    <ArrowRight class="h-4 w-4 shrink-0 text-muted-foreground" />
                </Link>
            </div>
        </Section>

        <!-- Section: Riwayat Evaluasi -->
        <Section
            v-if="sudahDievaluasi.length > 0"
            title="Riwayat Evaluasi"
            eyebrow="History"
            :description="`${sudahDievaluasi.length} evaluasi terbaru`"
        >
            <div class="-mx-6 -my-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-secondary/40 text-left text-[10px] uppercase tracking-widest text-muted-foreground">
                            <th class="px-6 py-3 font-semibold">No. Pengadaan</th>
                            <th class="px-6 py-3 font-semibold">Penyedia</th>
                            <th class="px-6 py-3 font-semibold">Tanggal</th>
                            <th class="px-6 py-3 text-right font-semibold">Rating</th>
                            <th class="px-6 py-3 font-semibold">Rekomendasi</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="ev in sudahDievaluasi"
                            :key="ev.id"
                            class="cursor-pointer transition hover:bg-muted/30"
                            @click="ev.pengadaan && $inertia.visit(`/evaluasi/${ev.pengadaan.id}`)"
                        >
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-xs">
                                {{ ev.pengadaan?.no_pengadaan ?? '—' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium">{{ ev.pengadaan?.penyedia?.nama ?? '—' }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ ev.pengadaan?.usulan?.judul ?? '—' }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">
                                {{ formatDate(ev.tanggal_evaluasi) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <span class="font-display text-base font-bold text-primary">
                                    {{ Number(ev.nilai_rata_rata).toFixed(2) }}
                                </span>
                                <span class="ml-0.5 text-xs text-muted-foreground">/ 5</span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="badge-status"
                                    :class="rekomendasiVariant(ev.rekomendasi)"
                                >
                                    {{ formatRekomendasi(ev.rekomendasi) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <Link
                                    v-if="ev.pengadaan"
                                    :href="`/evaluasi/${ev.pengadaan.id}`"
                                    class="text-xs font-semibold text-primary hover:underline"
                                    @click.stop
                                >
                                    Lihat →
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Section>
    </div>
</template>