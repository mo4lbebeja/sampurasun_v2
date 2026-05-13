<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import {
    ChevronDown,
    ChevronRight,
    Pencil,
    Plus,
    Search,
    Wallet,
} from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

type DpaAnggaran = {
    id: number;
    tahun_anggaran: number;
    no_dpa: string;
    tanggal_dpa?: string | null;
    nama_dpa?: string | null;
};

type SubKegiatanOption = {
    id: number;
    dpa_anggaran_id: number;
    kode_sub_kegiatan: string;
    nama_kegiatan: string;
    tahun_anggaran: number;
    dpa_anggaran?: DpaAnggaran | null;
};

type Anggaran = {
    id: number;
    tahun: number;
    sub_kegiatan_id: number | null;
    kode_rekening: string;
    nama_rekening: string;
    uraian: string | null;
    pagu: string | number;
    terpakai: string | number;
    sisa: string | number;
    is_active: boolean;
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
        subKegiatanOptions?: SubKegiatanOption[];
        selectedSubKegiatanId?: number | null;
        selectedSubKegiatan?: SubKegiatanOption | null;
        stats?: {
            total_pagu: string | number;
            total_terpakai: string | number;
            total_sisa: string | number;
            total_rekening: number;
        };
        filters?: {
            search: string;
            sub_kegiatan_id: number | null;
        };
        tahunAnggaran?: number;
    }>(),
    {
        anggaran: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            from: null,
            to: null,
            total: 0,
            links: [],
        }),
        subKegiatanOptions: () => [],
        selectedSubKegiatanId: null,
        selectedSubKegiatan: null,
        stats: () => ({
            total_pagu: 0,
            total_terpakai: 0,
            total_sisa: 0,
            total_rekening: 0,
        }),
        filters: () => ({
            search: '',
            sub_kegiatan_id: null,
        }),
        tahunAnggaran: new Date().getFullYear(),
    },
);

const search = ref(props.filters.search);

const selectedSubKegiatanId = ref<number | null>(
    props.filters.sub_kegiatan_id ?? props.selectedSubKegiatanId ?? null,
);

const expandedRows = ref<number[]>([]);

const reload = () => {
    router.get(
        '/anggaran',
        {
            search: search.value || undefined,
            sub_kegiatan_id: selectedSubKegiatanId.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const debouncedReload = debounce(reload, 400);

watch(search, () => debouncedReload());

watch(selectedSubKegiatanId, () => reload());

const formatRupiah = (val: string | number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(val) || 0);

const totalPagu = computed(() => Number(props.stats?.total_pagu ?? 0));

const totalTerpakai = computed(() => Number(props.stats?.total_terpakai ?? 0));

const totalSisa = computed(() => Number(props.stats?.total_sisa ?? 0));

const selectedSubKegiatan = computed(() => props.selectedSubKegiatan);

const selectedDpaLabel = computed(() => {
    const dpa = selectedSubKegiatan.value?.dpa_anggaran;

    if (!dpa) return '-';

    return dpa.no_dpa || dpa.nama_dpa || '-';
});

const isExpanded = (id: number) => expandedRows.value.includes(id);

const toggleRow = (id: number) => {
    if (isExpanded(id)) {
        expandedRows.value = expandedRows.value.filter((rowId) => rowId !== id);
        return;
    }

    expandedRows.value.push(id);
};

const shortText = (text: string | null, maxLength = 38) => {
    if (!text) return '-';

    return text.length > maxLength ? `${text.slice(0, maxLength)}...` : text;
};

const getStatus = (row: Anggaran) => {
    const pagu = Number(row.pagu) || 0;
    const sisa = Number(row.sisa) || 0;

    if (!row.is_active) {
        return {
            label: 'Nonaktif',
            class: 'bg-muted text-muted-foreground',
        };
    }

    if (pagu <= 0 || sisa <= 0) {
        return {
            label: 'Penuh',
            class: 'bg-red-100 text-red-700',
        };
    }

    const percentage = (sisa / pagu) * 100;

    if (percentage <= 20) {
        return {
            label: 'Hampir Penuh',
            class: 'bg-amber-100 text-amber-800',
        };
    }

    return {
        label: 'Tersedia',
        class: 'bg-green-100 text-green-700',
    };
};

const isEmpty = computed(() => props.anggaran.data.length === 0);

const hasFilters = computed(() => !!props.filters.search);
</script>

<template>
    <Head title="Anggaran" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <PageHeader
            title="Anggaran"
            :subtitle="`${anggaran.total} pos anggaran pada tahun anggaran ${tahunAnggaran}`"
            eyebrow="Master Data"
        >
            <template #actions>
                <Link
                    :href="`/anggaran/create?sub_kegiatan_id=${selectedSubKegiatanId || ''}`"
                >
                    <PrimaryButton
                        variant="primary"
                        :disabled="!selectedSubKegiatanId"
                    >
                        <Plus class="h-4 w-4" />
                        Tambah Anggaran
                    </PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <!-- Langkah 1: Pilih Sub Kegiatan -->
        <Section>
            <div class="space-y-4">
                <div>
                    <div class="text-eyebrow">Langkah 1</div>

                    <h2 class="mt-1 text-lg font-semibold">
                        Pilih Sub Kegiatan
                    </h2>
                </div>

                <select
                    v-model.number="selectedSubKegiatanId"
                    class="h-11 w-full max-w-4xl rounded-md border border-input bg-card px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
                    <option
                        v-if="subKegiatanOptions.length === 0"
                        :value="null"
                    >
                        Belum ada sub kegiatan pada tahun anggaran ini
                    </option>

                    <option
                        v-for="subKegiatan in subKegiatanOptions"
                        :key="subKegiatan.id"
                        :value="subKegiatan.id"
                    >
                        {{ subKegiatan.kode_sub_kegiatan }} — {{ subKegiatan.nama_kegiatan }}
                    </option>
                </select>

                <div
                    class="grid gap-4 rounded-lg border border-border bg-background p-5 md:grid-cols-[150px_minmax(0,1fr)_220px_220px]"
                >
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Tahun Anggaran
                        </div>

                        <div class="mt-2 text-sm font-semibold text-foreground">
                            {{ tahunAnggaran }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Sub Kegiatan
                        </div>

                        <div class="mt-2 text-sm font-semibold leading-snug text-foreground">
                            <span v-if="selectedSubKegiatan">
                                {{ selectedSubKegiatan.kode_sub_kegiatan }} —
                                {{ selectedSubKegiatan.nama_kegiatan }}
                            </span>

                            <span v-else>-</span>
                        </div>

                        <div class="mt-1 text-xs text-muted-foreground">
                            {{ selectedDpaLabel }}
                        </div>
                    </div>

                    <div class="md:text-right">
                        <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Pagu Total
                        </div>

                        <div class="mt-2 text-sm font-semibold text-foreground">
                            {{ formatRupiah(totalPagu) }}
                        </div>

                        <div class="mt-1 text-xs text-muted-foreground">
                            Terpakai:
                            <span>
                                {{ formatRupiah(totalTerpakai) }}
                            </span>
                        </div>
                    </div>

                    <div class="md:text-right">
                        <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Sisa Pagu
                        </div>

                        <div class="mt-2 text-sm font-semibold text-primary">
                            {{ formatRupiah(totalSisa) }}
                        </div>

                        <div class="mt-1 text-xs text-muted-foreground">
                            {{ stats.total_rekening }} rekening terdaftar
                        </div>
                    </div>
                </div>
            </div>
        </Section>

        <!-- Filter search -->
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />

                <input
                    v-model="search"
                    type="search"
                    placeholder="Cari kode, nama rekening, atau uraian..."
                    class="h-11 w-full rounded-md border border-input bg-card py-2 pl-10 pr-3 text-sm placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                />
            </div>
        </div>

        <!-- Empty state -->
        <EmptyState
            v-if="isEmpty"
            :title="hasFilters ? 'Tidak ada hasil yang cocok' : 'Belum ada anggaran'"
            :description="
                hasFilters
                    ? 'Coba ubah kata kunci pencarian.'
                    : 'Tambahkan pos anggaran untuk mulai mencatat alokasi pengadaan.'
            "
            :icon="Wallet"
        >
            <template
                v-if="!hasFilters"
                #action
            >
                <Link href="/anggaran/create">
                    <PrimaryButton variant="primary">
                        <Plus class="h-4 w-4" />
                        Tambah Anggaran Pertama
                    </PrimaryButton>
                </Link>
            </template>
        </EmptyState>

        <!-- Tabel -->
        <Section v-else>
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="text-eyebrow">Langkah 2</div>

                    <h2 class="mt-1 text-lg font-semibold">
                        Nilai Anggaran
                    </h2>
                </div>

                <div class="text-sm font-medium text-muted-foreground">
                    {{ stats.total_rekening }} rekening terdaftar
                </div>
            </div>

            <div class="-mx-6 -mb-6 overflow-x-auto">
                <table class="w-full min-w-[980px] text-sm">
                    <thead>
                        <tr class="border-b border-border bg-secondary/40 text-left text-[10px] uppercase tracking-widest text-muted-foreground">
                            <th class="w-12 px-4 py-3"></th>
                            <th class="px-4 py-3 font-semibold">Kode Rekening</th>
                            <th class="px-4 py-3 font-semibold">Nama Rekening</th>
                            <th class="px-4 py-3 text-right font-semibold">Pagu</th>
                            <th class="px-4 py-3 text-right font-semibold">Terpakai</th>
                            <th class="px-4 py-3 text-right font-semibold">Sisa</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 text-right"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border">
                        <template
                            v-for="row in anggaran.data"
                            :key="row.id"
                        >
                            <tr class="transition hover:bg-muted/30">
                                <td class="px-4 py-4">
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md hover:bg-muted"
                                        @click="toggleRow(row.id)"
                                    >
                                        <ChevronDown
                                            v-if="isExpanded(row.id)"
                                            class="h-4 w-4 text-muted-foreground"
                                        />

                                        <ChevronRight
                                            v-else
                                            class="h-4 w-4 text-muted-foreground"
                                        />
                                    </button>
                                </td>

                                <td class="whitespace-nowrap px-4 py-4 align-top font-mono text-xs font-bold">
                                    {{ row.kode_rekening }}
                                </td>

                                <td class="px-4 py-4 align-top">
                                    <div class="font-medium">
                                        {{ shortText(row.nama_rekening) }}
                                    </div>

                                    <div
                                        v-if="row.uraian"
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        Klik panah untuk melihat uraian.
                                    </div>
                                </td>

                                <td class="whitespace-nowrap px-4 py-4 text-right align-top font-mono text-xs">
                                    {{ formatRupiah(row.pagu) }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-4 text-right align-top font-mono text-xs">
                                    {{ formatRupiah(row.terpakai) }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-4 text-right align-top font-mono text-xs font-semibold text-primary">
                                    {{ formatRupiah(row.sisa) }}
                                </td>

                                <td class="px-4 py-4 align-top">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="getStatus(row).class"
                                    >
                                        {{ getStatus(row).label }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-right align-top">
                                    <Link
                                        :href="`/anggaran/${row.id}/edit`"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-border bg-background transition hover:bg-muted"
                                        title="Edit anggaran"
                                    >
                                        <Pencil class="h-3.5 w-3.5" />
                                    </Link>
                                </td>
                            </tr>

                            <tr
                                v-if="isExpanded(row.id)"
                                class="bg-muted/20"
                            >
                                <td></td>

                                <td
                                    colspan="7"
                                    class="px-4 py-4"
                                >
                                    <div class="max-w-5xl">
                                        <div class="text-eyebrow">Uraian</div>

                                        <p class="mt-2 leading-relaxed">
                                            {{ row.uraian || 'Tidak ada uraian.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>

                    <tfoot class="border-t border-border bg-secondary/30">
                        <tr class="font-semibold">
                            <td
                                colspan="3"
                                class="px-4 py-4 text-right"
                            >
                                Total
                            </td>

                            <td class="px-4 py-4 text-right font-mono text-xs">
                                {{ formatRupiah(totalPagu) }}
                            </td>

                            <td class="px-4 py-4 text-right font-mono text-xs">
                                {{ formatRupiah(totalTerpakai) }}
                            </td>

                            <td class="px-4 py-4 text-right font-mono text-xs">
                                {{ formatRupiah(totalSisa) }}
                            </td>

                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="anggaran.last_page > 1"
                class="mt-6 flex flex-wrap gap-2"
            >
                <Link
                    v-for="link in anggaran.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="rounded-md border px-3 py-1.5 text-sm"
                    :class="[
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border bg-background hover:bg-muted',
                        !link.url ? 'pointer-events-none opacity-50' : '',
                    ]"
                    v-html="link.label"
                />
            </div>
        </Section>
    </div>
</template>