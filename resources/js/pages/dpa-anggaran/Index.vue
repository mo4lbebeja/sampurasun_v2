<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { Pencil, Plus, Search, Trash2 } from 'lucide-vue-next';

import PageHeader    from '@/components/ev/PageHeader.vue';
import Section       from '@/components/ev/Section.vue';
import EmptyState    from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

type DpaAnggaran = {
    id:             number;
    tahun_anggaran: number;
    no_dpa:         string;
    tanggal_dpa:    string | null;
    nama_dpa:       string | null;
    keterangan:     string | null;
    is_active:      boolean;
};

type Paginated<T> = {
    data: T[];
    current_page: number; last_page: number;
    from: number | null; to: number | null; total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = withDefaults(
    defineProps<{
        dpa?:         Paginated<DpaAnggaran>;
        tahunOptions?: number[];
        filters?:     { search: string; tahun: string | null };
    }>(),
    {
        dpa: () => ({ data: [], current_page: 1, last_page: 1, from: null, to: null, total: 0, links: [] }),
        tahunOptions: () => [],
        filters:     () => ({ search: '', tahun: null }),
    },
);

const search = ref(props.filters?.search ?? '');
const tahun  = ref<string>(props.filters?.tahun ?? '');

const reload = () => {
    router.get('/dpa-anggaran', {
        search: search.value || undefined,
        tahun:  tahun.value  || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const debouncedReload = debounce(reload, 400);
watch(search, () => debouncedReload());
watch(tahun,  () => reload());

const formatTanggal = (val: string | null) => {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const hapus = (dpa: DpaAnggaran) => {
    if (!confirm(`Hapus DPA ${dpa.no_dpa}? Tindakan ini tidak bisa dibatalkan.`)) return;
    router.delete(`/dpa-anggaran/${dpa.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="DPA Anggaran" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">

        <PageHeader title="DPA Anggaran" eyebrow="Master Data"
            :subtitle="`${dpa?.total ?? 0} DPA terdaftar`">
            <template #actions>
                <Link href="/dpa-anggaran/create">
                    <PrimaryButton variant="primary">
                        <Plus class="h-4 w-4" />
                        Tambah DPA
                    </PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <Section title="Daftar DPA Anggaran" eyebrow="Data">
            <!-- Filter -->
            <div class="mb-4 flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input v-model="search" type="text" placeholder="Cari nomor atau nama DPA..."
                        class="h-10 w-full rounded-md border border-input bg-background pl-9 pr-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                </div>
                <select v-model="tahun"
                    class="h-10 rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                    <option value="">Semua Tahun</option>
                    <option v-for="t in tahunOptions" :key="t" :value="String(t)">{{ t }}</option>
                </select>
            </div>

            <EmptyState v-if="dpa?.data.length === 0"
                title="Belum ada DPA Anggaran"
                description="Tambahkan DPA Anggaran terlebih dahulu sebelum membuat Sub Kegiatan dan Anggaran." />

            <div v-else class="overflow-hidden rounded-lg border border-border">
                <table class="w-full text-sm">
                    <thead class="border-b border-border bg-muted text-xs uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left">No DPA</th>
                            <th class="px-4 py-3 text-left">Nama DPA</th>
                            <th class="w-20 px-4 py-3 text-center">Tahun</th>
                            <th class="w-36 px-4 py-3 text-left">Tanggal DPA</th>
                            <th class="w-24 px-4 py-3 text-center">Status</th>
                            <th class="w-28 px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in dpa?.data" :key="item.id"
                            class="border-b border-border last:border-b-0 hover:bg-muted/30">
                            <td class="px-4 py-3 font-mono font-medium">{{ item.no_dpa }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ item.nama_dpa ?? '—' }}</div>
                                <div v-if="item.keterangan" class="mt-0.5 text-xs text-muted-foreground line-clamp-1">
                                    {{ item.keterangan }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center font-mono">{{ item.tahun_anggaran }}</td>
                            <td class="px-4 py-3 text-sm text-muted-foreground">
                                {{ formatTanggal(item.tanggal_dpa) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="item.is_active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                        : 'bg-muted text-muted-foreground'">
                                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="`/dpa-anggaran/${item.id}/edit`"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-border bg-background transition hover:bg-muted">
                                        <Pencil class="h-3.5 w-3.5" />
                                    </Link>
                                    <button type="button" @click="hapus(item)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-border bg-background text-destructive transition hover:bg-destructive/10">
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="dpa && dpa.last_page > 1" class="mt-4 flex flex-wrap gap-2">
                <Link v-for="link in dpa.links" :key="link.label"
                    :href="link.url ?? '#'"
                    class="rounded-md border px-3 py-1.5 text-sm"
                    :class="[
                        link.active ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-background hover:bg-muted',
                        !link.url ? 'pointer-events-none opacity-50' : '',
                    ]"
                    v-html="link.label" />
            </div>
        </Section>
    </div>
</template>