<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Wallet,
    Upload,
    CheckCircle2,
    AlertCircle,
    Building2,
    Calendar,
    Hash,
    Calculator,
    Banknote,
    FileText,
} from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import StatusBadge from '@/components/ev/StatusBadge.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

type Pengadaan = {
    id: number;
    no_pengadaan: string;
    no_kontrak: string | null;
    nilai_kontrak: string;
    metode: string;
    status: string;
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
    penyedia: {
        id: number;
        nama: string;
        jenis_badan: string;
        npwp: string | null;
        nama_bank: string | null;
        rekening_bank: string | null;
        atas_nama_rekening: string | null;
    } | null;
    pejabat: { id: number; name: string } | null;
    dokumen_upbj: {
        id: number;
        no_bast: string | null;
        tanggal_bast: string | null;
    } | null;
};

type Pembayaran = {
    id: number;
    no_spm: string | null;
    no_sp2d: string | null;
    tanggal_bayar: string | null;
    metode_bayar: string;
    nilai_bayar: string;
    pajak_pph: string;
    pajak_ppn: string;
    nilai_bersih: string;
    bukti_bayar: string | null;
    file_spp: string | null;
    status: string;
    catatan: string | null;
};

const props = defineProps<{
    pengadaan: Pengadaan;
    pembayaran: Pembayaran;
}>();

const form = useForm({
    no_spm: props.pembayaran.no_spm ?? '',
    no_sp2d: props.pembayaran.no_sp2d ?? '',
    tanggal_bayar: props.pembayaran.tanggal_bayar ?? new Date().toISOString().slice(0, 10),
    metode_bayar: props.pembayaran.metode_bayar ?? 'transfer',
    nilai_bayar: Number(props.pembayaran.nilai_bayar) || Number(props.pengadaan.nilai_kontrak) || 0,
    pajak_pph: Number(props.pembayaran.pajak_pph) || 0,
    pajak_ppn: Number(props.pembayaran.pajak_ppn) || 0,
    catatan: props.pembayaran.catatan ?? '',
    bukti_bayar: null as File | null,
    file_spp: null as File | null,
});

const buktiName = ref<string>('');
const sppName   = ref<string>('');

const onBuktiChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    form.bukti_bayar = file;
    buktiName.value = file?.name ?? '';
};
const onSppChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    form.file_spp = file;
    sppName.value = file?.name ?? '';
};

const submitDraft = () => {
    form.post(`/pembayaran/${props.pengadaan.id}`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.bukti_bayar = null;
            buktiName.value = '';
            form.file_spp    = null;    // ← TAMBAHKAN
            sppName.value    = '';      // ← TAMBAHKAN
        },
    });
};

// Form complete (terpisah)
const completeForm = useForm({ complete: '' });
const completePembayaran = () => {
    if (!confirm('Yakin tandai pembayaran sebagai LUNAS?\n\nSetelah ini, pengadaan akan diteruskan ke Bagian Perencanaan untuk tahap evaluasi.')) return;
    completeForm.post(`/pembayaran/${props.pengadaan.id}/complete`, {
        preserveScroll: true,
    });
};

// =====================
// Auto-calc helpers
// =====================

// Hitung PPh 22 (1.5%) dari nilai bayar
const calcPph22 = () => {
    form.pajak_pph = Math.round(form.nilai_bayar * 0.015);
};

// Hitung PPN 11% dari DPP (DPP = nilai_bayar / 1.11 jika sudah include PPN)
const calcPpn11 = () => {
    // Asumsi: nilai_bayar adalah nilai termasuk PPN
    const dpp = form.nilai_bayar / 1.11;
    form.pajak_ppn = Math.round(form.nilai_bayar - dpp);
};

const resetPajak = () => {
    form.pajak_pph = 0;
    form.pajak_ppn = 0;
};

// Auto-calc nilai bersih (real-time)
const nilaiBersih = computed(() => {
    return Math.max(0,
        Number(form.nilai_bayar || 0)
        - Number(form.pajak_pph || 0)
        - Number(form.pajak_ppn || 0)
    );
});

// =====================
// Helpers
// =====================
const formatRupiah = (val: number | string) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(val) || 0);

const formatDate = (val: string | null) => {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};

const formatMetode = (val: string) =>
    val.split('_').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');

const metodeBayarOptions = [
    { value: 'transfer', label: 'Transfer Bank' },
    { value: 'cek',      label: 'Cek' },
    { value: 'tunai',    label: 'Tunai' },
    { value: 'giro',     label: 'Giro' },
];

const isLunas = computed(() => props.pembayaran.status === 'lunas');
const isReadOnly = computed(() => isLunas.value);

// Validasi readiness untuk complete
const canComplete = computed(() => {
    return !!form.no_spm
        && !!form.no_sp2d
        && !!form.tanggal_bayar
        && Number(form.nilai_bayar) > 0
        && (!!props.pembayaran.bukti_bayar || !!form.bukti_bayar);
});

const missingFields = computed(() => {
    const list: string[] = [];
    if (!form.no_spm) list.push('Nomor SPM');
    if (!form.no_sp2d) list.push('Nomor SP2D');
    if (!form.tanggal_bayar) list.push('Tanggal Bayar');
    if (Number(form.nilai_bayar) <= 0) list.push('Nilai Bayar');
    if (!props.pembayaran.bukti_bayar && !form.bukti_bayar) list.push('Bukti Bayar');
    return list;
});
</script>

<template>
    <Head :title="`Pembayaran ${pengadaan.no_pengadaan}`" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <!-- Header -->
        <div class="flex items-start gap-3">
            <Link
                href="/pembayaran"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-card transition hover:bg-muted"
            >
                <ArrowLeft class="h-4 w-4" />
            </Link>
            <div class="flex-1">
                <PageHeader
                    :title="pengadaan.usulan?.judul ?? '—'"
                    :eyebrow="pengadaan.no_pengadaan"
                >
                    <template #actions>
                        <StatusBadge
                            :status="pembayaran.status"
                            :label="pembayaran.status === 'lunas' ? 'Lunas' : pembayaran.status === 'diproses' ? 'Diproses' : 'Pending'"
                        />
                    </template>
                </PageHeader>
            </div>
        </div>

        <!-- Banner: Tindakan Diperlukan -->
        <Section
            v-if="!isLunas"
            class="border-2"
            style="border-color: var(--color-brand-info); background-color: var(--color-brand-info-bg);"
        >
            <div class="flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                    style="background-color: var(--color-brand-info);"
                >
                    <Wallet class="h-5 w-5" />
                </div>
                <div>
                    <div class="text-eyebrow">Tindakan Keuangan</div>
                    <h2 class="font-display text-xl font-semibold" style="color: var(--color-brand-info);">
                        Proses Pembayaran ke Penyedia
                    </h2>
                    <p class="mt-1 text-sm" style="color: var(--color-brand-info);">
                        Isi nomor SPM/SP2D, hitung pajak (PPh & PPN), upload bukti transfer, lalu klik "Tandai Lunas" untuk meneruskan ke Perencanaan.
                    </p>
                </div>
            </div>
        </Section>

        <!-- Banner: Sudah Lunas -->
        <Section
            v-else
            class="border-2"
            style="border-color: var(--color-brand-success); background-color: var(--color-brand-success-bg);"
        >
            <div class="flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                    style="background-color: var(--color-brand-success);"
                >
                    <CheckCircle2 class="h-5 w-5" />
                </div>
                <div>
                    <div class="text-eyebrow">Status</div>
                    <h2 class="font-display text-xl font-semibold" style="color: var(--color-brand-success);">
                        Pembayaran Telah Lunas
                    </h2>
                    <p class="mt-1 text-sm" style="color: var(--color-brand-success);">
                        Pengadaan ini sudah diteruskan ke Bagian Perencanaan untuk tahap evaluasi.
                    </p>
                </div>
            </div>
        </Section>

        <!-- Info Pengadaan + Penyedia (4 cards) -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Section>
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <Building2 class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Penyedia</div>
                        <div class="mt-0.5 truncate text-sm font-semibold">{{ pengadaan.penyedia?.nama ?? '—' }}</div>
                        <div v-if="pengadaan.penyedia?.npwp" class="font-mono text-xs text-muted-foreground">
                            {{ pengadaan.penyedia.npwp }}
                        </div>
                    </div>
                </div>
            </Section>
            <Section>
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-copper);"
                    >
                        <Hash class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">No. Kontrak</div>
                        <div class="mt-0.5 truncate font-mono text-sm font-semibold">
                            {{ pengadaan.no_kontrak ?? '—' }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ formatMetode(pengadaan.metode) }}
                        </div>
                    </div>
                </div>
            </Section>
            <Section>
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-info);"
                    >
                        <Banknote class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Bank Penyedia</div>
                        <div class="mt-0.5 truncate text-sm font-semibold">
                            {{ pengadaan.penyedia?.nama_bank ?? '—' }}
                        </div>
                        <div v-if="pengadaan.penyedia?.rekening_bank" class="truncate font-mono text-xs text-muted-foreground">
                            {{ pengadaan.penyedia.rekening_bank }}
                        </div>
                    </div>
                </div>
            </Section>
            <Section>
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-success);"
                    >
                        <FileText class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">No. BAST</div>
                        <div class="mt-0.5 truncate font-mono text-sm font-semibold">
                            {{ pengadaan.dokumen_upbj?.no_bast ?? '—' }}
                        </div>
                        <div v-if="pengadaan.dokumen_upbj?.tanggal_bast" class="text-xs text-muted-foreground">
                            {{ formatDate(pengadaan.dokumen_upbj.tanggal_bast) }}
                        </div>
                    </div>
                </div>
            </Section>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitDraft" class="space-y-6">
            <!-- Section 1: SPM & SP2D -->
            <Section title="SPM & SP2D" eyebrow="Langkah 1">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">
                            Nomor SPM <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.no_spm"
                            type="text"
                            placeholder="Contoh: 00123/SPM/2026"
                            :disabled="isReadOnly"
                            class="h-11 w-full rounded-md border border-input bg-background px-3 font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                            :class="{ 'border-destructive': form.errors.no_spm }"
                        />
                        <p v-if="form.errors.no_spm" class="mt-1 text-xs text-destructive">
                            {{ form.errors.no_spm }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">Surat Perintah Membayar</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">
                            Nomor SP2D <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.no_sp2d"
                            type="text"
                            placeholder="Contoh: 00456/SP2D/2026"
                            :disabled="isReadOnly"
                            class="h-11 w-full rounded-md border border-input bg-background px-3 font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                            :class="{ 'border-destructive': form.errors.no_sp2d }"
                        />
                        <p v-if="form.errors.no_sp2d" class="mt-1 text-xs text-destructive">
                            {{ form.errors.no_sp2d }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">Surat Perintah Pencairan Dana</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">
                            Tanggal Bayar <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model="form.tanggal_bayar"
                            type="date"
                            :disabled="isReadOnly"
                            class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                            :class="{ 'border-destructive': form.errors.tanggal_bayar }"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">
                            Metode Bayar <span class="text-destructive">*</span>
                        </label>
                        <select
                            v-model="form.metode_bayar"
                            :disabled="isReadOnly"
                            class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <option v-for="opt in metodeBayarOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                </div>
            </Section>

            <!-- Section 2: Kalkulasi Nilai & Pajak -->
            <Section title="Nilai & Pajak" eyebrow="Langkah 2">
                <template #actions>
                    <div class="flex flex-wrap gap-2">
                        <PrimaryButton
                            v-if="!isReadOnly"
                            type="button"
                            variant="secondary"
                            size="sm"
                            @click="calcPph22"
                        >
                            <Calculator class="h-3.5 w-3.5" />
                            PPh 22 (1.5%)
                        </PrimaryButton>
                        <PrimaryButton
                            v-if="!isReadOnly"
                            type="button"
                            variant="secondary"
                            size="sm"
                            @click="calcPpn11"
                        >
                            <Calculator class="h-3.5 w-3.5" />
                            PPN 11%
                        </PrimaryButton>
                        <PrimaryButton
                            v-if="!isReadOnly"
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="resetPajak"
                        >
                            Reset
                        </PrimaryButton>
                    </div>
                </template>

                <div class="space-y-4">
                    <!-- Nilai Bayar -->
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">
                            Nilai Bayar (Bruto) <span class="text-destructive">*</span>
                        </label>
                        <input
                            v-model.number="form.nilai_bayar"
                            type="number"
                            min="0"
                            :disabled="isReadOnly"
                            class="h-11 w-full rounded-md border border-input bg-background px-3 text-right font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                            :class="{ 'border-destructive': form.errors.nilai_bayar }"
                        />
                        <p v-if="form.errors.nilai_bayar" class="mt-1 text-xs text-destructive">
                            {{ form.errors.nilai_bayar }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Default = Nilai Kontrak: {{ formatRupiah(pengadaan.nilai_kontrak) }}
                        </p>
                    </div>

                    <!-- Pajak grid -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold">
                                PPh 22
                            </label>
                            <input
                                v-model.number="form.pajak_pph"
                                type="number"
                                min="0"
                                :disabled="isReadOnly"
                                class="h-11 w-full rounded-md border border-input bg-background px-3 text-right font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">Pajak Penghasilan barang (default 1.5%)</p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-semibold">
                                PPN
                            </label>
                            <input
                                v-model.number="form.pajak_ppn"
                                type="number"
                                min="0"
                                :disabled="isReadOnly"
                                class="h-11 w-full rounded-md border border-input bg-background px-3 text-right font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">Pajak Pertambahan Nilai (default 11%)</p>
                        </div>
                    </div>

                    <!-- Summary kalkulasi -->
                    <div class="rounded-md border border-primary/30 bg-primary/5 p-5">
                        <div class="text-eyebrow mb-3">Ringkasan Pembayaran</div>
                        <dl class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <dt class="text-muted-foreground">Nilai Bayar (Bruto)</dt>
                                <dd class="font-mono font-medium">{{ formatRupiah(form.nilai_bayar) }}</dd>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <dt class="text-muted-foreground">- PPh 22</dt>
                                <dd class="font-mono font-medium" style="color: var(--color-brand-danger);">
                                    -{{ formatRupiah(form.pajak_pph) }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <dt class="text-muted-foreground">- PPN</dt>
                                <dd class="font-mono font-medium" style="color: var(--color-brand-danger);">
                                    -{{ formatRupiah(form.pajak_ppn) }}
                                </dd>
                            </div>
                            <div class="border-t border-primary/20 pt-2">
                                <div class="flex items-center justify-between">
                                    <dt class="text-eyebrow">Nilai Bersih (Diterima Penyedia)</dt>
                                    <dd class="font-display text-xl font-bold text-primary">
                                        {{ formatRupiah(nilaiBersih) }}
                                    </dd>
                                </div>
                            </div>
                        </dl>
                    </div>
                </div>
            </Section>

            <!-- Section 3: Bukti Bayar -->
            <Section title="Bukti Pembayaran" eyebrow="Langkah 3">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        File Bukti Transfer <span class="text-destructive">*</span>
                    </label>

                    <!-- File existing -->
                    <a
                        v-if="pembayaran.bukti_bayar"
                        :href="`/storage/${pembayaran.bukti_bayar}`"
                        target="_blank"
                        class="mb-2 flex items-center gap-2 rounded-md border border-border bg-card p-3 text-sm font-medium transition hover:border-primary hover:bg-muted/30"
                    >
                        <FileText class="h-4 w-4 text-primary" />
                        <span>Lihat bukti yang sudah ter-upload</span>
                        <span class="ml-auto text-xs text-muted-foreground">↗</span>
                    </a>

                    <!-- Upload field -->
                    <label
                        v-if="!isReadOnly"
                        class="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-border bg-background px-3 py-2.5 text-sm transition hover:border-primary hover:bg-muted/30"
                    >
                        <Upload class="h-4 w-4 text-muted-foreground" />
                        <span class="flex-1 truncate text-muted-foreground">
                            {{ buktiName || (pembayaran.bukti_bayar ? 'Ganti file...' : 'Pilih file...') }}
                        </span>
                        <input
                            type="file"
                            accept=".pdf,.jpg,.png"
                            class="hidden"
                            @change="onBuktiChange"
                        />
                    </label>
                    <p v-if="form.errors.bukti_bayar" class="mt-1 text-xs text-destructive">
                        {{ form.errors.bukti_bayar }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Format: PDF, JPG, atau PNG. Maksimal 5MB.
                    </p>
                </div>

                <!-- Upload SPP -->
                <div class="mt-4">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Surat Permintaan Pembayaran (SPP)
                    </label>

                    <!-- File existing — sama persis dengan pola bukti_bayar -->
                    <a
                        v-if="pembayaran.file_spp"
                        :href="`/storage/${pembayaran.file_spp}`"
                        target="_blank"
                        class="mb-2 flex items-center gap-2 rounded-md border border-border bg-card p-3 text-sm font-medium transition hover:border-primary hover:bg-muted/30"
                    >
                        <FileText class="h-4 w-4 text-primary" />
                        <span>Lihat SPP yang sudah ter-upload</span>
                        <span class="ml-auto text-xs text-muted-foreground">↗</span>
                    </a>

                    <!-- Upload field -->
                    <label
                        v-if="!isReadOnly"
                        class="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-border bg-background px-3 py-2.5 text-sm transition hover:border-primary hover:bg-muted/30"
                    >
                        <Upload class="h-4 w-4 text-muted-foreground" />
                        <span class="flex-1 truncate text-muted-foreground">
                            {{ sppName || (pembayaran.file_spp ? 'Ganti file...' : 'Pilih file SPP...') }}
                        </span>
                        <input
                            type="file"
                            accept=".pdf,.doc,.docx"
                            class="hidden"
                            @change="onSppChange"
                        />
                    </label>

                    <p v-if="form.errors['file_spp']" class="mt-1 text-xs text-destructive">
                        {{ form.errors['file_spp'] }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Format: PDF atau DOC. Maksimal 5MB.
                    </p>
                </div>
            </Section>

            <!-- Action buttons -->
            <div v-if="!isReadOnly" class="space-y-3">
                <!-- Error complete -->
                <div
                    v-if="completeForm.errors['complete']"
                    class="flex items-start gap-3 rounded-md p-3 text-sm"
                    style="background-color: var(--color-brand-danger-bg); color: var(--color-brand-danger);"
                >
                    <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                    <div>{{ completeForm.errors['complete'] }}</div>
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <Link href="/pembayaran">
                        <PrimaryButton type="button" variant="secondary">
                            Kembali ke Daftar
                        </PrimaryButton>
                    </Link>

                    <PrimaryButton
                        type="submit"
                        variant="secondary"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Draft' }}
                    </PrimaryButton>

                    <PrimaryButton
                        type="button"
                        variant="primary"
                        size="lg"
                        :disabled="!canComplete || completeForm.processing"
                        @click="completePembayaran"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        {{ completeForm.processing ? 'Memproses...' : 'Tandai Lunas & Lanjut Evaluasi' }}
                    </PrimaryButton>
                </div>

                <p
                    v-if="!canComplete"
                    class="text-right text-xs text-muted-foreground"
                >
                    Belum lengkap: {{ missingFields.join(', ') }}
                </p>
            </div>

            <!-- Sudah lunas: tombol kembali saja -->
            <div v-else class="flex items-center justify-end">
                <Link href="/pembayaran">
                    <PrimaryButton type="button" variant="secondary">
                        Kembali ke Daftar
                    </PrimaryButton>
                </Link>
            </div>
        </form>
    </div>
</template>