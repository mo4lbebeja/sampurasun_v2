<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { ExternalLink, Pencil, Plus, Search, Trash2, CheckCircle2, XCircle, Wallet } from 'lucide-vue-next';

import PageHeader    from '@/components/ev/PageHeader.vue';
import Section       from '@/components/ev/Section.vue';
import StatusBadge   from '@/components/ev/StatusBadge.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';
import EmptyState    from '@/components/ev/EmptyState.vue';

type Belanja = {
    id: number;
    no_nota: string | null;
    tanggal_belanja: string;
    uraian: string;
    jenis: string;
    nominal: string;
    status: string;
    catatan_penolakan: string | null;
    pembelanja: { id: number; name: string } | null;
    anggaran: { id: number; kode_rekening: string; nama_rekening: string } | null;
    approver: { id: number; name: string } | null;
};

type Stats = {
    total: number; pending: number;
    disetujui: number; dibayar: number;
    total_nominal: number;
};

type Paginated<T> = {
    data: T[]; current_page: number; last_page: number;
    from: number | null; to: number | null; total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = withDefaults(
    defineProps<{
        belanja?:    Paginated<Belanja>;
        stats?:      Stats;
        jenisLabel?: Record<string, string>;
        userRole?:   string;
        filters?:    { search: string; status: string; jenis: string };
    }>(),
    {
        belanja:    () => ({ data: [], current_page: 1, last_page: 1, from: null, to: null, total: 0, links: [] }),
        stats:      () => ({ total: 0, pending: 0, disetujui: 0, dibayar: 0, total_nominal: 0 }),
        jenisLabel: () => ({}),
        userRole:   '',
        filters:    () => ({ search: '', status: '', jenis: '' }),
    },
);

const search       = ref(props.filters?.search ?? '');
const filterStatus = ref(props.filters?.status ?? '');
const filterJenis  = ref(props.filters?.jenis  ?? '');

const reload = () => {
    router.get('/belanja-langsung', {
        search: search.value       || undefined,
        status: filterStatus.value || undefined,
        jenis:  filterJenis.value  || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const debouncedReload = debounce(reload, 400);
watch(search,       () => debouncedReload());
watch(filterStatus, () => reload());
watch(filterJenis,  () => reload());

const fmt = (val: string | number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0,
    }).format(Number(val));

const fmtDate = (val: string) =>
    new Date(val).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

const hapus = (item: Belanja) => {
    if (!confirm(`Hapus nota "${item.uraian}"?`)) return;
    router.delete(`/belanja-langsung/${item.id}`, { preserveScroll: true });
};

// Modal approve/reject/bayar
const modalOpen   = ref(false);
const modalType   = ref<'approve' | 'reject' | 'bayar'>('approve');
const selectedId  = ref<number | null>(null);

const actionForm = useForm({
    catatan:             '',
    catatan_penolakan:   '',
    tanggal_dibayar:     new Date().toISOString().slice(0, 10),
});

const openModal = (type: typeof modalType.value, id: number) => {
    modalType.value  = type;
    selectedId.value = id;
    actionForm.reset();
    modalOpen.value  = true;
};

const submitAction = () => {
    if (!selectedId.value) return;
    const url = modalType.value === 'approve'
        ? `/belanja-langsung/${selectedId.value}/approve`
        : modalType.value === 'reject'
        ? `/belanja-langsung/${selectedId.value}/reject`
        : `/belanja-langsung/${selectedId.value}/bayar`;

    actionForm.post(url, {
        preserveScroll: true,
        onSuccess: () => { modalOpen.value = false; },
    });
};

const statusColor = (s: string): string => {
    const map: Record<string, string> = {
        draft:     'bg-muted text-muted-foreground',
        diajukan:  'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300',
        disetujui: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
        ditolak:   'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
        dibayar:   'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    };
    return map[s] ?? 'bg-muted text-muted-foreground';
};

const canApprove = (item: Belanja) =>
    (props.userRole === 'pptk' || props.userRole === 'admin') && item.status === 'diajukan';

const canBayar = (item: Belanja) =>
    (props.userRole === 'keuangan' || props.userRole === 'admin') && item.status === 'disetujui';
</script>

<template>
    <Head title="Belanja Langsung" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">

        <PageHeader title="Belanja Langsung & Reimburse" eyebrow="Keuangan"
            subtitle="Pencatatan pembelanjaan langsung tanpa alur pengadaan formal">
            <template #actions>
                <Link v-if="userRole === 'sarana_umum' || userRole === 'admin'" href="/belanja-langsung/create">
                    <PrimaryButton variant="primary">
                        <Plus class="h-4 w-4" />
                        Tambah Nota
                    </PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <!-- Stats -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-lg border border-border bg-card p-4">
                <div class="text-eyebrow">Total Nota</div>
                <div class="mt-1 font-display text-2xl font-bold">{{ stats.total }}</div>
            </div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950">
                <div class="text-eyebrow">Menunggu</div>
                <div class="mt-1 font-display text-2xl font-bold text-amber-700 dark:text-amber-300">{{ stats.pending }}</div>
            </div>
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950">
                <div class="text-eyebrow">Disetujui</div>
                <div class="mt-1 font-display text-2xl font-bold text-blue-700 dark:text-blue-300">{{ stats.disetujui }}</div>
            </div>
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
                <div class="text-eyebrow">Total Dibayar</div>
                <div class="mt-1 font-mono text-sm font-bold text-green-700 dark:text-green-300">{{ fmt(stats.total_nominal) }}</div>
            </div>
        </div>

        <Section title="Daftar Nota" eyebrow="Belanja Langsung" :description="`${belanja?.total ?? 0} nota`">
            <!-- Filter -->
            <div class="mb-4 flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input v-model="search" type="text" placeholder="Cari uraian atau no. nota..."
                        class="h-10 w-full rounded-md border border-input bg-background pl-9 pr-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                </div>
                <select v-model="filterStatus"
                    class="h-10 rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none">
                    <option value="">Semua Status</option>
                    <option value="draft">Draft</option>
                    <option value="diajukan">Diajukan</option>
                    <option value="disetujui">Disetujui</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="dibayar">Dibayar</option>
                </select>
                <select v-model="filterJenis"
                    class="h-10 rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none">
                    <option value="">Semua Jenis</option>
                    <option v-for="(label, key) in jenisLabel" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>

            <EmptyState v-if="belanja?.data.length === 0"
                title="Belum ada nota belanja"
                description="Tambahkan nota pembelanjaan langsung atau reimburse." />

            <div v-else class="overflow-hidden rounded-lg border border-border">
                <table class="w-full text-sm">
                    <thead class="border-b border-border bg-muted text-xs uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left">Uraian</th>
                            <th class="w-24 px-4 py-3 text-left">Jenis</th>
                            <th class="w-32 px-4 py-3 text-left">Tanggal</th>
                            <th class="w-36 px-4 py-3 text-right">Nominal</th>
                            <th class="w-24 px-4 py-3 text-center">Status</th>
                            <th class="w-32 px-4 py-3 text-left">Pembelanja</th>
                            <th class="w-36 px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in belanja?.data" :key="item.id"
                            class="border-b border-border last:border-b-0 hover:bg-muted/30">
                            <td class="px-4 py-3">
                                <div class="font-medium line-clamp-1">{{ item.uraian }}</div>
                                <div v-if="item.anggaran" class="mt-0.5 text-xs text-muted-foreground line-clamp-1">
                                    {{ item.anggaran.kode_rekening }} — {{ item.anggaran.nama_rekening }}
                                </div>
                                <div v-if="item.catatan_penolakan"
                                    class="mt-1 text-xs text-red-600 dark:text-red-400">
                                    Ditolak: {{ item.catatan_penolakan }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-secondary px-2 py-0.5 text-xs">
                                    {{ jenisLabel[item.jenis] ?? item.jenis }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ fmtDate(item.tanggal_belanja) }}</td>
                            <td class="px-4 py-3 text-right font-mono font-semibold">{{ fmt(item.nominal) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusColor(item.status)">
                                    {{ item.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-muted-foreground">{{ item.pembelanja?.name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Approve/Reject — PPTK -->
                                    <template v-if="canApprove(item)">
                                        <button type="button" title="Setujui"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-green-300 bg-green-50 text-green-700 transition hover:bg-green-100"
                                            @click="openModal('approve', item.id)">
                                            <CheckCircle2 class="h-3.5 w-3.5" />
                                        </button>
                                        <button type="button" title="Tolak"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-300 bg-red-50 text-red-700 transition hover:bg-red-100"
                                            @click="openModal('reject', item.id)">
                                            <XCircle class="h-3.5 w-3.5" />
                                        </button>
                                    </template>

                                    <!-- Bayar — Keuangan -->
                                    <button v-if="canBayar(item)" type="button" title="Catat Pembayaran"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-300 bg-blue-50 text-blue-700 transition hover:bg-blue-100"
                                        @click="openModal('bayar', item.id)">
                                        <Wallet class="h-3.5 w-3.5" />
                                    </button>

                                    <!-- Edit — draft/ditolak -->
                                    <Link v-if="['draft','ditolak'].includes(item.status)"
                                        :href="`/belanja-langsung/${item.id}/edit`"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-border bg-background transition hover:bg-muted">
                                        <Pencil class="h-3.5 w-3.5" />
                                    </Link>

                                    <!-- Nota file -->
                                    <a v-if="item.file_nota" :href="`/storage/${item.file_nota}`" target="_blank"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-border bg-background transition hover:bg-muted">
                                        <ExternalLink class="h-3.5 w-3.5" />
                                    </a>

                                    <!-- Hapus — draft only -->
                                    <button v-if="item.status === 'draft'" type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-border bg-background text-destructive transition hover:bg-destructive/10"
                                        @click="hapus(item)">
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="belanja && belanja.last_page > 1" class="mt-4 flex flex-wrap gap-2">
                <Link v-for="link in belanja.links" :key="link.label"
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

    <!-- Modal Approve / Reject / Bayar -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-md overflow-hidden rounded-xl border border-border bg-card shadow-xl">
            <div class="border-b border-border px-5 py-4">
                <h3 class="font-display text-base font-semibold">
                    {{ modalType === 'approve' ? 'Setujui Nota'
                     : modalType === 'reject'  ? 'Tolak Nota'
                     : 'Catat Pembayaran Reimburse' }}
                </h3>
            </div>
            <form @submit.prevent="submitAction" class="space-y-4 p-5">
                <!-- Approve -->
                <div v-if="modalType === 'approve'">
                    <label class="mb-1.5 block text-sm font-semibold">Catatan (opsional)</label>
                    <textarea v-model="actionForm.catatan" rows="3"
                        class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        placeholder="Catatan persetujuan..." />
                </div>

                <!-- Reject -->
                <div v-if="modalType === 'reject'">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Alasan Penolakan <span class="text-destructive">*</span>
                    </label>
                    <textarea v-model="actionForm.catatan_penolakan" rows="3" required
                        class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        placeholder="Jelaskan alasan penolakan..." />
                </div>

                <!-- Bayar -->
                <div v-if="modalType === 'bayar'">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Tanggal Dibayar <span class="text-destructive">*</span>
                    </label>
                    <input v-model="actionForm.tanggal_dibayar" type="date" required
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                </div>

                <div class="flex justify-end gap-3">
                    <PrimaryButton type="button" variant="secondary" @click="modalOpen = false">Batal</PrimaryButton>
                    <PrimaryButton type="submit" variant="primary" :disabled="actionForm.processing">
                        {{ modalType === 'approve' ? 'Setujui'
                         : modalType === 'reject'  ? 'Tolak'
                         : 'Catat Pembayaran' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </div>
</template>