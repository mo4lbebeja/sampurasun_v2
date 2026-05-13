<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import {
    Search, ArrowLeft, Wallet, Inbox, ChevronDown,
    CheckCircle2, Clock, AlertCircle, Receipt,
} from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

type Pembayaran = {
    id: number;
    no_spm: string | null;
    no_sp2d: string | null;
    tanggal_bayar: string | null;
    metode_bayar: string;
    nilai_bayar: string;
    pajak_pph: string;
    pajak_ppn: string;
    nilai_bersih: string;
    status: string;
    updated_at: string;
    pengadaan: {
        id: number;
        no_pengadaan: string;
        no_kontrak: string | null;
        nilai_kontrak: string;
        metode: string;
        penyedia: {
            id: number;
            nama: string;
            jenis_badan: string;
            nama_bank: string | null;
            rekening_bank: string | null;
        } | null;
        usulan: {
            id: number;
            no_usulan: string;
            judul: string;
            status: string;
        } | null;
    } | null;
    petugas: { id: number; name: string } | null;
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

type Stats = {
    total: number;
    lunas: number;
    diproses: number;
    pending: number;
    total_lunas: string | number;
    total_pending: string | number;
    total_pajak: string | number;
};

const props = withDefaults(
    defineProps<{
        pembayaran?: Paginated<Pembayaran>;
        stats?: Stats;
        tahunOptions?: number[];
        penyediaOptions?: { id: number; nama: string }[];
        filters?: {
            search: string;
            tahun: string | number;
            bulan: string | number;
            penyedia_id: string | number;
            status: string;
            metode_bayar: string;
        };
    }>(),
    {
        pembayaran: () => ({
            data: [], current_page: 1, last_page: 1, from: null, to: null, total: 0, links: [],
        }),
        stats: () => ({
            total: 0, lunas: 0, diproses: 0, pending: 0,
            total_lunas: 0, total_pending: 0, total_pajak: 0,
        }),
        tahunOptions: () => [],
        penyediaOptions: () => [],
        filters: () => ({
            search: '', tahun: '', bulan: '', penyedia_id: '', status: '', metode_bayar: '',
        }),
    },
);

// Filter state
const search = ref(props.filters.search);
const tahun = ref(props.filters.tahun);
const bulan = ref(props.filters.bulan);
const penyediaId = ref(props.filters.penyedia_id);
const status = ref(props.filters.status);
const metodeBayar = ref(props.filters.metode_bayar);

const filterOpen = ref(
    !!props.filters.search || !!props.filters.tahun || !!props.filters.bulan
    || !!props.filters.penyedia_id || !!props.filters.status || !!props.filters.metode_bayar,
);

const activeFilterCount = computed(() => {
    let c = 0;
    if (props.filters.search) c++;
    if (props.filters.tahun) c++;
    if (props.filters.bulan) c++;
    if (props.filters.penyedia_id) c++;
    if (props.filters.status) c++;
    if (props.filters.metode_bayar) c++;
    return c;
});

const hasActiveFilters = computed(() => activeFilterCount.value > 0);

const reload = () => {
    router.get('/pembayaran/rekap', {
        search: search.value || undefined,
        tahun: tahun.value || undefined,
        bulan: bulan.value || undefined,
        penyedia_id: penyediaId.value || undefined,
        status: status.value || undefined,
        metode_bayar: metodeBayar.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const debouncedReload = debounce(reload, 400);
watch(search, () => debouncedReload());
watch([tahun, bulan, penyediaId, status, metodeBayar], () => reload());

const resetFilters = () => {
    search.value = '';
    tahun.value = '';
    bulan.value = '';
    penyediaId.value = '';
    status.value = '';
    metodeBayar.value = '';
    reload();
};

// Format helpers
const formatRupiah = (val: any) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0,
    }).format(Number(val) || 0);

const formatRupiahCompact = (val: any) => {
    const num = Number(val);
    if (num >= 1_000_000_000) return `Rp ${(num / 1_000_000_000).toFixed(1)} M`;
    if (num >= 1_000_000) return `Rp ${(num / 1_000_000).toFixed(1)} jt`;
    if (num >= 1_000) return `Rp ${(num / 1_000).toFixed(0)} rb`;
    return formatRupiah(num);
};

const formatDate = (val: string | null) => {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
};

const formatMetodeBayar = (val: string) =>
    val.charAt(0).toUpperCase() + val.slice(1);

// Status mapping
const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'pending', label: 'Pending' },
    { value: 'diproses', label: 'Diproses' },
    { value: 'lunas', label: 'Lunas' },
];

const metodeBayarOptions = [
    { value: '', label: 'Semua Metode' },
    { value: 'transfer', label: 'Transfer Bank' },
    { value: 'cek', label: 'Cek' },
    { value: 'tunai', label: 'Tunai' },
    { value: 'giro', label: 'Giro' },
];

const bulanOptions = [
    { value: '', label: 'Semua Bulan' },
    { value: '1', label: 'Januari' }, { value: '2', label: 'Februari' },
    { value: '3', label: 'Maret' }, { value: '4', label: 'April' },
    { value: '5', label: 'Mei' }, { value: '6', label: 'Juni' },
    { value: '7', label: 'Juli' }, { value: '8', label: 'Agustus' },
    { value: '9', label: 'September' }, { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' }, { value: '12', label: 'Desember' },
];

const statusBadge = (s: string) => {
    if (s === 'lunas') return 'badge-success';
    if (s === 'diproses') return 'badge-info';
    if (s === 'pending') return 'badge-warning';
    return 'badge-muted';
};

const statusLabel = (s: string) => {
    if (s === 'lunas') return 'Lunas';
    if (s === 'diproses') return 'Diproses';
    if (s === 'pending') return 'Pending';
    return s;
};

const isEmpty = computed(() => props.pembayaran.data.length === 0);

const persenLunas = computed(() => {
    if (props.stats.total === 0) return 0;
    return Math.round((props.stats.lunas / props.stats.total) * 100);
});
</script>

<template>
    <Head title="Rekap Pembayaran" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <!-- Header -->
        <div class="flex items-start gap-3">
            <Link
                href="/pembayaran"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-card transition hover:bg-muted"
            >
                <ArrowLeft class="h-4 w-4" />
            </Link>
            <div class="flex-1">
                <PageHeader
                    title="Rekap Pembayaran"
                    :subtitle="`${stats.total} pembayaran tercatat`"
                    eyebrow="Laporan & History"
                />
            </div>
        </div>

        <!-- Stats banner: 4 cards -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <Section>
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <Wallet class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Total Lunas</div>
                        <div class="font-display text-xl font-bold">
                            {{ formatRupiahCompact(stats.total_lunas) }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ stats.lunas }} pembayaran ({{ persenLunas }}%)
                        </div>
                    </div>
                </div>
            </Section>
            <Section>
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-info);"
                    >
                        <Clock class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Pending Bayar</div>
                        <div class="font-display text-xl font-bold">
                            {{ formatRupiahCompact(stats.total_pending) }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ Number(stats.diproses) + Number(stats.pending) }} pembayaran
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
                        <Receipt class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Total Pajak</div>
                        <div class="font-display text-xl font-bold">
                            {{ formatRupiahCompact(stats.total_pajak) }}
                        </div>
                        <div class="text-xs text-muted-foreground">PPh + PPN dipotong</div>
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
                        <div class="text-eyebrow">Total Transaksi</div>
                        <div class="font-display text-xl font-bold">{{ stats.total }}</div>
                        <div class="text-xs text-muted-foreground">
                            {{ stats.lunas }} lunas, {{ Number(stats.diproses) + Number(stats.pending) }} pending
                        </div>
                    </div>
                </div>
            </Section>
        </div>

        <!-- Filter (collapsible) -->
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
                        placeholder="Cari nomor SPM, SP2D, no pengadaan, atau judul usulan..."
                        class="h-11 w-full rounded-md border border-input bg-background py-2 pl-10 pr-3 text-sm placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <select v-model="tahun" class="h-11 rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="">Semua Tahun</option>
                        <option v-for="t in tahunOptions" :key="t" :value="t">{{ t }}</option>
                    </select>
                    <select v-model="bulan" class="h-11 rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option v-for="opt in bulanOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <select v-model="penyediaId" class="h-11 rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option value="">Semua Penyedia</option>
                        <option v-for="p in penyediaOptions" :key="p.id" :value="p.id">{{ p.nama }}</option>
                    </select>
                    <select v-model="status" class="h-11 rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <select v-model="metodeBayar" class="h-11 rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <option v-for="opt in metodeBayarOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <EmptyState
            v-if="isEmpty"
            :title="hasActiveFilters ? 'Tidak ada hasil yang cocok' : 'Belum ada data pembayaran'"
            :description="hasActiveFilters
                ? 'Coba ubah filter atau reset untuk lihat semua data.'
                : 'Pembayaran akan muncul di sini setelah ada pengadaan yang masuk tahap pembayaran.'
            "
            :icon="Inbox"
        />

        <!-- Tabel rekap -->
        <Section v-else title="Daftar Pembayaran" eyebrow="Detail">
            <div class="-mx-6 -my-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-secondary/40 text-left text-[10px] uppercase tracking-widest text-muted-foreground">
                            <th class="px-6 py-3 font-semibold">No. Pengadaan / SPM-SP2D</th>
                            <th class="px-6 py-3 font-semibold">Penyedia & Judul</th>
                            <th class="px-6 py-3 font-semibold">Tanggal Bayar</th>
                            <th class="px-6 py-3 text-right font-semibold">Nilai Bersih</th>
                            <th class="px-6 py-3 text-right font-semibold">Pajak</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="p in pembayaran.data"
                            :key="p.id"
                            class="cursor-pointer transition hover:bg-muted/30"
                            @click="p.pengadaan && $inertia.visit(`/pembayaran/${p.pengadaan.id}`)"
                        >
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="font-mono text-xs font-bold">
                                    {{ p.pengadaan?.no_pengadaan ?? '—' }}
                                </div>
                                <div v-if="p.no_spm" class="mt-0.5 font-mono text-[10px] text-muted-foreground">
                                    SPM: {{ p.no_spm }}
                                </div>
                                <div v-if="p.no_sp2d" class="font-mono text-[10px] text-muted-foreground">
                                    SP2D: {{ p.no_sp2d }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium">
                                    {{ p.pengadaan?.penyedia?.nama ?? '—' }}
                                </div>
                                <div class="mt-0.5 text-xs text-muted-foreground">
                                    {{ p.pengadaan?.usulan?.judul ?? '—' }}
                                </div>
                                <div
                                    v-if="p.pengadaan?.penyedia?.nama_bank"
                                    class="mt-0.5 text-[10px] text-muted-foreground"
                                >
                                    {{ p.pengadaan.penyedia.nama_bank }}
                                    <span v-if="p.pengadaan.penyedia.rekening_bank" class="font-mono">
                                        · {{ p.pengadaan.penyedia.rekening_bank }}
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">
                                {{ formatDate(p.tanggal_bayar) }}
                                <div class="text-[10px]">{{ formatMetodeBayar(p.metode_bayar) }}</div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <div class="font-mono text-sm font-bold text-primary">
                                    {{ formatRupiahCompact(p.nilai_bersih) }}
                                </div>
                                <div class="text-[10px] text-muted-foreground">
                                    Bruto: {{ formatRupiahCompact(p.nilai_bayar) }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <div class="font-mono text-xs">
                                    {{ formatRupiahCompact(Number(p.pajak_pph) + Number(p.pajak_ppn)) }}
                                </div>
                                <div class="text-[10px] text-muted-foreground">
                                    PPh: {{ formatRupiahCompact(p.pajak_pph) }}
                                </div>
                                <div class="text-[10px] text-muted-foreground">
                                    PPN: {{ formatRupiahCompact(p.pajak_ppn) }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge-status" :class="statusBadge(p.status)">
                                    {{ statusLabel(p.status) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <Link
                                    v-if="p.pengadaan"
                                    :href="`/pembayaran/${p.pengadaan.id}`"
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

            <!-- Pagination -->
            <div
                v-if="pembayaran.last_page > 1"
                class="-mx-6 -mb-6 mt-6 flex items-center justify-between border-t border-border bg-secondary/20 px-6 py-3"
            >
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ pembayaran.from }}–{{ pembayaran.to }} dari {{ pembayaran.total }}
                </div>
                <div class="flex gap-1">
                    <template v-for="(link, idx) in pembayaran.links" :key="idx">
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
    </div>
</template>