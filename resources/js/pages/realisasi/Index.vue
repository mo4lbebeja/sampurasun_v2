<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import {
    Search,
    Wallet,
    TrendingUp,
    CheckCircle2,
    AlertCircle,
    ChevronDown,
    ChevronRight,
    Inbox,
    FileSpreadsheet,
} from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

type Anggaran = {
    id: number;
    tahun: number;
    kode_rekening: string;
    nama_rekening: string;
    uraian: string | null;
    pagu: string;
    terpakai: string;
    sisa: string;
    is_active: boolean;
    komitmen: number;
    realisasi: number;
    jumlah_pengadaan: number;
};

type Stats = {
    total_pagu: number;
    total_komitmen: number;
    total_realisasi: number;
    total_terpakai: number;
    total_sisa: number;
    persen_terpakai: number;
    persen_realisasi: number;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = withDefaults(
    defineProps<{
        anggaran?: Paginated<Anggaran>;
        stats?: Stats;
        tahunOptions?: number[];
        filters?: { search: string; tahun: string | number; status: string };
    }>(),
    {
        anggaran: () => ({
            data: [],
            current_page: 1, last_page: 1, from: null, to: null, total: 0, links: [],
        }),
        stats: () => ({
            total_pagu: 0, total_komitmen: 0, total_realisasi: 0,
            total_terpakai: 0, total_sisa: 0, persen_terpakai: 0, persen_realisasi: 0,
        }),
        tahunOptions: () => [],
        filters: () => ({ search: '', tahun: '', status: '' }),
    },
);

// Filter state
const search = ref(props.filters.search);
const tahun = ref(props.filters.tahun);
const status = ref(props.filters.status);

const filterOpen = ref(
    !!props.filters.search || !!props.filters.tahun || !!props.filters.status,
);

const activeFilterCount = computed(() => {
    let count = 0;
    if (props.filters.search) count++;
    if (props.filters.tahun) count++;
    if (props.filters.status) count++;
    return count;
});

const hasActiveFilters = computed(() => activeFilterCount.value > 0);

const reload = () => {
    router.get(
        '/realisasi',
        {
            search: search.value || undefined,
            tahun: tahun.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const debouncedReload = debounce(reload, 400);
watch(search, () => debouncedReload());
watch([tahun, status], () => reload());

const resetFilters = () => {
    search.value = '';
    tahun.value = '';
    status.value = '';
    reload();
};

// Drill-down expandable rows
const expandedIds = ref<Set<number>>(new Set());
const detailData = ref<Record<number, any>>({});
const detailLoading = ref<Set<number>>(new Set());

const toggleDetail = async (anggaranId: number) => {
    if (expandedIds.value.has(anggaranId)) {
        expandedIds.value.delete(anggaranId);
        expandedIds.value = new Set(expandedIds.value);
        return;
    }

    expandedIds.value.add(anggaranId);
    expandedIds.value = new Set(expandedIds.value);

    // Lazy load detail
    if (!detailData.value[anggaranId]) {
        detailLoading.value.add(anggaranId);
        try {
            const res = await fetch(`/realisasi/${anggaranId}/detail`);
            if (res.ok) detailData.value[anggaranId] = await res.json();
        } catch (e) {
            console.error(e);
        } finally {
            detailLoading.value.delete(anggaranId);
            detailLoading.value = new Set(detailLoading.value);
        }
    }
};

// Format helpers
const formatRupiah = (val: number | string) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(val) || 0);

const formatRupiahCompact = (val: number | string) => formatRupiah(val);

const formatDate = (val: string | null) => {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
};

// Persen kalkulasi per row
const persenKomitmen = (row: Anggaran) => {
    const pagu = Number(row.pagu);
    if (pagu === 0) return 0;
    return Math.min(100, (Number(row.komitmen) / pagu) * 100);
};

const persenRealisasi = (row: Anggaran) => {
    const pagu = Number(row.pagu);
    if (pagu === 0) return 0;
    return Math.min(100, (Number(row.realisasi) / pagu) * 100);
};

const persenTerpakai = (row: Anggaran) => {
    return persenKomitmen(row) + persenRealisasi(row);
};

const sisaCalc = (row: Anggaran) => {
    return Math.max(0, Number(row.pagu) - Number(row.komitmen) - Number(row.realisasi));
};

const progressTone = (persen: number) => {
    if (persen >= 90) return 'var(--color-brand-danger)';
    if (persen >= 75) return 'var(--color-brand-warning)';
    if (persen >= 50) return 'var(--color-brand-info)';
    return 'var(--color-brand-success)';
};

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'aktif', label: 'Aktif' },
    { value: 'nonaktif', label: 'Nonaktif' },
];

const isEmpty = computed(() => props.anggaran.data.length === 0);

// Pengadaan status label & badge
const pengadaanStatusBadge = (s: string) => {
    if (s === 'kontrak') return 'badge-info';
    if (s === 'selesai') return 'badge-success';
    if (s === 'batal') return 'badge-danger';
    return 'badge-muted';
};

const pengadaanStatusLabel = (s: string) => {
    return s.charAt(0).toUpperCase() + s.slice(1);
};
</script>

<template>
    <Head title="Rekap Realisasi Anggaran" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <PageHeader
            title="Rekap Realisasi Anggaran"
            :subtitle="`${anggaran.total} pos anggaran tercatat`"
            eyebrow="Laporan Keuangan"
        />

        <!-- Stats banner: 4 cards -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <Section>
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <Wallet class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Total Pagu</div>
                        <div class="font-display text-xl font-bold">
                            {{ formatRupiahCompact(stats.total_pagu) }}
                        </div>
                        <div class="text-xs text-muted-foreground">Alokasi keseluruhan</div>
                    </div>
                </div>
            </Section>

            <Section>
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-info);"
                    >
                        <FileSpreadsheet class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Komitmen</div>
                        <div class="font-display text-xl font-bold">
                            {{ formatRupiahCompact(stats.total_komitmen) }}
                        </div>
                        <div class="text-xs text-muted-foreground">Sudah kontrak, belum bayar</div>
                    </div>
                </div>
            </Section>

            <Section>
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-success);"
                    >
                        <CheckCircle2 class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Realisasi</div>
                        <div class="font-display text-xl font-bold">
                            {{ formatRupiahCompact(stats.total_realisasi) }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ stats.persen_realisasi }}% dari pagu
                        </div>
                    </div>
                </div>
            </Section>

            <Section>
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-warning);"
                    >
                        <TrendingUp class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Sisa Tersedia</div>
                        <div class="font-display text-xl font-bold text-primary">
                            {{ formatRupiahCompact(stats.total_sisa) }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ (100 - stats.persen_terpakai).toFixed(1) }}% available
                        </div>
                    </div>
                </div>
            </Section>
        </div>

        <!-- Overall progress bar -->
        <div
            v-if="stats.total_pagu > 0"
            class="rounded-lg border border-border bg-card p-5"
        >
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <div class="text-eyebrow">Progress Realisasi Keseluruhan</div>
                    <div class="font-display text-lg font-semibold">
                        {{ stats.persen_terpakai }}% dari Pagu
                    </div>
                </div>
                <div class="text-right text-xs text-muted-foreground">
                    Komitmen + Realisasi
                </div>
            </div>

            <!-- Stacked progress bar -->
            <div class="relative h-3 overflow-hidden rounded-full bg-secondary">
                <!-- Realisasi (hijau, dari kiri) -->
                <div
                    class="absolute left-0 top-0 h-full transition-all"
                    :style="`width: ${stats.persen_realisasi}%; background-color: var(--color-brand-success);`"
                />
                <!-- Komitmen (info, lanjut dari realisasi) -->
                <div
                    class="absolute top-0 h-full transition-all"
                    :style="`left: ${stats.persen_realisasi}%; width: ${stats.persen_terpakai - stats.persen_realisasi}%; background-color: var(--color-brand-info);`"
                />
            </div>

            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="inline-block h-2 w-2 rounded-full" style="background-color: var(--color-brand-success);" />
                    <span class="text-muted-foreground">Realisasi: <span class="font-semibold text-foreground">{{ stats.persen_realisasi }}%</span></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block h-2 w-2 rounded-full" style="background-color: var(--color-brand-info);" />
                    <span class="text-muted-foreground">Komitmen: <span class="font-semibold text-foreground">{{ (stats.persen_terpakai - stats.persen_realisasi).toFixed(1) }}%</span></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block h-2 w-2 rounded-full bg-secondary ring-1 ring-border" />
                    <span class="text-muted-foreground">Tersedia: <span class="font-semibold text-foreground">{{ (100 - stats.persen_terpakai).toFixed(1) }}%</span></span>
                </div>
            </div>
        </div>

        <!-- Filter section (collapsible) -->
        <div class="rounded-lg border border-border bg-card">
            <button
                type="button"
                class="flex w-full items-center justify-between gap-3 px-6 py-4 text-left transition hover:bg-muted/30"
                @click="filterOpen = !filterOpen"
            >
                <div class="flex items-center gap-3">
                    <Search class="h-4 w-4 text-muted-foreground" />
                    <div>
                        <div class="text-eyebrow">Pencarian</div>
                        <div class="font-display text-lg font-semibold">
                            Filter
                            <span
                                v-if="activeFilterCount > 0"
                                class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-xs font-semibold text-primary-foreground"
                            >
                                {{ activeFilterCount }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span
                        v-if="hasActiveFilters && !filterOpen"
                        class="hidden text-xs text-muted-foreground sm:inline"
                    >
                        {{ activeFilterCount }} filter aktif
                    </span>
                    <ChevronDown
                        class="h-5 w-5 text-muted-foreground transition-transform duration-200"
                        :class="{ 'rotate-180': filterOpen }"
                    />
                </div>
            </button>

            <div v-show="filterOpen" class="space-y-3 border-t border-border px-6 py-5">
                <div v-if="hasActiveFilters" class="flex justify-end">
                    <button
                        type="button"
                        class="text-xs font-semibold text-primary hover:underline"
                        @click="resetFilters"
                    >
                        Reset Filter
                    </button>
                </div>

                <div class="relative">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari kode atau nama rekening..."
                        class="h-11 w-full rounded-md border border-input bg-background py-2 pl-10 pr-3 text-sm placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <select
                        v-model="tahun"
                        class="h-11 rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option value="">Semua Tahun</option>
                        <option v-for="t in tahunOptions" :key="t" :value="t">{{ t }}</option>
                    </select>

                    <select
                        v-model="status"
                        class="h-11 rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <EmptyState
            v-if="isEmpty"
            :title="hasActiveFilters ? 'Tidak ada hasil yang cocok' : 'Belum ada data anggaran'"
            :description="
                hasActiveFilters
                    ? 'Coba ubah filter atau reset untuk lihat semua data.'
                    : 'Tambahkan anggaran terlebih dahulu di menu Master Data.'
            "
            :icon="Inbox"
        />

        <!-- Tabel realisasi -->
        <Section v-else title="Detail Per Pos Anggaran" eyebrow="Breakdown">
            <div class="-mx-6 -my-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-secondary/40 text-left text-[10px] uppercase tracking-widest text-muted-foreground">
                            <th class="px-6 py-3 font-semibold">Tahun</th>
                            <th class="px-6 py-3 font-semibold">Kode / Nama Rekening</th>
                            <th class="px-6 py-3 text-right font-semibold">Pagu</th>
                            <th class="px-6 py-3 text-right font-semibold">Komitmen</th>
                            <th class="px-6 py-3 text-right font-semibold">Realisasi</th>
                            <th class="px-6 py-3 text-right font-semibold">Sisa</th>
                            <th class="px-6 py-3 font-semibold">Progress</th>
                            <th class="px-6 py-3 w-12"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <template v-for="row in anggaran.data" :key="row.id">
                            <!-- Main row -->
                            <tr
                                class="cursor-pointer transition hover:bg-muted/30"
                                @click="toggleDetail(row.id)"
                            >
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex rounded-md bg-secondary px-2 py-0.5 font-mono text-xs font-bold">
                                        {{ row.tahun }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-mono text-xs font-bold text-primary">
                                        {{ row.kode_rekening }}
                                    </div>
                                    <div class="mt-0.5 font-medium">{{ row.nama_rekening }}</div>
                                    <div class="mt-0.5 text-xs text-muted-foreground">
                                        {{ row.jumlah_pengadaan }} pengadaan terkait
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right font-mono text-xs">
                                    {{ formatRupiahCompact(row.pagu) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right font-mono text-xs">
                                    <span style="color: var(--color-brand-info);">
                                        {{ formatRupiahCompact(row.komitmen) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right font-mono text-xs">
                                    <span style="color: var(--color-brand-success);">
                                        {{ formatRupiahCompact(row.realisasi) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right font-mono text-xs font-semibold text-primary">
                                    {{ formatRupiahCompact(sisaCalc(row)) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <!-- Stacked mini progress bar -->
                                        <div class="relative h-2 w-24 overflow-hidden rounded-full bg-secondary">
                                            <div
                                                class="absolute left-0 top-0 h-full transition-all"
                                                :style="`width: ${persenRealisasi(row)}%; background-color: var(--color-brand-success);`"
                                            />
                                            <div
                                                class="absolute top-0 h-full transition-all"
                                                :style="`left: ${persenRealisasi(row)}%; width: ${persenKomitmen(row)}%; background-color: var(--color-brand-info);`"
                                            />
                                        </div>
                                        <span
                                            class="font-mono text-xs font-semibold"
                                            :style="`color: ${progressTone(persenTerpakai(row))};`"
                                        >
                                            {{ persenTerpakai(row).toFixed(1) }}%
                                        </span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <ChevronRight
                                        class="h-4 w-4 text-muted-foreground transition-transform"
                                        :class="{ 'rotate-90': expandedIds.has(row.id) }"
                                    />
                                </td>
                            </tr>

                            <!-- Expanded detail row -->
                            <tr v-if="expandedIds.has(row.id)" class="bg-secondary/30">
                                <td colspan="8" class="px-6 py-4">
                                    <!-- Loading -->
                                    <div v-if="detailLoading.has(row.id)" class="text-center text-sm text-muted-foreground">
                                        Memuat detail...
                                    </div>

                                    <!-- Empty detail -->
                                    <div
                                        v-else-if="detailData[row.id] && (!detailData[row.id].pengadaan || detailData[row.id].pengadaan.length === 0)"
                                        class="text-center text-sm text-muted-foreground"
                                    >
                                        Belum ada pengadaan terkait pos anggaran ini.
                                    </div>

                                    <!-- Detail list -->
                                    <div v-else-if="detailData[row.id]" class="space-y-2">
                                        <div class="text-eyebrow mb-2">Pengadaan Terkait</div>
                                        <div class="space-y-2">
                                            <div
                                                v-for="p in detailData[row.id].pengadaan"
                                                :key="p.id"
                                                class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-border bg-card px-4 py-3"
                                            >
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="font-mono text-xs font-bold">{{ p.no_pengadaan }}</span>
                                                        <span class="badge-status" :class="pengadaanStatusBadge(p.status)">
                                                            {{ pengadaanStatusLabel(p.status) }}
                                                        </span>
                                                        <span
                                                            v-if="p.pembayaran && p.pembayaran.status === 'lunas'"
                                                            class="badge-status badge-success"
                                                        >
                                                            ✓ Lunas
                                                        </span>
                                                    </div>
                                                    <div class="mt-1 text-sm font-medium">{{ p.usulan?.judul ?? '—' }}</div>
                                                    <div class="mt-0.5 text-xs text-muted-foreground">
                                                        {{ p.penyedia?.nama ?? '—' }}
                                                        <span v-if="p.usulan?.pemohon"> · {{ p.usulan.pemohon.name }}</span>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="text-eyebrow">Nilai Kontrak</div>
                                                    <div class="font-mono text-sm font-semibold">{{ formatRupiah(p.nilai_kontrak) }}</div>
                                                    <div
                                                        v-if="p.pembayaran && p.pembayaran.status === 'lunas'"
                                                        class="text-xs"
                                                        style="color: var(--color-brand-success);"
                                                    >
                                                        Bayar: {{ formatRupiah(p.pembayaran.nilai_bayar) }}
                                                        <span v-if="p.pembayaran.tanggal_bayar">
                                                            ({{ formatDate(p.pembayaran.tanggal_bayar) }})
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Belanja Langsung untuk pos anggaran ini -->
                                    <div v-if="detailData[row.id]?.belanja_langsung?.length > 0" class="mt-4">
                                        <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                            Belanja Langsung ({{ detailData[row.id].belanja_langsung.length }} nota)
                                        </div>
                                        <table class="w-full text-xs">
                                            <thead>
                                                <tr class="border-b border-border text-left text-muted-foreground">
                                                    <th class="py-1.5 pr-3">Uraian</th>
                                                    <th class="py-1.5 pr-3">Jenis</th>
                                                    <th class="py-1.5 pr-3">Pembelanja</th>
                                                    <th class="py-1.5 pr-3">Tanggal</th>
                                                    <th class="py-1.5 text-right">Nominal</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-border">
                                                <tr v-for="bl in detailData[row.id].belanja_langsung" :key="bl.id"
                                                    class="hover:bg-muted/10">
                                                    <td class="py-1.5 pr-3">{{ bl.uraian }}</td>
                                                    <td class="py-1.5 pr-3">
                                                        <span class="rounded-full bg-secondary px-2 py-0.5 text-[10px]">
                                                            {{ bl.jenis_label }}
                                                        </span>
                                                    </td>
                                                    <td class="py-1.5 pr-3 text-muted-foreground">{{ bl.pembelanja }}</td>
                                                    <td class="py-1.5 pr-3 text-muted-foreground">
                                                        {{ bl.tanggal_dibayar
                                                            ? new Date(bl.tanggal_dibayar).toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric' })
                                                            : '—' }}
                                                    </td>
                                                    <td class="py-1.5 text-right font-mono font-semibold">
                                                        {{ new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', minimumFractionDigits:0 }).format(bl.nominal) }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="anggaran.last_page > 1"
                class="-mx-6 -mb-6 mt-6 flex items-center justify-between border-t border-border bg-secondary/20 px-6 py-3"
            >
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ anggaran.from }}–{{ anggaran.to }} dari {{ anggaran.total }}
                </div>
                <div class="flex gap-1">
                    <template v-for="(link, idx) in anggaran.links" :key="idx">
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
        </Section>

        <!-- Legend -->
        <div class="rounded-md border border-border bg-card p-4 text-sm">
            <div class="text-eyebrow mb-2">Keterangan</div>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <div class="flex items-start gap-2">
                    <span class="mt-0.5 inline-block h-3 w-3 shrink-0 rounded" style="background-color: var(--color-brand-info);" />
                    <div>
                        <div class="text-xs font-semibold">Komitmen</div>
                        <div class="text-xs text-muted-foreground">Kontrak sudah ditandatangani, pembayaran belum cair</div>
                    </div>
                </div>
                <div class="flex items-start gap-2">
                    <span class="mt-0.5 inline-block h-3 w-3 shrink-0 rounded" style="background-color: var(--color-brand-success);" />
                    <div>
                        <div class="text-xs font-semibold">Realisasi</div>
                        <div class="text-xs text-muted-foreground">Pembayaran sudah lunas dan masuk ke penyedia</div>
                    </div>
                </div>
                <div class="flex items-start gap-2">
                    <span class="mt-0.5 inline-block h-3 w-3 shrink-0 rounded bg-secondary ring-1 ring-border" />
                    <div>
                        <div class="text-xs font-semibold">Sisa</div>
                        <div class="text-xs text-muted-foreground">Pagu - Komitmen - Realisasi (anggaran masih tersedia)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>