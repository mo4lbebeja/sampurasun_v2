<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { Search, Plus, Building2, Pencil } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

type Penyedia = {
    id: number;
    nama: string;
    jenis_badan: string;
    npwp: string | null;
    telepon: string | null;
    email: string | null;
    nama_pic: string | null;
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
        penyedia?: Paginated<Penyedia>;
        filters?: { search: string; jenis_badan: string };
    }>(),
    {
        penyedia: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            from: null,
            to: null,
            total: 0,
            links: [],
        }),
        filters: () => ({ search: '', jenis_badan: '' }),
    },
);

const search = ref(props.filters.search);
const jenisBadan = ref(props.filters.jenis_badan);

const jenisBadanOptions = [
    { value: '', label: 'Semua Jenis' },
    { value: 'PT', label: 'PT' },
    { value: 'CV', label: 'CV' },
    { value: 'UD', label: 'UD' },
    { value: 'Firma', label: 'Firma' },
    { value: 'Koperasi', label: 'Koperasi' },
    { value: 'Perorangan', label: 'Perorangan' },
];

const reload = () => {
    router.get(
        '/penyedia',
        {
            search: search.value || undefined,
            jenis_badan: jenisBadan.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const debouncedReload = debounce(reload, 400);

watch(search, () => debouncedReload());
watch(jenisBadan, () => reload());

const isEmpty = computed(() => props.penyedia.data.length === 0);
const hasFilters = computed(() => !!props.filters.search || !!props.filters.jenis_badan);
</script>

<template>
    <Head title="Master Penyedia" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <PageHeader
            title="Master Penyedia"
            :subtitle="`${penyedia.total} vendor terdaftar`"
            eyebrow="Master Data"
        >
            <template #actions>
                <Link href="/penyedia/create">
                    <PrimaryButton variant="primary">
                        <Plus class="h-4 w-4" />
                        Tambah Penyedia
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
                    placeholder="Cari nama, NPWP, atau email..."
                    class="h-11 w-full rounded-md border border-input bg-card py-2 pl-10 pr-3 text-sm placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                />
            </div>
            <select
                v-model="jenisBadan"
                class="h-11 rounded-md border border-input bg-card px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary sm:w-44"
            >
                <option v-for="opt in jenisBadanOptions" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                </option>
            </select>
        </div>

        <!-- Empty state -->
        <EmptyState
            v-if="isEmpty"
            :title="hasFilters ? 'Tidak ada hasil yang cocok' : 'Belum ada penyedia'"
            :description="
                hasFilters
                    ? 'Coba ubah kata kunci atau filter jenis badan.'
                    : 'Tambahkan vendor pertama untuk mulai proses pengadaan.'
            "
            :icon="Building2"
        >
            <template v-if="!hasFilters" #action>
                <Link href="/penyedia/create">
                    <PrimaryButton variant="primary">
                        <Plus class="h-4 w-4" />
                        Tambah Penyedia Pertama
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
                            <th class="px-6 py-3 font-semibold">Nama Penyedia</th>
                            <th class="px-6 py-3 font-semibold">Jenis</th>
                            <th class="px-6 py-3 font-semibold">NPWP</th>
                            <th class="px-6 py-3 font-semibold">PIC</th>
                            <th class="px-6 py-3 font-semibold">Kontak</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in penyedia.data"
                            :key="row.id"
                            class="cursor-pointer transition hover:bg-muted/30"
                            @click="$inertia.visit(`/penyedia/${row.id}/edit`)"
                        >
                            <td class="px-6 py-4 font-medium">{{ row.nama }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-md bg-secondary px-2 py-0.5 text-xs font-semibold">
                                    {{ row.jenis_badan }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-xs text-muted-foreground">
                                {{ row.npwp ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">
                                {{ row.nama_pic ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-muted-foreground">
                                <div v-if="row.telepon" class="text-xs">{{ row.telepon }}</div>
                                <div v-if="row.email" class="text-xs">{{ row.email }}</div>
                                <span v-if="!row.telepon && !row.email">—</span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="badge-status"
                                    :class="row.is_active ? 'badge-success' : 'badge-muted'"
                                >
                                    {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <Link
                                    :href="`/penyedia/${row.id}/edit`"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline"
                                    @click.stop
                                >
                                    <Pencil class="h-3 w-3" />
                                    Edit
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="penyedia.last_page > 1"
                class="-mx-6 -mb-6 mt-6 flex items-center justify-between border-t border-border bg-secondary/20 px-6 py-3"
            >
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ penyedia.from }}–{{ penyedia.to }} dari {{ penyedia.total }}
                </div>
                <div class="flex gap-1">
                    <template v-for="(link, idx) in penyedia.links" :key="idx">
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