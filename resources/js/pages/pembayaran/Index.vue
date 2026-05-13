<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Wallet, FileText, ArrowRight, Inbox, Clock } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

// ── Types ────────────────────────────────────────────────────────

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

// SEBELUM: Pengadaan[] (array biasa)
// SESUDAH: Paginated<Pengadaan> (objek paginator Laravel)
type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

// ── Props ────────────────────────────────────────────────────────

// SEBELUM: pengadaan?: Pengadaan[]
// SESUDAH: pengadaan: Paginated<Pengadaan>
const props = defineProps<{
    pengadaan: Paginated<Pengadaan>;
}>();

// ── Helpers ──────────────────────────────────────────────────────

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

// SEBELUM: props.pengadaan.filter(...)
// SESUDAH: props.pengadaan.data.filter(...) — filter dari data halaman ini
const belumDimulai = computed(() =>
    props.pengadaan.data.filter((p) => !p.pembayaran || p.pembayaran.status === 'pending'),
);

const dalamProses = computed(() =>
    props.pengadaan.data.filter((p) => p.pembayaran?.status === 'diproses'),
);

// SEBELUM: props.pengadaan.reduce(...)
// SESUDAH: sum dari halaman ini; untuk total akurat gunakan rekap
const totalNilai = computed(() =>
    props.pengadaan.data.reduce((sum, p) => sum + Number(p.nilai_kontrak ?? 0), 0),
);
</script>

<template>
    <Head title="Pembayaran" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <PageHeader
            title="Pembayaran"
            :subtitle="`${pengadaan.total} pengadaan dalam tahap pembayaran`"
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

        <!-- Total nilai banner (dari halaman ini) -->
        <div
            v-if="pengadaan.total > 0"
            class="rounded-md border border-border bg-card p-5"
        >
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-accent/10 text-accent">
                        <Wallet class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-eyebrow">Total Nilai Kontrak (halaman ini)</div>
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

        <!-- Empty state global -->
        <EmptyState
            v-if="pengadaan.total === 0"
            title="Belum ada pengadaan untuk dibayar"
            description="Pengadaan dengan dokumen UPBJ yang sudah lengkap akan otomatis muncul di sini untuk pemrosesan SPM, SP2D, dan pembayaran ke penyedia."
            :icon="Inbox"
        />

        <template v-else>
            <!-- ── Section: Belum Dimulai ─────────────────────────────── -->
            <Section
                v-if="belumDimulai.length > 0"
                title="Menunggu Diproses"
                eyebrow="Pending"
                :description="`${belumDimulai.length} pengadaan menunggu pemrosesan SPM`"
            >
                <div class="-mx-6 -my-6 divide-y divide-border">
                    <!-- SEBELUM: v-for="row in belumDimulai" (dari props.pengadaan.filter) -->
                    <!-- SESUDAH: v-for="row in belumDimulai" (dari props.pengadaan.data.filter) -->
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
                            <Wallet class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-mono text-xs text-muted-foreground">
                                {{ row.no_pengadaan }}
                            </div>
                            <div class="mt-0.5 font-medium">{{ row.usulan?.judul }}</div>
                            <div class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-muted-foreground">
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

            <!-- ── Section: Dalam Proses ──────────────────────────────── -->
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
                            </div>
                            <div class="mt-0.5 font-medium">{{ row.usulan?.judul }}</div>
                            <div class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-muted-foreground">
                                <span v-if="row.pembayaran?.no_spm">SPM: {{ row.pembayaran.no_spm }}</span>
                                <span v-if="row.pembayaran?.updated_at">
                                    · {{ formatRelative(row.pembayaran.updated_at) }}
                                </span>
                            </div>
                        </div>
                        <div class="hidden shrink-0 text-right sm:block">
                            <template v-if="row.pembayaran?.tanggal_bayar">
                                <div class="text-eyebrow">Tgl Bayar</div>
                                <div class="mt-0.5 text-sm">{{ formatDate(row.pembayaran.tanggal_bayar) }}</div>
                            </template>
                            <template v-else>
                                <div class="text-eyebrow">Nilai Kontrak</div>
                                <div class="mt-0.5 font-mono text-sm font-semibold">
                                    {{ formatRupiah(row.nilai_kontrak) }}
                                </div>
                            </template>
                        </div>
                        <ArrowRight class="h-4 w-4 shrink-0 text-muted-foreground" />
                    </Link>
                </div>
            </Section>

            <!-- ── Pagination ─────────────────────────────────────────── -->
            <div
                v-if="pengadaan.last_page > 1"
                class="flex items-center justify-between rounded-lg border border-border bg-card px-5 py-3"
            >
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ pengadaan.from }}–{{ pengadaan.to }} dari {{ pengadaan.total }}
                </div>
                <div class="flex gap-1">
                    <template v-for="(link, idx) in pengadaan.links" :key="idx">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            preserve-state
                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border border-border bg-card px-3 text-xs font-medium transition hover:bg-muted"
                            :class="{ 'border-primary bg-primary text-primary-foreground hover:bg-primary/90': link.active }"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border border-border px-3 text-xs text-muted-foreground/50"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </template>

        <!-- Helper info alur kerja -->
        <div
            v-if="pengadaan.total > 0"
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
                        dan <span class="font-semibold text-foreground">SP2D</span>,
                        input pajak <span class="font-semibold text-foreground">PPh & PPN</span>,
                        upload bukti transfer, dan klik
                        <span class="font-semibold text-foreground">"Tandai Lunas"</span>
                        untuk menyelesaikan. Pengadaan otomatis diteruskan ke Bagian Perencanaan.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>