<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
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
} from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import StatusBadge from '@/components/ev/StatusBadge.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

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
    pengadaan: {
        id: number;
        penyedia: { id: number; nama: string } | null;
    } | null;
};

const props = defineProps<{ usulan: Usulan }>();

// =====================
// User & approval logic
// =====================
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

    const labels = {
        disetujui: 'menyetujui',
        ditolak: 'menolak',
        revisi: 'meminta revisi',
    };

    if (!confirm(`Yakin ${labels[approvalForm.keputusan]} usulan ini?`)) return;

    approvalForm.post(`/usulan/${props.usulan.id}/decide`, {
        preserveScroll: true,
    });
};

// =====================
// Pengadaan logic
// =====================
const canStartPengadaan = computed(() =>
    (userRole.value === 'pejabat_pengadaan' || isAdmin.value)
        && props.usulan.status === 'disetujui'
);

const pengadaanForm = useForm({
    metode: 'pengadaan_langsung' as string,
    tanggal_mulai: new Date().toISOString().slice(0, 10),
    catatan: '',
});

const metodeOptions = [
    { value: 'pengadaan_langsung',  label: 'Pengadaan Langsung'  },
    { value: 'penunjukan_langsung', label: 'Penunjukan Langsung' },
    { value: 'tender',              label: 'Tender'              },
    { value: 'tender_cepat',        label: 'Tender Cepat'        },
    { value: 'e_purchasing',        label: 'E-Purchasing'        },
    { value: 'swakelola',           label: 'Swakelola'           },
];

const startPengadaan = () => {
    if (!confirm('Yakin mulai proses pengadaan untuk usulan ini?')) return;
    pengadaanForm.post(`/usulan/${props.usulan.id}/pengadaan`, {
        preserveScroll: true,
    });
};

// =====================
// Format helpers
// =====================
const formatRupiah = (val: string | number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(val));

const formatDate = (val: string | null) => {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};

const formatDateTime = (val: string | null) => {
    if (!val) return '—';
    return new Date(val).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// =====================
// Workflow timeline
// =====================
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
        <!-- Header dengan back -->
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

        <!-- ⭐ PANEL APPROVAL (PPTK saat status diajukan) -->
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
                        :class="
                            approvalForm.keputusan === 'disetujui'
                                ? '!border-[var(--color-brand-success)] !bg-[var(--color-brand-success-bg)] !text-[var(--color-brand-success)]'
                                : 'border-border hover:border-[var(--color-brand-success)]'
                        "
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        Setujui
                    </button>
                    <button
                        type="button"
                        @click="approvalForm.keputusan = 'revisi'"
                        class="flex items-center justify-center gap-2 rounded-md border-2 bg-card px-4 py-3 text-sm font-semibold transition"
                        :class="
                            approvalForm.keputusan === 'revisi'
                                ? '!border-[var(--color-brand-warning)] !bg-[var(--color-brand-warning-bg)] !text-[var(--color-brand-warning)]'
                                : 'border-border hover:border-[var(--color-brand-warning)]'
                        "
                    >
                        <RotateCcw class="h-4 w-4" />
                        Minta Revisi
                    </button>
                    <button
                        type="button"
                        @click="approvalForm.keputusan = 'ditolak'"
                        class="flex items-center justify-center gap-2 rounded-md border-2 bg-card px-4 py-3 text-sm font-semibold transition"
                        :class="
                            approvalForm.keputusan === 'ditolak'
                                ? '!border-[var(--color-brand-danger)] !bg-[var(--color-brand-danger-bg)] !text-[var(--color-brand-danger)]'
                                : 'border-border hover:border-[var(--color-brand-danger)]'
                        "
                    >
                        <XCircle class="h-4 w-4" />
                        Tolak
                    </button>
                </div>

                <p v-if="approvalForm.errors.keputusan" class="text-xs text-destructive">
                    {{ approvalForm.errors.keputusan }}
                </p>

                <div v-if="showCatatan">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Catatan <span class="text-destructive">*</span>
                    </label>
                    <textarea
                        v-model="approvalForm.catatan"
                        rows="3"
                        placeholder="Jelaskan alasan penolakan atau hal yang perlu direvisi..."
                        class="w-full rounded-md border border-input bg-card px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': approvalForm.errors.catatan }"
                    />
                    <p v-if="approvalForm.errors.catatan" class="mt-1 text-xs text-destructive">
                        {{ approvalForm.errors.catatan }}
                    </p>
                </div>

                <div v-else-if="approvalForm.keputusan === 'disetujui'">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Catatan <span class="text-muted-foreground">(opsional)</span>
                    </label>
                    <textarea
                        v-model="approvalForm.catatan"
                        rows="2"
                        placeholder="Catatan tambahan saat menyetujui (opsional)..."
                        class="w-full rounded-md border border-input bg-card px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>

                <PrimaryButton
                    v-if="approvalForm.keputusan"
                    type="button"
                    variant="primary"
                    size="lg"
                    :disabled="approvalForm.processing"
                    class="w-full sm:w-auto"
                    @click="submitDecision"
                >
                    {{ approvalForm.processing ? 'Memproses...' : 'Kirim Keputusan' }}
                </PrimaryButton>
            </div>
        </Section>

        <!-- ⭐ PANEL MULAI PENGADAAN (Pejabat Pengadaan saat disetujui) -->
        <Section
            v-if="canStartPengadaan"
            class="border-2"
            style="border-color: var(--color-brand-info); background-color: var(--color-brand-info-bg);"
        >
            <div class="space-y-4">
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-info);"
                    >
                        <ShoppingCart class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-eyebrow">Tindakan Pengadaan</div>
                        <h2 class="font-display text-xl font-semibold" style="color: var(--color-brand-info);">
                            Usulan Disetujui — Siap Dimulai Pengadaan
                        </h2>
                        <p class="mt-1 text-sm" style="color: var(--color-brand-info);">
                            Pilih metode pengadaan yang sesuai untuk memulai proses.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">
                            Metode Pengadaan <span class="text-destructive">*</span>
                        </label>
                        <select
                            v-model="pengadaanForm.metode"
                            class="h-11 w-full rounded-md border border-input bg-card px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        >
                            <option v-for="opt in metodeOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">
                            Tanggal Mulai <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="pengadaanForm.tanggal_mulai"
                            type="date"
                            class="h-11 w-full rounded-md border border-input bg-card px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-semibold">
                            Catatan <span class="text-muted-foreground">(opsional)</span>
                        </label>
                        <textarea
                            v-model="pengadaanForm.catatan"
                            rows="2"
                            placeholder="Catatan untuk proses pengadaan..."
                            class="w-full rounded-md border border-input bg-card px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        />
                    </div>
                </div>

                <PrimaryButton
                    type="button"
                    variant="primary"
                    size="lg"
                    :disabled="pengadaanForm.processing"
                    class="w-full sm:w-auto"
                    @click="startPengadaan"
                >
                    <ShoppingCart class="h-4 w-4" />
                    {{ pengadaanForm.processing ? 'Memproses...' : 'Mulai Pengadaan' }}
                </PrimaryButton>
            </div>
        </Section>

        <!-- Workflow timeline -->
        <Section title="Tahap Workflow" eyebrow="Status">
            <div class="overflow-x-auto">
                <div class="flex min-w-max items-stretch gap-2 lg:min-w-0">
                    <template v-for="(step, idx) in workflowSteps" :key="step.num">
                        <div
                            class="flex min-w-[140px] flex-1 items-center gap-2.5 rounded-md border p-3 transition"
                            :class="{
                                'border-primary bg-primary/5': stepStatus(idx) === 'active',
                                'border-[var(--color-brand-success)] bg-[var(--color-brand-success-bg)]': stepStatus(idx) === 'done',
                                'border-border bg-secondary/30': stepStatus(idx) === 'pending',
                            }"
                        >
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full font-display text-sm font-semibold"
                                :class="{
                                    'bg-primary text-primary-foreground': stepStatus(idx) === 'active',
                                    'bg-[var(--color-brand-success)] text-white': stepStatus(idx) === 'done',
                                    'bg-card text-muted-foreground ring-1 ring-border': stepStatus(idx) === 'pending',
                                }"
                            >
                                {{ stepStatus(idx) === 'done' ? '✓' : step.num }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div
                                    class="truncate text-sm font-semibold"
                                    :class="{
                                        'text-primary': stepStatus(idx) === 'active',
                                        'text-[var(--color-brand-success)]': stepStatus(idx) === 'done',
                                        'text-muted-foreground': stepStatus(idx) === 'pending',
                                    }"
                                >
                                    {{ step.name }}
                                </div>
                                <div class="truncate text-[10px] uppercase tracking-wider text-muted-foreground">
                                    {{ step.role }}
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="idx < workflowSteps.length - 1"
                            class="hidden shrink-0 items-center text-border lg:flex"
                        >
                            →
                        </div>
                    </template>
                </div>
            </div>
        </Section>

        <!-- Info grid: Pemohon + Anggaran -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <Section title="Informasi Pemohon" eyebrow="Identitas">
                <dl class="space-y-3.5">
                    <div class="flex items-start gap-3">
                        <User class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                        <div>
                            <dt class="text-eyebrow">Nama Pemohon</dt>
                            <dd class="text-sm font-medium">{{ usulan.pemohon?.name ?? '—' }}</dd>
                            <dd v-if="usulan.pemohon?.jabatan" class="text-xs text-muted-foreground">
                                {{ usulan.pemohon.jabatan }}
                            </dd>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <Building2 class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                        <div>
                            <dt class="text-eyebrow">Unit Kerja</dt>
                            <dd class="text-sm font-medium">{{ usulan.pemohon?.unit_kerja?.nama ?? '—' }}</dd>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <Calendar class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                        <div>
                            <dt class="text-eyebrow">Tanggal Usulan</dt>
                            <dd class="text-sm font-medium">{{ formatDate(usulan.tanggal_usulan) }}</dd>
                            <dd v-if="usulan.submitted_at" class="text-xs text-muted-foreground">
                                Diajukan {{ formatDateTime(usulan.submitted_at) }}
                            </dd>
                        </div>
                    </div>
                </dl>
            </Section>

            <Section title="Sumber Anggaran" eyebrow="Pendanaan">
                <div class="space-y-3.5">
                    <div class="flex items-start gap-3">
                        <Wallet class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                        <div>
                            <dt class="text-eyebrow">Kode Rekening</dt>
                            <dd class="font-mono text-sm font-medium">
                                {{ usulan.anggaran?.kode_rekening ?? '—' }}
                            </dd>
                            <dd class="text-sm text-muted-foreground">{{ usulan.anggaran?.nama_rekening }}</dd>
                        </div>
                    </div>
                    <div class="ml-7 grid grid-cols-2 gap-3 rounded-md border border-border bg-secondary/40 p-3">
                        <div>
                            <div class="text-eyebrow">Total Pagu</div>
                            <div class="mt-0.5 font-mono text-sm font-medium">
                                {{ usulan.anggaran ? formatRupiah(usulan.anggaran.pagu) : '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-eyebrow">Estimasi</div>
                            <div class="mt-0.5 font-mono text-sm font-semibold text-primary">
                                {{ formatRupiah(usulan.total_estimasi) }}
                            </div>
                        </div>
                    </div>
                </div>
            </Section>
        </div>

        <!-- Latar belakang & keterangan -->
        <Section
            v-if="usulan.latar_belakang || usulan.keterangan"
            title="Latar Belakang & Keterangan"
            eyebrow="Konteks"
        >
            <div v-if="usulan.latar_belakang" class="mb-4">
                <div class="text-eyebrow mb-1">Latar Belakang</div>
                <p class="whitespace-pre-line text-sm">{{ usulan.latar_belakang }}</p>
            </div>
            <div v-if="usulan.keterangan">
                <div class="text-eyebrow mb-1">Keterangan</div>
                <p class="whitespace-pre-line text-sm">{{ usulan.keterangan }}</p>
            </div>
        </Section>

        <!-- Rincian Items -->
        <Section
            title="Rincian Barang"
            eyebrow="Detail"
            :description="`${usulan.items.length} item diusulkan`"
        >
            <div class="-mx-6 -my-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-secondary/40 text-left text-[10px] uppercase tracking-widest text-muted-foreground">
                            <th class="px-6 py-3 font-semibold">#</th>
                            <th class="px-6 py-3 font-semibold">Barang</th>
                            <th class="px-6 py-3 font-semibold">Kategori</th>
                            <th class="px-6 py-3 text-right font-semibold">Jumlah</th>
                            <th class="px-6 py-3 text-right font-semibold">Harga Satuan</th>
                            <th class="px-6 py-3 text-right font-semibold">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="(item, idx) in usulan.items" :key="item.id">
                            <td class="px-6 py-4 align-top text-muted-foreground">{{ idx + 1 }}</td>
                            <td class="px-6 py-4 align-top">
                                <div class="font-medium">{{ item.nama_barang }}</div>
                                <div v-if="item.spesifikasi" class="mt-1 text-xs text-muted-foreground">
                                    {{ item.spesifikasi }}
                                </div>
                            </td>
                            <td class="px-6 py-4 align-top text-muted-foreground">
                                {{ item.kategori?.nama ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right align-top font-mono">
                                {{ item.jumlah }} {{ item.satuan }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right align-top font-mono text-xs">
                                {{ formatRupiah(item.harga_satuan_estimasi) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right align-top font-mono text-xs font-semibold">
                                {{ formatRupiah(item.subtotal) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-primary/30 bg-primary/5">
                            <td colspan="5" class="px-6 py-4 text-right">
                                <div class="text-eyebrow">Total Estimasi</div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right font-mono">
                                <span class="font-display text-lg font-bold text-primary">
                                    {{ formatRupiah(usulan.total_estimasi) }}
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </Section>

        <!-- Riwayat Approval -->
        <Section
            v-if="usulan.approvals.length > 0"
            title="Riwayat Approval"
            eyebrow="Audit Trail"
        >
            <ul class="space-y-4">
                <li
                    v-for="(approval, idx) in usulan.approvals"
                    :key="approval.id"
                    class="flex gap-3"
                >
                    <div class="relative flex flex-col items-center">
                        <div class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-primary" />
                        <div
                            v-if="idx < usulan.approvals.length - 1"
                            class="mt-1 w-px flex-1 bg-border"
                        />
                    </div>
                    <div class="flex-1 pb-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold">
                                {{ approval.approver?.name ?? 'Unknown' }}
                            </span>
                            <StatusBadge :status="approval.keputusan" />
                        </div>
                        <div v-if="approval.approver?.jabatan" class="text-xs text-muted-foreground">
                            {{ approval.approver.jabatan }}
                        </div>
                        <p
                            v-if="approval.catatan"
                            class="mt-2 rounded-md border border-border bg-secondary/40 p-3 text-sm"
                        >
                            {{ approval.catatan }}
                        </p>
                        <div class="mt-1 text-xs text-muted-foreground">
                            {{ formatDateTime(approval.tanggal_keputusan) }}
                        </div>
                    </div>
                </li>
            </ul>
        </Section>

        <!-- Empty state riwayat -->
        <EmptyState
            v-else-if="usulan.status === 'diajukan'"
            title="Menunggu Review"
            description="Usulan sedang menunggu keputusan dari PPTK"
            :icon="FileText"
        />
    </div>
</template>