<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    FileText,
    Calendar,
    User,
    Building2,
    Wallet,
    Stamp,
    ShoppingCart,
    CheckCircle2,
    XCircle,
    RotateCcw,
    PlusCircle,
    X,
} from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import StatusBadge from '@/components/ev/StatusBadge.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

// ── Types ──────────────────────────────────────────────────────────

type Item = {
    id: number;
    nama_barang: string;
    spesifikasi: string | null;
    satuan: string;
    jumlah: number;
    harga_satuan_estimasi: string;
    subtotal: string;
    kategori: { id: number; nama: string } | null;
};

type Approval = {
    id: number;
    keputusan: string;
    catatan: string | null;
    tanggal_keputusan: string;
    approver: { id: number; name: string; jabatan: string | null } | null;
};

type PaketPengadaan = {
    id: number;
    no_pengadaan: string;
    nama_paket: string | null;
    status: string;
    metode: string;
    estimasi_paket: string | number;
    nilai_kontrak: string | number;
};

type Usulan = {
    id: number;
    no_usulan: string;
    judul: string;
    latar_belakang: string | null;
    keterangan: string | null;
    tanggal_usulan: string;
    submitted_at: string | null;
    total_estimasi: string;
    status: string;
    pemohon: {
        id: number;
        name: string;
        jabatan: string | null;
        unit_kerja: { id: number; nama: string } | null;
    } | null;
    anggaran: {
        id: number;
        kode_rekening: string;
        nama_rekening: string;
        pagu: string;
    } | null;
    items: Item[];
    approvals: Approval[];
};

// ── Props ──────────────────────────────────────────────────────────

const props = withDefaults(
    defineProps<{
        usulan: Usulan;
        pengadaanList?: PaketPengadaan[];
        itemsAssignedIds?: number[];
    }>(),
    {
        pengadaanList: () => [],
        itemsAssignedIds: () => [],
    },
);

// ── User & approval logic ──────────────────────────────────────────

const page = usePage();
const userRole = computed(() => page.props.auth.user?.role);
const isAdmin = computed(() => page.props.auth.user?.is_admin === true);

const canDecide = computed(() =>
    (userRole.value === 'pptk' || isAdmin.value) && props.usulan.status === 'diajukan'
);

const approvalForm = useForm({
    keputusan: '' as 'disetujui' | 'ditolak' | 'revisi' | '',
    catatan: '',
});

const showCatatan = computed(() =>
    approvalForm.keputusan === 'ditolak' || approvalForm.keputusan === 'revisi'
);

const submitDecision = () => {
    if (!approvalForm.keputusan) return;

    const labels: Record<string, string> = {
        disetujui: 'menyetujui',
        ditolak: 'menolak',
        revisi: 'meminta revisi',
    };

    if (!confirm(`Yakin ${labels[approvalForm.keputusan]} usulan ini?`)) return;

    approvalForm.post(`/usulan/${props.usulan.id}/decide`, {
        preserveScroll: true,
    });
};

// ── Pengadaan logic — multi-paket ─────────────────────────────────

const assignedIds = computed(() => props.itemsAssignedIds ?? []);

const itemsBelumDipaket = computed(() =>
    (props.usulan.items ?? []).filter((item) => !assignedIds.value.includes(item.id))
);

const canCreatePaket = computed(() =>
    (userRole.value === 'pejabat_pengadaan' || isAdmin.value) &&
    (props.usulan.status === 'disetujui' ||
        (props.usulan.status === 'dalam_pengadaan' && itemsBelumDipaket.value.length > 0))
);

const paketForm = useForm({
    nama_paket:    '',
    metode:        'pengadaan_langsung' as string,
    tanggal_mulai: new Date().toISOString().slice(0, 10),
    catatan:       '',
    item_ids:      [] as number[],
});

const selectAllItems = ref(false);

watch(selectAllItems, (val) => {
    paketForm.item_ids = val
        ? itemsBelumDipaket.value.map((i) => i.id)
        : [];
});

watch(() => paketForm.item_ids, (ids) => {
    selectAllItems.value =
        itemsBelumDipaket.value.length > 0 &&
        ids.length === itemsBelumDipaket.value.length;
});

const estimasiDipilih = computed(() =>
    (props.usulan.items ?? [])
        .filter((i) => paketForm.item_ids.includes(i.id))
        .reduce((sum, i) => sum + Number(i.subtotal ?? 0), 0)
);

const showPaketForm = ref(false);

const metodeOptions = [
    { value: 'pengadaan_langsung',  label: 'Pengadaan Langsung'  },
    { value: 'penunjukan_langsung', label: 'Penunjukan Langsung' },
    { value: 'tender',              label: 'Tender'              },
    { value: 'tender_cepat',        label: 'Tender Cepat'        },
    { value: 'e_purchasing',        label: 'E-Purchasing'        },
    { value: 'swakelola',           label: 'Swakelola'           },
];

const submitPaket = () => {
    if (itemsBelumDipaket.value.length > 0 && paketForm.item_ids.length === 0) {
        alert('Pilih minimal satu item untuk paket ini.');
        return;
    }

    paketForm.post(`/usulan/${props.usulan.id}/pengadaan`, {
        preserveScroll: true,
        onSuccess: () => {
            showPaketForm.value = false;
            paketForm.reset();
            selectAllItems.value = false;
        },
    });
};

// ── Format helpers ─────────────────────────────────────────────────

const formatRupiah = (val: string | number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(val));

const formatDate = (val: string | null) => {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'long', year: 'numeric',
    });
};

const formatDateTime = (val: string | null) => {
    if (!val) return '—';
    return new Date(val).toLocaleString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const formatMetode = (val: string) =>
    val.split('_').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');

// ── Workflow timeline ──────────────────────────────────────────────

const workflowSteps = [
    { num: 1, name: 'Usulan',     role: 'Sarana & Umum' },
    { num: 2, name: 'Approval',   role: 'PPTK' },
    { num: 3, name: 'Pengadaan',  role: 'Pejabat Pengadaan' },
    { num: 4, name: 'Dokumen',    role: 'UPBJ' },
    { num: 5, name: 'Pembayaran', role: 'Keuangan' },
    { num: 6, name: 'Evaluasi',   role: 'Perencanaan' },
];

const currentStepIdx = computed(() => {
    const order: Record<string, number> = {
        draft: 0, diajukan: 0,
        disetujui: 1, ditolak: 1,
        dalam_pengadaan: 2,
        dokumen: 3,
        pembayaran: 4,
        evaluasi: 5, selesai: 5,
        dibatalkan: -1,
    };
    return order[props.usulan.status] ?? 0;
});

const stepStatus = (idx: number): 'done' | 'active' | 'pending' => {
    if (idx < currentStepIdx.value) return 'done';
    if (idx === currentStepIdx.value) return 'active';
    return 'pending';
};
</script>

<template>
    <Head :title="`Detail ${usulan.no_usulan}`" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Header -->
        <div class="flex items-start gap-3">
            <Link
                href="/usulan"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-card transition hover:bg-muted"
            >
                <ArrowLeft class="h-4 w-4" />
            </Link>
            <div class="flex-1">
                <PageHeader :title="usulan.judul" :eyebrow="usulan.no_usulan">
                    <template #actions>
                        <StatusBadge :status="usulan.status" />
                    </template>
                </PageHeader>
            </div>
        </div>

        <!-- ── Panel Approval (PPTK) ─────────────────────────────── -->
        <Section
            v-if="canDecide"
            class="border-2"
            style="border-color: var(--color-brand-warning); background-color: var(--color-brand-warning-bg);"
        >
            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-warning);"
                    >
                        <Stamp class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-eyebrow">Tindakan Diperlukan</div>
                        <h2 class="font-display text-xl font-semibold" style="color: var(--color-brand-warning);">
                            Usulan Menunggu Keputusan Anda
                        </h2>
                        <p class="mt-1 text-sm" style="color: var(--color-brand-warning);">
                            Pilih salah satu keputusan. Catatan wajib diisi untuk Tolak atau Revisi.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <button
                        type="button"
                        @click="approvalForm.keputusan = 'disetujui'"
                        class="flex items-center justify-center gap-2 rounded-md border-2 bg-card px-4 py-3 text-sm font-semibold transition"
                        :class="approvalForm.keputusan === 'disetujui'
                            ? 'border-green-500 bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300'
                            : 'border-border hover:border-green-400'"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        Setujui
                    </button>
                    <button
                        type="button"
                        @click="approvalForm.keputusan = 'ditolak'"
                        class="flex items-center justify-center gap-2 rounded-md border-2 bg-card px-4 py-3 text-sm font-semibold transition"
                        :class="approvalForm.keputusan === 'ditolak'
                            ? 'border-red-500 bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300'
                            : 'border-border hover:border-red-400'"
                    >
                        <XCircle class="h-4 w-4" />
                        Tolak
                    </button>
                    <button
                        type="button"
                        @click="approvalForm.keputusan = 'revisi'"
                        class="flex items-center justify-center gap-2 rounded-md border-2 bg-card px-4 py-3 text-sm font-semibold transition"
                        :class="approvalForm.keputusan === 'revisi'
                            ? 'border-amber-500 bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                            : 'border-border hover:border-amber-400'"
                    >
                        <RotateCcw class="h-4 w-4" />
                        Minta Revisi
                    </button>
                </div>

                <div v-if="showCatatan">
                    <label class="text-eyebrow mb-1 block">Catatan (wajib)</label>
                    <textarea
                        v-model="approvalForm.catatan"
                        rows="3"
                        class="input w-full"
                        placeholder="Jelaskan alasan penolakan atau permintaan revisi..."
                    />
                    <p v-if="approvalForm.errors.catatan" class="mt-1 text-xs text-destructive">
                        {{ approvalForm.errors.catatan }}
                    </p>
                </div>

                <div class="flex justify-end">
                    <PrimaryButton
                        type="button"
                        :disabled="!approvalForm.keputusan || approvalForm.processing"
                        @click="submitDecision"
                    >
                        {{ approvalForm.processing ? 'Memproses...' : 'Kirim Keputusan' }}
                    </PrimaryButton>
                </div>
            </div>
        </Section>

        <!-- ── Info Usulan ────────────────────────────────────────── -->
        <Section title="Informasi Usulan" eyebrow="Detail">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <div class="text-eyebrow">Nomor Usulan</div>
                    <div class="mt-0.5 font-mono text-sm">{{ usulan.no_usulan }}</div>
                </div>
                <div>
                    <div class="text-eyebrow">Tanggal Usulan</div>
                    <div class="mt-0.5 text-sm">{{ formatDate(usulan.tanggal_usulan) }}</div>
                </div>
                <div>
                    <div class="text-eyebrow">Pemohon</div>
                    <div class="mt-0.5 text-sm font-medium">{{ usulan.pemohon?.name ?? '—' }}</div>
                    <div class="text-xs text-muted-foreground">
                        {{ usulan.pemohon?.jabatan }} · {{ usulan.pemohon?.unit_kerja?.nama }}
                    </div>
                </div>
                <div>
                    <div class="text-eyebrow">Pos Anggaran</div>
                    <div class="mt-0.5 font-mono text-xs">{{ usulan.anggaran?.kode_rekening }}</div>
                    <div class="text-sm">{{ usulan.anggaran?.nama_rekening }}</div>
                </div>
                <div v-if="usulan.latar_belakang" class="sm:col-span-2">
                    <div class="text-eyebrow">Latar Belakang</div>
                    <p class="mt-0.5 whitespace-pre-line text-sm">{{ usulan.latar_belakang }}</p>
                </div>
                <div v-if="usulan.keterangan" class="sm:col-span-2">
                    <div class="text-eyebrow">Keterangan</div>
                    <p class="mt-0.5 whitespace-pre-line text-sm">{{ usulan.keterangan }}</p>
                </div>
            </div>
        </Section>

        <!-- ── Detail Barang/Jasa ─────────────────────────────────── -->
        <Section
            title="Detail Barang/Jasa"
            eyebrow="Item Usulan"
            :description="`${usulan.items.length} item · Total ${formatRupiah(usulan.total_estimasi)}`"
        >
            <EmptyState
                v-if="usulan.items.length === 0"
                title="Belum ada item"
                :icon="ShoppingCart"
            />
            <div v-else class="-mx-6 -my-6 divide-y divide-border">
                <div
                    v-for="item in usulan.items"
                    :key="item.id"
                    class="flex items-start gap-3 px-6 py-3"
                    :class="assignedIds.includes(item.id) ? 'opacity-50' : ''"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium">{{ item.nama_barang }}</span>
                            <span
                                v-if="assignedIds.includes(item.id)"
                                class="rounded-full bg-muted px-2 py-0.5 text-[10px] text-muted-foreground"
                            >
                                Sudah dipaketkan
                            </span>
                        </div>
                        <div class="mt-0.5 text-xs text-muted-foreground">
                            {{ item.kategori?.nama }} ·
                            {{ item.jumlah }} {{ item.satuan }} ·
                            {{ formatRupiah(item.harga_satuan_estimasi) }}/{{ item.satuan }}
                        </div>
                        <div v-if="item.spesifikasi" class="mt-1 text-xs text-muted-foreground">
                            {{ item.spesifikasi }}
                        </div>
                    </div>
                    <div class="shrink-0 font-mono text-sm font-medium">
                        {{ formatRupiah(item.subtotal) }}
                    </div>
                </div>

                <!-- Total -->
                <div class="flex items-center justify-between px-6 py-3">
                    <span class="text-sm font-semibold">Total Estimasi</span>
                    <span class="font-mono text-sm font-semibold">
                        {{ formatRupiah(usulan.total_estimasi) }}
                    </span>
                </div>
            </div>
        </Section>

        <!-- ── Paket Pengadaan ────────────────────────────────────── -->
        <Section
            v-if="canCreatePaket || pengadaanList.length > 0"
            title="Paket Pengadaan"
            eyebrow="Pejabat Pengadaan"
            :description="pengadaanList.length
                ? `${pengadaanList.length} paket dari usulan ini`
                : 'Belum ada paket pengadaan'"
        >
            <!-- Daftar paket yang sudah ada -->
            <div v-if="pengadaanList.length > 0" class="-mx-6 -mt-6 mb-0 divide-y divide-border">
                <Link
                    v-for="paket in pengadaanList"
                    :key="paket.id"
                    :href="`/pengadaan/${paket.id}`"
                    class="flex items-center gap-4 px-6 py-3 transition hover:bg-muted/30"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs text-muted-foreground">
                                {{ paket.no_pengadaan }}
                            </span>
                            <StatusBadge :status="paket.status" />
                        </div>
                        <div class="mt-0.5 text-sm font-medium">
                            {{ paket.nama_paket || '(Tanpa nama paket)' }}
                        </div>
                        <div class="mt-0.5 text-xs text-muted-foreground">
                            {{ formatMetode(paket.metode) }} ·
                            <template v-if="Number(paket.nilai_kontrak) > 0">
                                Kontrak {{ formatRupiah(paket.nilai_kontrak) }}
                            </template>
                            <template v-else>
                                Estimasi {{ formatRupiah(paket.estimasi_paket) }}
                            </template>
                        </div>
                    </div>
                    <ArrowRight class="h-4 w-4 shrink-0 text-muted-foreground" />
                </Link>
            </div>

            <!-- Tombol buat paket baru -->
            <div
                v-if="canCreatePaket && !showPaketForm"
                :class="pengadaanList.length > 0 ? 'border-t border-border -mx-6 px-6 py-4' : '-mx-6 -mb-6 px-6 py-4'"
            >
                <button
                    type="button"
                    class="flex w-full items-center justify-center gap-2 rounded-md border border-dashed border-border py-3 text-sm text-muted-foreground transition hover:border-primary hover:text-primary"
                    @click="showPaketForm = true"
                >
                    <PlusCircle class="h-4 w-4" />
                    Buat Paket Pengadaan Baru
                    <template v-if="itemsBelumDipaket.length > 0">
                        ({{ itemsBelumDipaket.length }} item belum dipaketkan)
                    </template>
                </button>
            </div>

            <!-- Form buat paket baru -->
            <form
                v-if="canCreatePaket && showPaketForm"
                class="-mx-6 -mb-6 border-t border-border px-6 pb-6 pt-5 space-y-4"
                @submit.prevent="submitPaket"
            >
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Paket Baru</span>
                    <button type="button" @click="showPaketForm = false; paketForm.reset(); selectAllItems = false">
                        <X class="h-4 w-4 text-muted-foreground hover:text-foreground" />
                    </button>
                </div>

                <!-- Nama paket -->
                <div>
                    <label class="text-eyebrow mb-1 block">Nama Paket</label>
                    <input
                        v-model="paketForm.nama_paket"
                        type="text"
                        placeholder="Misal: Paket ATK, Paket Komputer & Printer"
                        class="input w-full"
                    />
                    <p class="mt-1 text-xs text-muted-foreground">
                        Kosongkan jika satu usulan hanya satu paket.
                    </p>
                </div>

                <!-- Metode + Tanggal -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-eyebrow mb-1 block">Metode Pengadaan</label>
                        <select v-model="paketForm.metode" class="input w-full">
                            <option v-for="opt in metodeOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="text-eyebrow mb-1 block">Tanggal Mulai</label>
                        <input v-model="paketForm.tanggal_mulai" type="date" class="input w-full" />
                    </div>
                </div>

                <!-- Pilih item -->
                <div v-if="itemsBelumDipaket.length > 0">
                    <label class="text-eyebrow mb-2 block">Item yang Masuk Paket Ini</label>

                    <label class="mb-2 flex items-center gap-2 text-sm">
                        <input v-model="selectAllItems" type="checkbox" class="h-4 w-4" />
                        <span class="font-medium">
                            Pilih semua ({{ itemsBelumDipaket.length }} item)
                        </span>
                    </label>

                    <div class="divide-y divide-border rounded-md border border-border">
                        <label
                            v-for="item in itemsBelumDipaket"
                            :key="item.id"
                            class="flex cursor-pointer items-start gap-3 px-4 py-3 transition hover:bg-muted/20"
                        >
                            <input
                                v-model="paketForm.item_ids"
                                type="checkbox"
                                :value="item.id"
                                class="mt-0.5 h-4 w-4 flex-shrink-0"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium">{{ item.nama_barang }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ item.jumlah }} {{ item.satuan }} ·
                                    {{ formatRupiah(item.harga_satuan_estimasi) }}/{{ item.satuan }}
                                    · Subtotal {{ formatRupiah(item.subtotal) }}
                                </div>
                            </div>
                        </label>
                    </div>

                    <div v-if="paketForm.item_ids.length > 0" class="mt-3 rounded-md bg-muted/30 px-4 py-3">
                        <div class="text-eyebrow">Estimasi Paket Ini</div>
                        <div class="mt-0.5 font-mono text-lg font-semibold">
                            {{ formatRupiah(estimasiDipilih) }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ paketForm.item_ids.length }} dari {{ itemsBelumDipaket.length }} item dipilih
                        </div>
                    </div>
                </div>

                <!-- Catatan -->
                <div>
                    <label class="text-eyebrow mb-1 block">Catatan</label>
                    <textarea
                        v-model="paketForm.catatan"
                        rows="2"
                        class="input w-full"
                        placeholder="Opsional"
                    />
                </div>

                <p v-if="paketForm.errors.item_ids" class="text-sm text-destructive">
                    {{ paketForm.errors.item_ids }}
                </p>

                <div class="flex gap-3">
                    <PrimaryButton
                        type="submit"
                        :disabled="paketForm.processing"
                        class="flex-1"
                    >
                        {{ paketForm.processing ? 'Memproses...' : 'Mulai Paket Pengadaan' }}
                    </PrimaryButton>
                    <button
                        type="button"
                        class="rounded-md border border-border px-4 py-2 text-sm transition hover:bg-muted"
                        @click="showPaketForm = false; paketForm.reset(); selectAllItems = false"
                    >
                        Batal
                    </button>
                </div>
            </form>
        </Section>

        <!-- ── Riwayat Approval ───────────────────────────────────── -->
        <Section
            v-if="usulan.approvals.length > 0"
            title="Riwayat Keputusan"
            eyebrow="Approval"
        >
            <div class="-mx-6 -my-6 divide-y divide-border">
                <div
                    v-for="approval in usulan.approvals"
                    :key="approval.id"
                    class="flex items-start gap-4 px-6 py-4"
                >
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                        :class="{
                            'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300': approval.keputusan === 'disetujui',
                            'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300': approval.keputusan === 'ditolak',
                            'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300': approval.keputusan === 'revisi',
                        }"
                    >
                        <CheckCircle2 v-if="approval.keputusan === 'disetujui'" class="h-4 w-4" />
                        <XCircle v-else-if="approval.keputusan === 'ditolak'" class="h-4 w-4" />
                        <RotateCcw v-else class="h-4 w-4" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-medium">{{ approval.approver?.name ?? '—' }}</span>
                            <StatusBadge :status="approval.keputusan" :label="approval.keputusan" />
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ approval.approver?.jabatan }} ·
                            {{ formatDateTime(approval.tanggal_keputusan) }}
                        </div>
                        <p v-if="approval.catatan" class="mt-1 text-sm text-muted-foreground">
                            {{ approval.catatan }}
                        </p>
                    </div>
                </div>
            </div>
        </Section>

        <!-- ── Workflow Timeline ──────────────────────────────────── -->
        <div class="rounded-lg border border-border bg-card px-6 py-5">
            <div class="text-eyebrow mb-4">Progress Workflow</div>
            <div class="flex items-start gap-0">
                <template v-for="(step, idx) in workflowSteps" :key="step.num">
                    <div class="flex flex-1 flex-col items-center text-center">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold"
                            :class="{
                                'bg-green-500 text-white': stepStatus(idx) === 'done',
                                'bg-primary text-primary-foreground': stepStatus(idx) === 'active',
                                'bg-muted text-muted-foreground': stepStatus(idx) === 'pending',
                            }"
                        >
                            <CheckCircle2 v-if="stepStatus(idx) === 'done'" class="h-4 w-4" />
                            <span v-else>{{ step.num }}</span>
                        </div>
                        <div class="mt-1.5 text-xs font-medium">{{ step.name }}</div>
                        <div class="text-[10px] text-muted-foreground">{{ step.role }}</div>
                    </div>
                    <div
                        v-if="idx < workflowSteps.length - 1"
                        class="mt-4 h-px flex-1 transition-colors"
                        :class="stepStatus(idx) === 'done' ? 'bg-green-400' : 'bg-border'"
                    />
                </template>
            </div>
        </div>

    </div>
</template>