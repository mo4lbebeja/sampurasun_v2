<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { Search, Plus, Tag, Pencil } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

type Kategori = {
    id: number;
    kode: string;
    nama: string;
    deskripsi: string | null;
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
        kategori?: Paginated<Kategori>;
        filters?: { search: string };
    }>(),
    {
        kategori: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            from: null,
            to: null,
            total: 0,
            links: [],
        }),
        filters: () => ({ search: '' }),
    },
);

const search = ref(props.filters.search);

const reload = () => {
    router.get(
        '/kategori',
        { search: search.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

watch(search, debounce(reload, 400));

const isEmpty = computed(() => props.kategori.data.length === 0);
const hasFilters = computed(() => !!props.filters.search);
</script>

<template>
    <Head title="Kategori Barang" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <PageHeader
            title="Kategori Barang"
            :subtitle="`${kategori.total} kategori terdaftar`"
            eyebrow="Master Data"
        >
            <template #actions>
                <Link href="/kategori/create">
                    <PrimaryButton variant="primary">
                        <Plus class="h-4 w-4" />
                        Tambah Kategori
                    </PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <!-- Search bar -->
        <div class="relative">
            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <input
                v-model="search"
                type="search"
                placeholder="Cari kode atau nama kategori..."
                class="h-11 w-full rounded-md border border-input bg-card py-2 pl-10 pr-3 text-sm placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            />
        </div>

        <!-- Empty state -->
        <EmptyState
            v-if="isEmpty"
            :title="hasFilters ? 'Tidak ada hasil yang cocok' : 'Belum ada kategori'"
            :description="
                hasFilters
                    ? 'Coba ubah kata kunci pencarian.'
                    : 'Tambahkan kategori pertama untuk mulai mengelompokkan barang/jasa.'
            "
            :icon="Tag"
        >
            <template v-if="!hasFilters" #action>
                <Link href="/kategori/create">
                    <PrimaryButton variant="primary">
                        <Plus class="h-4 w-4" />
                        Tambah Kategori Pertama
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
                            <th class="px-6 py-3 font-semibold">Kode</th>
                            <th class="px-6 py-3 font-semibold">Nama Kategori</th>
                            <th class="px-6 py-3 font-semibold">Deskripsi</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="row in kategori.data"
                            :key="row.id"
                            class="cursor-pointer transition hover:bg-muted/30"
                            @click="$inertia.visit(`/kategori/${row.id}/edit`)"
                        >
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-md bg-primary/10 px-2 py-1 font-mono text-xs font-bold text-primary">
                                    {{ row.kode }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium">{{ row.nama }}</td>
                            <td class="px-6 py-4 text-muted-foreground">
                                <div class="line-clamp-1">{{ row.deskripsi ?? '—' }}</div>
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
                                    :href="`/kategori/${row.id}/edit`"
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
                v-if="kategori.last_page > 1"
                class="-mx-6 -mb-6 mt-6 flex items-center justify-between border-t border-border bg-secondary/20 px-6 py-3"
            >
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ kategori.from }}–{{ kategori.to }} dari {{ kategori.total }}
                </div>
                <div class="flex gap-1">
                    <template v-for="(link, idx) in kategori.links" :key="idx">
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