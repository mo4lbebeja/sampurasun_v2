<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { Search, Plus, FileText } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import StatusBadge from '@/components/ev/StatusBadge.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

type Usulan = {
    id: number;
    no_usulan: string;
    judul: string;
    tanggal_usulan: string;
    total_estimasi: string;
    status: string;
    pemohon: {
        id: number;
        name: string;
        unit_kerja: { id: number; nama: string } | null;
    } | null;
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
        usulan?: Paginated<Usulan>;
        filters?: { status: string; search: string };
    }>(),
    {
        usulan: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            from: null,
            to: null,
            total: 0,
            links: [],
        }),
        filters: () => ({ status: '', search: '' }),
    },
);

const search = ref(props.filters.search);
const status = ref(props.filters.status);

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'draft', label: 'Draft' },
    { value: 'diajukan', label: 'Diajukan' },
    { value: 'disetujui', label: 'Disetujui' },
    { value: 'ditolak', label: 'Ditolak' },
    { value: 'dalam_pengadaan', label: 'Dalam Pengadaan' },
    { value: 'dokumen', label: 'Dokumen' },
    { value: 'pembayaran', label: 'Pembayaran' },
    { value: 'evaluasi', label: 'Evaluasi' },
    { value: 'selesai', label: 'Selesai' },
    { value: 'dibatalkan', label: 'Dibatalkan' },
];

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

const reload = () => {
    router.get(
        '/usulan',
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const debouncedReload = debounce(reload, 400);

watch(search, () => debouncedReload());
watch(status, () => reload());

const isEmpty = computed(() => props.usulan.data.length === 0);
const hasFilters = computed(() => !!props.filters.search || !!props.filters.status);
</script>

<template>
    <Head title="Daftar Usulan" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <!-- Header -->
        <PageHeader
            title="Daftar Usulan Pengadaan"
            :subtitle="`${usulan.total} usulan terdaftar`"
            eyebrow="Pengadaan"
        >
            <template #actions>
                <Link href="/usulan/create">
                    <PrimaryButton variant="primary">
                        <Plus class="h-4 w-4" />
                        Buat Usulan
                    </PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <!-- Filter bar -->
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    v-model="search"
                    type="search"
                    placeholder="Cari nomor atau judul usulan..."
                    class="h-11 w-full rounded-md border border-input bg-card py-2 pl-10 pr-3 text-sm placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                />
            </div>
            <select
                v-model="status"
                class="h-11 rounded-md border border-input bg-card px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary sm:w-56"
            >
                <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                </option>
            </select>
        </div>

        <!-- Empty state -->
        <EmptyState
            v-if="isEmpty"
            :title="hasFilters ? 'Tidak ada hasil yang cocok' : 'Belum ada usulan'"
            :description="
                hasFilters
                    ? 'Coba ubah kata kunci atau filter status.'
                    : 'Mulai dengan membuat usulan pengadaan baru.'
            "
            :icon="FileText"
        >
            <template v-if="!hasFilters" #action>
                <Link href="/usulan/create">
                    <PrimaryButton variant="primary">
                        <Plus class="h-4 w-4" />
                        Buat Usulan Pertama
                    </PrimaryButton>
                </Link>
            </template>
        </EmptyState>

        <!-- Tabel -->
        <Section v-else>
            <div class="-mx-6 -my-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-secondary/40 text-left text-[10px] uppercase tracking-widest text-muted-foreground">
                            <th class="px-6 py-3 font-semibold">No. Usulan</th>
                            <th class="px-6 py-3 font-semibold">Judul</th>
                            <th class="px-6 py-3 font-semibold">Pemohon</th>
                            <th class="px-6 py-3 font-semibold">Tanggal</th>
                            <th class="px-6 py-3 text-right font-semibold">Estimasi</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in usulan.data"
                            :key="row.id"
                            class="cursor-pointer transition hover:bg-muted/30"
                            @click="$inertia.visit(`/usulan/${row.id}`)"
                        >
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-xs text-foreground">
                                {{ row.no_usulan }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-foreground">{{ row.judul }}</div>
                                <div v-if="row.pemohon?.unit_kerja" class="mt-0.5 text-xs text-muted-foreground">
                                    {{ row.pemohon.unit_kerja.nama }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">
                                {{ row.pemohon?.name ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">
                                {{ formatDate(row.tanggal_usulan) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right font-mono text-xs">
                                {{ formatRupiah(row.total_estimasi) }}
                            </td>
                            <td class="px-6 py-4">
                                <StatusBadge :status="row.status" />
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <Link
                                    :href="`/usulan/${row.id}`"
                                    class="text-xs font-semibold text-primary hover:underline"
                                    @click.stop
                                >
                                    Detail →
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="usulan.last_page > 1"
                class="-mx-6 -mb-6 mt-6 flex items-center justify-between border-t border-border bg-secondary/20 px-6 py-3"
            >
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ usulan.from }}–{{ usulan.to }} dari {{ usulan.total }}
                </div>
                <div class="flex gap-1">
                    <template v-for="(link, idx) in usulan.links" :key="idx">
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