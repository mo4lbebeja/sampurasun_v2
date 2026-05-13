<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import {
    Plus,
    Search,
    Inbox,
} from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

type DpaAnggaran = {
    id: number;
    tahun_anggaran: number;
    no_dpa: string;
    tanggal_dpa: string | null;
    nama_dpa: string | null;
};

type SubKegiatan = {
    id: number;
    dpa_anggaran_id: number | null;
    kode_sub_kegiatan: string | null;
    nama_kegiatan: string;
    tahun_anggaran: number;
    is_active: boolean;
    dpa_anggaran?: DpaAnggaran | null;
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
        subKegiatan?: Paginated<SubKegiatan>;
        filters?: {
            search: string;
        };
    }>(),
    {
        subKegiatan: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            from: null,
            to: null,
            total: 0,
            links: [],
        }),
        filters: () => ({
            search: '',
        }),
    },
);

const search = ref(props.filters.search);

const reload = () => {
    router.get(
        '/sub-kegiatan',
        {
            search: search.value || undefined,
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

const isEmpty = computed(() => props.subKegiatan.data.length === 0);

const hasActiveFilters = computed(() => !!props.filters.search);

const resetFilters = () => {
    search.value = '';
    reload();
};

const formatDate = (val: string | null) => {
    if (!val) return '—';

    return new Date(val).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Sub Kegiatan" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <!-- Header -->
        <PageHeader
            title="Sub Kegiatan"
            :subtitle="`${subKegiatan.total} sub kegiatan terdaftar`"
            eyebrow="Master Data"
        >
            <template #actions>
                <Link href="/sub-kegiatan/create">
                    <PrimaryButton variant="primary">
                        <Plus class="h-4 w-4" />
                        Tambah Sub Kegiatan
                    </PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <!-- Search seperti Kategori Barang -->
        <div class="relative">
            <Search class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground" />

            <input
                v-model="search"
                type="search"
                placeholder="Cari kode, nama sub kegiatan, atau nomor DPA..."
                class="h-14 w-full rounded-md border border-input bg-background py-2 pl-12 pr-4 text-base placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            />
        </div>

        <!-- Empty state -->
        <EmptyState
            v-if="isEmpty"
            :title="hasActiveFilters ? 'Tidak ada hasil yang cocok' : 'Belum ada sub kegiatan'"
            :description="
                hasActiveFilters
                    ? 'Coba ubah kata kunci pencarian atau reset pencarian.'
                    : 'Tambahkan sub kegiatan untuk mengelompokkan rekening anggaran.'
            "
            :icon="Inbox"
        >
            <template v-if="hasActiveFilters" #action>
                <PrimaryButton variant="primary" @click="resetFilters">
                    Reset Pencarian
                </PrimaryButton>
            </template>
        </EmptyState>

        <!-- Tabel -->
        <Section v-else>
            <div class="-mx-6 -my-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-secondary/40 text-left text-[10px] uppercase tracking-widest text-muted-foreground">
                            <th class="px-6 py-3 font-semibold">Tahun</th>
                            <th class="px-6 py-3 font-semibold">Kode Sub Kegiatan</th>
                            <th class="px-6 py-3 font-semibold">Nama Sub Kegiatan</th>
                            <th class="px-6 py-3 font-semibold">DPA</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="item in subKegiatan.data"
                            :key="item.id"
                            class="transition hover:bg-muted/30"
                        >
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-xs">
                                {{ item.tahun_anggaran }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 font-mono text-xs">
                                {{ item.kode_sub_kegiatan ?? '—' }}
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-medium">
                                    {{ item.nama_kegiatan }}
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-mono text-xs font-medium">
                                    {{ item.dpa_anggaran?.no_dpa ?? '—' }}
                                </div>

                                <div
                                    v-if="item.dpa_anggaran?.tanggal_dpa"
                                    class="mt-0.5 text-xs text-muted-foreground"
                                >
                                    {{ formatDate(item.dpa_anggaran.tanggal_dpa) }}
                                </div>

                                <div
                                    v-if="item.dpa_anggaran?.nama_dpa"
                                    class="mt-0.5 text-xs text-muted-foreground"
                                >
                                    {{ item.dpa_anggaran.nama_dpa }}
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    class="badge-status"
                                    :class="item.is_active ? 'badge-success' : 'badge-muted'"
                                >
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <Link
                                    :href="`/sub-kegiatan/${item.id}/edit`"
                                    class="text-xs font-semibold text-primary hover:underline"
                                >
                                    Edit
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="subKegiatan.last_page > 1"
                class="-mx-6 -mb-6 mt-6 flex items-center justify-between border-t border-border bg-secondary/20 px-6 py-3"
            >
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ subKegiatan.from }}–{{ subKegiatan.to }} dari {{ subKegiatan.total }}
                </div>

                <div class="flex gap-1">
                    <template
                        v-for="(link, idx) in subKegiatan.links"
                        :key="idx"
                    >
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