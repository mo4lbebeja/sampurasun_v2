<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import {
    Search,
    ArrowLeft,
    FileCheck,
    Inbox,
    FileSpreadsheet,
    CheckCircle2,
    Clock,
    AlertCircle,
    ChevronDown,
} from 'lucide-vue-next';

import DetailModal from './_DetailModal.vue';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

type Dokumen = {
    id: number;
    no_bast: string | null;
    tanggal_bast: string | null;
    is_complete: boolean;
    completed_at: string | null;
    updated_at: string;
    pengadaan: {
        id: number;
        no_pengadaan: string;
        no_kontrak: string | null;
        nilai_kontrak: string;
        metode: string;
        penyedia: { id: number; nama: string; jenis_badan: string } | null;
        usulan: {
            id: number;
            no_usulan: string;
            judul: string;
            status: string;
            pemohon: {
                id: number;
                name: string;
                unit_kerja: { id: number; nama: string } | null;
            } | null;
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
    selesai: number;
    proses: number;
    belum: number;
    total_nilai: string | number;
};

type PenyediaOption = { id: number; nama: string };

const props = withDefaults(
    defineProps<{
        dokumen?: Paginated<Dokumen>;
        stats?: Stats;
        tahunOptions?: number[];
        penyediaOptions?: PenyediaOption[];
        filters?: {
            search: string;
            tahun: string | number;
            bulan: string | number;
            penyedia_id: string | number;
            status: string;
        };
    }>(),
    {
        dokumen: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            from: null,
            to: null,
            total: 0,
            links: [],
        }),
        stats: () => ({ total: 0, selesai: 0, proses: 0, belum: 0, total_nilai: 0 }),
        tahunOptions: () => [],
        penyediaOptions: () => [],
        filters: () => ({ search: '', tahun: '', bulan: '', penyedia_id: '', status: '' }),
    },
);

// Reactive filters
const search = ref(props.filters.search);
const tahun = ref(props.filters.tahun);
const bulan = ref(props.filters.bulan);
const penyediaId = ref(props.filters.penyedia_id);
const status = ref(props.filters.status);

const reload = () => {
    router.get(
        '/dokumen/rekap',
        {
            search: search.value || undefined,
            tahun: tahun.value || undefined,
            bulan: bulan.value || undefined,
            penyedia_id: penyediaId.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const debouncedReload = debounce(reload, 400);

watch(search, () => debouncedReload());
watch([tahun, bulan, penyediaId, status], () => reload());

const resetFilters = () => {
    search.value = '';
    tahun.value = '';
    bulan.value = '';
    penyediaId.value = '';
    status.value = '';
    reload();
};

const hasActiveFilters = computed(() =>
    !!props.filters.search
    || !!props.filters.tahun
    || !!props.filters.bulan
    || !!props.filters.penyedia_id
    || !!props.filters.status,
);

const isEmpty = computed(() => props.dokumen.data.length === 0);

// Format helpers
const formatRupiah = (val: string | number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(val) || 0);

const formatRupiahCompact = (val: string | number) => formatRupiah(val);

const formatDate = (val: string | null) => {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const formatMetode = (val: string) =>
    val.split('_').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');

// Status mapping
const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'belum', label: 'Belum Dimulai' },
    { value: 'proses', label: 'Dalam Proses' },
    { value: 'selesai', label: 'Selesai' },
];

const bulanOptions = [
    { value: '', label: 'Semua Bulan' },
    { value: '1', label: 'Januari' },
    { value: '2', label: 'Februari' },
    { value: '3', label: 'Maret' },
    { value: '4', label: 'April' },
    { value: '5', label: 'Mei' },
    { value: '6', label: 'Juni' },
    { value: '7', label: 'Juli' },
    { value: '8', label: 'Agustus' },
    { value: '9', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' },
];

const dokumenStatus = (d: Dokumen): { label: string; variant: string } => {
    if (d.is_complete) return { label: 'Selesai', variant: 'badge-success' };
    if (d.no_bast) return { label: 'Dalam Proses', variant: 'badge-info' };
    return { label: 'Belum Dimulai', variant: 'badge-warning' };
};

// Status usulan label (untuk konteks)
const usulanStatusLabel: Record<string, string> = {
    dokumen: 'Tahap Dokumen',
    pembayaran: 'Tahap Pembayaran',
    evaluasi: 'Tahap Evaluasi',
    selesai: 'Selesai',
};
// Collapsible filter — default open kalau ada filter aktif, tutup kalau tidak
const filterOpen = ref(
    !!props.filters.search
    || !!props.filters.tahun
    || !!props.filters.bulan
    || !!props.filters.penyedia_id
    || !!props.filters.status
);

// Hitung jumlah filter yang aktif (untuk badge di header)
const activeFilterCount = computed(() => {
    let count = 0;
    if (props.filters.search) count++;
    if (props.filters.tahun) count++;
    if (props.filters.bulan) count++;
    if (props.filters.penyedia_id) count++;
    if (props.filters.status) count++;
    return count;
});

// Modal state
const modalOpen = ref(false);
const selectedPengadaanId = ref<number | null>(null);

const openDetailModal = (pengadaanId: number) => {
    selectedPengadaanId.value = pengadaanId;
    modalOpen.value = true;
};

const onModalSaved = () => {
    // Refresh tabel data setelah save
    router.reload({ only: ['dokumen', 'stats'] });
};

// Persen completion
const persenSelesai = computed(() => {
    if (props.stats.total === 0) return 0;
    return Math.round((props.stats.selesai / props.stats.total) * 100);
});
</script>

<template>
    <Head title="Rekap Dokumen UPBJ" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <!-- Header dengan back button -->
        <div class="flex items-start gap-3">
            <Link
                href="/dokumen"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-card transition hover:bg-muted"
            >
                <ArrowLeft class="h-4 w-4" />
            </Link>
            <div class="flex-1">
                <PageHeader
                    title="Rekap Dokumen UPBJ"
                    :subtitle="`${stats.total} dokumen tercatat`"
                    eyebrow="Laporan & History"
                />
            </div>
        </div>

        <!-- Stats banner: 4 cards -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <Section>
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <FileSpreadsheet class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Total Dokumen</div>
                        <div class="font-display text-2xl font-bold">{{ stats.total }}</div>
                        <div class="text-xs text-muted-foreground">
                            {{ formatRupiahCompact(stats.total_nilai) }}
                        </div>
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
                        <div class="text-eyebrow">Selesai</div>
                        <div class="font-display text-2xl font-bold">{{ stats.selesai }}</div>
                        <div class="text-xs text-muted-foreground">
                            {{ persenSelesai }}% dari total
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
                        <div class="text-eyebrow">Dalam Proses</div>
                        <div class="font-display text-2xl font-bold">{{ stats.proses }}</div>
                        <div class="text-xs text-muted-foreground">Sedang dikerjakan</div>
                    </div>
                </div>
            </Section>
            <Section>
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-warning);"
                    >
                        <AlertCircle class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Belum Dimulai</div>
                        <div class="font-display text-2xl font-bold">{{ stats.belum }}</div>
                        <div class="text-xs text-muted-foreground">Menunggu pengurusan</div>
                    </div>
                </div>
            </Section>
        </div>

        <!-- Filter bar -->
        <!-- Filter bar — collapsible -->
        <div class="rounded-lg border border-border bg-card">
            <!-- Header (clickable to toggle) -->
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

            <!-- Body (filter inputs) -->
            <div
                v-show="filterOpen"
                class="space-y-3 border-t border-border px-6 py-5"
            >
                <!-- Reset button -->
                <div v-if="hasActiveFilters" class="flex justify-end">
                    <button
                        type="button"
                        class="text-xs font-semibold text-primary hover:underline"
                        @click="resetFilters"
                    >
                        Reset Filter
                    </button>
                </div>

                <!-- Search -->
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari nomor BAST, nomor pengadaan, atau judul usulan..."
                        class="h-11 w-full rounded-md border border-input bg-background py-2 pl-10 pr-3 text-sm placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>

                <!-- Filter selects -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <select
                        v-model="tahun"
                        class="h-11 rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option value="">Semua Tahun</option>
                        <option v-for="t in tahunOptions" :key="t" :value="t">{{ t }}</option>
                    </select>

                    <select
                        v-model="bulan"
                        class="h-11 rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option v-for="opt in bulanOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>

                    <select
                        v-model="penyediaId"
                        class="h-11 rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option value="">Semua Penyedia</option>
                        <option v-for="p in penyediaOptions" :key="p.id" :value="p.id">
                            {{ p.nama }}
                        </option>
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
            :title="hasActiveFilters ? 'Tidak ada hasil yang cocok' : 'Belum ada rekap dokumen'"
            :description="
                hasActiveFilters
                    ? 'Coba ubah kombinasi filter atau reset filter untuk melihat semua data.'
                    : 'Dokumen UPBJ akan muncul di sini setelah ada pengadaan yang masuk tahap dokumen.'
            "
            :icon="Inbox"
        >
            <template v-if="hasActiveFilters" #action>
                <PrimaryButton variant="primary" @click="resetFilters">
                    Reset Filter
                </PrimaryButton>
            </template>
        </EmptyState>

        <!-- Tabel rekap -->
        <Section v-else title="Daftar Dokumen" eyebrow="Detail">
            <div class="-mx-6 -my-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-secondary/40 text-left text-[10px] uppercase tracking-widest text-muted-foreground">
                            <th class="px-6 py-3 font-semibold">No. Pengadaan</th>
                            <th class="px-6 py-3 font-semibold">Pengadaan & Penyedia</th>
                            <th class="px-6 py-3 font-semibold">No. BAST</th>
                            <th class="px-6 py-3 font-semibold">Tgl BAST</th>
                            <th class="px-6 py-3 text-right font-semibold">Nilai</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3 font-semibold">Petugas</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="d in dokumen.data"
                            :key="d.id"
                            class="cursor-pointer transition hover:bg-muted/30"
                            @click="d.pengadaan && openDetailModal(d.pengadaan.id)"
                        >
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="font-mono text-xs font-bold">
                                    {{ d.pengadaan?.no_pengadaan ?? '—' }}
                                </div>
                                <div
                                    v-if="d.pengadaan?.usulan?.status"
                                    class="mt-0.5 text-[10px] uppercase tracking-wider text-muted-foreground"
                                >
                                    {{ usulanStatusLabel[d.pengadaan.usulan.status] ?? d.pengadaan.usulan.status }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium">{{ d.pengadaan?.usulan?.judul ?? '—' }}</div>
                                <div class="mt-0.5 flex flex-wrap gap-x-2 text-xs text-muted-foreground">
                                    <span v-if="d.pengadaan?.penyedia">{{ d.pengadaan.penyedia.nama }}</span>
                                    <span v-if="d.pengadaan?.metode">· {{ formatMetode(d.pengadaan.metode) }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-xs">
                                {{ d.no_bast ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">
                                {{ formatDate(d.tanggal_bast) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right font-mono text-xs">
                                {{ formatRupiahCompact(d.pengadaan?.nilai_kontrak ?? 0) }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge-status" :class="dokumenStatus(d).variant">
                                    {{ dokumenStatus(d).label }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">
                                {{ d.petugas?.name ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <button
                                    v-if="d.pengadaan"
                                    type="button"
                                    class="text-xs font-semibold text-primary hover:underline"
                                    @click.stop="openDetailModal(d.pengadaan.id)"
                                >
                                    Lihat →
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="dokumen.last_page > 1"
                class="-mx-6 -mb-6 mt-6 flex items-center justify-between border-t border-border bg-secondary/20 px-6 py-3"
            >
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ dokumen.from }}–{{ dokumen.to }} dari {{ dokumen.total }}
                </div>
                <div class="flex gap-1">
                    <template v-for="(link, idx) in dokumen.links" :key="idx">
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
        
        <!-- Detail modal -->
        <DetailModal
            :pengadaan-id="selectedPengadaanId"
            v-model:open="modalOpen"
            @saved="onModalSaved"
        />
    </div>
</template>