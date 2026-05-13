<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Wallet, FileText, ArrowRight, Inbox, Clock, CheckCircle2 } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

type Pengadaan = {
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
        status: string;
        pemohon: {
            id: number;
            name: string;
            unit_kerja: { id: number; nama: string } | null;
        } | null;
    } | null;
    penyedia: {
        id: number;
        nama: string;
        nama_bank: string | null;
        rekening_bank: string | null;
    } | null;
    pembayaran: {
        id: number;
        status: string;
        no_spm: string | null;
        no_sp2d: string | null;
        nilai_bayar: string;
        nilai_bersih: string;
        tanggal_bayar: string | null;
        updated_at: string;
    } | null;
};

const props = withDefaults(
    defineProps<{
        pengadaan?: Pengadaan[];
    }>(),
    {
        pengadaan: () => [],
    },
);

const formatRupiah = (val: string | number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(val));

const formatDate = (val: string | null) => {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const formatRelative = (val: string) => {
    const diff = (Date.now() - new Date(val).getTime()) / 1000;
    if (diff < 60) return 'Baru saja';
    if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`;
    if (diff < 604800) return `${Math.floor(diff / 86400)} hari lalu`;
    return new Date(val).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
};

// Bagi 3 berdasarkan status pembayaran
const belumDimulai = computed(() =>
    props.pengadaan.filter((p) => !p.pembayaran || p.pembayaran.status === 'pending'),
);

const dalamProses = computed(() =>
    props.pengadaan.filter((p) => p.pembayaran?.status === 'diproses'),
);

const totalNilai = computed(() =>
    props.pengadaan.reduce((sum, p) => sum + Number(p.nilai_kontrak ?? 0), 0),
);
</script>

<template>
    <Head title="Pembayaran" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <PageHeader
            title="Pembayaran"
            :subtitle="`${pengadaan.length} pengadaan dalam tahap pembayaran`"
            eyebrow="Bagian Keuangan"
        >
            <template #actions>
                <Link href="/pembayaran/rekap">
                    <PrimaryButton variant="secondary">
                        <FileText class="h-4 w-4" />
                        Lihat Rekap
                    </PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <!-- Total nilai banner -->
        <div
            v-if="pengadaan.length > 0"
            class="rounded-md border border-border bg-card p-5"
        >
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-accent/10 text-accent">
                        <Wallet class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-eyebrow">Total Nilai Kontrak</div>
                        <div class="font-display text-2xl font-semibold text-foreground">
                            {{ formatRupiah(totalNilai) }}
                        </div>
                    </div>
                </div>
                <div class="hidden text-right text-xs text-muted-foreground sm:block">
                    {{ belumDimulai.length }} pending<br>
                    {{ dalamProses.length }} sedang diproses
                </div>
            </div>
        </div>

        <!-- Empty global state -->
        <EmptyState
            v-if="pengadaan.length === 0"
            title="Belum ada pengadaan untuk dibayar"
            description="Pengadaan dengan dokumen UPBJ yang sudah lengkap akan otomatis muncul di sini untuk pemrosesan SPM, SP2D, dan pembayaran ke penyedia."
            :icon="Inbox"
        />

        <!-- Section: Belum Dimulai (Pending) -->
        <Section
            v-if="belumDimulai.length > 0"
            title="Belum Dimulai"
            eyebrow="Antrean Baru"
            :description="`${belumDimulai.length} pengadaan menunggu pemrosesan SPM/SP2D`"
        >
            <div class="-mx-6 -my-6 divide-y divide-border">
                <Link
                    v-for="row in belumDimulai"
                    :key="row.id"
                    :href="`/pembayaran/${row.id}`"
                    class="flex items-center gap-4 px-6 py-4 transition hover:bg-muted/30"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-warning);"
                    >
                        <FileText class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs text-muted-foreground">
                                {{ row.no_pengadaan }}
                            </span>
                            <span class="badge-status badge-warning">Belum Diproses</span>
                        </div>
                        <div class="mt-1 font-medium">{{ row.usulan?.judul }}</div>
                        <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-muted-foreground">
                            <span v-if="row.penyedia">{{ row.penyedia.nama }}</span>
                            <span v-if="row.penyedia?.nama_bank">
                                · {{ row.penyedia.nama_bank }}
                                <span v-if="row.penyedia.rekening_bank" class="font-mono">
                                    {{ row.penyedia.rekening_bank }}
                                </span>
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

        <!-- Section: Dalam Proses -->
        <Section
            v-if="dalamProses.length > 0"
            title="Dalam Proses"
            eyebrow="Sedang Dikerjakan"
            :description="`${dalamProses.length} pengadaan sudah memiliki SPM/SP2D, menunggu finalisasi`"
        >
            <div class="-mx-6 -my-6 divide-y divide-border">
                <Link
                    v-for="row in dalamProses"
                    :key="row.id"
                    :href="`/pembayaran/${row.id}`"
                    class="flex items-center gap-4 px-6 py-4 transition hover:bg-muted/30"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-info);"
                    >
                        <Clock class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs text-muted-foreground">
                                {{ row.no_pengadaan }}
                            </span>
                            <span class="badge-status badge-info">Diproses</span>
                        </div>
                        <div class="mt-1 font-medium">{{ row.usulan?.judul }}</div>
                        <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-muted-foreground">
                            <span v-if="row.pembayaran?.no_spm">
                                SPM: <span class="font-mono">{{ row.pembayaran.no_spm }}</span>
                            </span>
                            <span v-if="row.pembayaran?.no_sp2d">
                                · SP2D: <span class="font-mono">{{ row.pembayaran.no_sp2d }}</span>
                            </span>
                            <span v-if="row.pembayaran?.tanggal_bayar">
                                · {{ formatDate(row.pembayaran.tanggal_bayar) }}
                            </span>
                            <span v-if="row.pembayaran">
                                · diupdate {{ formatRelative(row.pembayaran.updated_at) }}
                            </span>
                        </div>
                    </div>
                    <div class="hidden shrink-0 text-right sm:block">
                        <div class="text-eyebrow">Nilai Bersih</div>
                        <div class="mt-0.5 font-mono text-sm font-semibold">
                            {{ formatRupiah(row.pembayaran?.nilai_bersih ?? row.nilai_kontrak) }}
                        </div>
                    </div>
                    <ArrowRight class="h-4 w-4 shrink-0 text-muted-foreground" />
                </Link>
            </div>
        </Section>

        <!-- Helper info -->
        <div
            v-if="pengadaan.length > 0"
            class="rounded-md border border-border bg-card p-4"
        >
            <div class="flex items-start gap-3">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-white"
                    style="background-color: var(--color-brand-info);"
                >
                    <Wallet class="h-4 w-4" />
                </div>
                <div class="text-sm">
                    <div class="text-eyebrow mb-1">Alur Kerja Keuangan</div>
                    <p class="text-muted-foreground">
                        Untuk setiap pengadaan: isi nomor <span class="font-semibold text-foreground">SPM</span>
                        (Surat Perintah Membayar) dan <span class="font-semibold text-foreground">SP2D</span>
                        (Surat Perintah Pencairan Dana), input pajak <span class="font-semibold text-foreground">PPh & PPN</span>,
                        upload bukti transfer, dan klik
                        <span class="font-semibold text-foreground">"Tandai Lunas"</span>
                        untuk menyelesaikan tahap pembayaran. Pengadaan akan otomatis diteruskan ke Bagian Perencanaan untuk evaluasi.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>