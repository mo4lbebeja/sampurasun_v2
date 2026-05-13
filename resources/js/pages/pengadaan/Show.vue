<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    FileText,
    ShoppingCart,
    Upload,
    X,
    User as UserIcon,
    CheckCircle2,
    ExternalLink,
} from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import StatusBadge from '@/components/ev/StatusBadge.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';
import PenyediaSearchSelect from '@/components/ev/PenyediaSearchSelect.vue';

type PenyediaOption = {
    id: number;
    nama: string;
    jenis_badan?: string | null;
    npwp?: string | null;
    nama_pic?: string | null;
    alamat?: string | null;
    telepon?: string | null;
};

type Penyedia = {
    id: number;
    nama: string;
    jenis_badan: string | null;
    npwp: string | null;
    nama_pic: string | null;
    alamat?: string | null;
    telepon?: string | null;
};
type PejabatOption = {
    id: number;
    name: string;
    nip?: string | null;
    jabatan?: string | null;
    alamat?: string | null;
};
type Pengadaan = {
    id: number;
    no_pengadaan: string;
    metode: string;
    tanggal_mulai: string;
    tanggal_selesai: string | null;
    no_kontrak: string | null;
    tanggal_kontrak: string | null;
    nilai_kontrak: string | number | null;
    file_kontrak: string | null;
    file_hps: string | null;
    status: string;
    catatan: string | null;
    pejabat_penandatangan_id?: number | null;
    pejabat_penandatangan?: PejabatOption | null;
    kpa_penandatangan_id?: number | null;
    kpa_penandatangan?: PejabatOption | null;
    usulan: {
        id: number;
        no_usulan: string;
        judul: string;
        total_estimasi: string | number;
        status: string;
        pemohon: {
            id: number;
            name: string;
            unit_kerja: { id: number; nama: string } | null;
        } | null;
        items: Array<{
            id: number;
            nama_barang: string;
            spesifikasi: string | null;
            satuan: string;
            jumlah: number;
            harga_satuan_estimasi: string | number;
            subtotal: string | number;
            kategori: { id: number; nama: string } | null;
        }>;
    } | null;
    pejabat: { id: number; name: string; jabatan: string | null } | null;
    penyedia: Penyedia | null;
};

const props = withDefaults(
    defineProps<{
        pengadaan: Pengadaan;
        penyediaList?: PenyediaOption[];
        penyediaOptions?: PenyediaOption[];
        pejabatOptions?: PejabatOption[];
        kpaOptions?: PejabatOption[];
    }>(),
    {
        penyediaList: () => [],
        penyediaOptions: () => [],
        pejabatOptions: () => [],
        kpaOptions: () => [],
    },
);

// =====================
// Status & permission
// =====================
const isProses = computed(() => props.pengadaan.status === 'proses');
const isKontrak = computed(() => props.pengadaan.status === 'kontrak');
const isSelesai = computed(() => props.pengadaan.status === 'selesai');
const isBatal = computed(() => props.pengadaan.status === 'batal');

//const isEditable = computed(() => isProses.value);
const canInputKontrak = computed(() => isProses.value);

const canUploadDokumenKontrak = computed(() =>
    ['proses', 'kontrak', 'dokumen'].includes(props.pengadaan.status),
);

const isEditable = computed(() => canInputKontrak.value);

// =====================
// Form state
// =====================
const kontrakForm = useForm({
    penyedia_id: props.pengadaan.penyedia?.id ?? null as number | null,
    pejabat_penandatangan_id: props.pengadaan.pejabat_penandatangan?.id ?? null as number | null,
    kpa_penandatangan_id: props.pengadaan.kpa_penandatangan?.id ?? null as number | null,
    no_kontrak: props.pengadaan.no_kontrak ?? '',
    tanggal_kontrak: props.pengadaan.tanggal_kontrak ?? new Date().toISOString().slice(0, 10),
    tanggal_selesai: props.pengadaan.tanggal_selesai ?? '',
    nilai_kontrak: Number(props.pengadaan.nilai_kontrak) || 0,
    catatan: props.pengadaan.catatan ?? '',
    file_kontrak: null as File | null,
    file_hps: null as File | null,
});

const fileKontrakName = ref('');
const fileHpsName = ref('');

const onFileKontrak = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    kontrakForm.file_kontrak = file;
    fileKontrakName.value = file?.name ?? '';
};

const onFileHps = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    kontrakForm.file_hps = file;
    fileHpsName.value = file?.name ?? '';
};

const submitKontrak = () => {
    kontrakForm.post(`/pengadaan/${props.pengadaan.id}/kontrak`, {
        forceFormData: true,
        preserveScroll: true,
    });
};

const cancelForm = useForm({});

const cancelPengadaan = () => {
    if (!confirm('Yakin batalkan pengadaan ini? Usulan akan dikembalikan ke status disetujui.')) return;

    cancelForm.post(`/pengadaan/${props.pengadaan.id}/cancel`, {
        preserveScroll: true,
    });
};

// =====================
// Helpers
// =====================
const formatRupiah = (val: number | string | null) =>
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

const selisih = computed(() => {
    if (!props.pengadaan.usulan) return 0;

    const nilaiSekarang = isEditable.value
        ? Number(kontrakForm.nilai_kontrak)
        : Number(props.pengadaan.nilai_kontrak);

    return nilaiSekarang - Number(props.pengadaan.usulan.total_estimasi);
});

const bannerConfig = computed(() => {
    if (isProses.value) {
        return {
            tone: 'warning',
            icon: ShoppingCart,
            title: 'Input Detail Kontrak',
            description: 'Pilih penyedia, isi detail kontrak, dan upload dokumen. Setelah disimpan, pengadaan akan diteruskan ke Bagian UPBJ.',
        };
    }

    if (isKontrak.value) {
        return {
            tone: 'info',
            icon: CheckCircle2,
            title: 'Kontrak Aktif',
            description: 'Kontrak sudah ditandatangani. Pengadaan saat ini sedang dalam proses pengurusan dokumen UPBJ.',
        };
    }

    if (isSelesai.value) {
        return {
            tone: 'success',
            icon: CheckCircle2,
            title: 'Pengadaan Selesai',
            description: 'Seluruh proses pengadaan telah tuntas — kontrak, dokumen, pembayaran, dan evaluasi sudah selesai.',
        };
    }

    if (isBatal.value) {
        return {
            tone: 'muted',
            icon: X,
            title: 'Pengadaan Dibatalkan',
            description: 'Pengadaan ini telah dibatalkan. Usulan dikembalikan ke status disetujui.',
        };
    }

    return null;
});

const submitUploadDokumen = () => {
    const hasFileKontrak = kontrakForm.file_kontrak !== null;
    const hasFileHps = kontrakForm.file_hps !== null;

    if (!hasFileKontrak && !hasFileHps) {
        alert('Pilih minimal satu file terlebih dahulu: File Kontrak atau File HPS.');
        return;
    }

    const message = [
        'Anda akan menyimpan upload dokumen.',
        '',
        hasFileKontrak ? '- File Kontrak akan diupload/diganti.' : null,
        hasFileHps ? '- File HPS akan diupload/diganti.' : null,
        '',
        'Pastikan dokumen yang dipilih sudah benar.',
        'Lanjutkan proses upload?',
    ]
        .filter(Boolean)
        .join('\n');

    if (!confirm(message)) {
        return;
    }

    kontrakForm.post(`/pengadaan/${props.pengadaan.id}/kontrak`, {
        forceFormData: true,
        preserveScroll: true,
    });
};

</script>

<template>
    <Head :title="pengadaan.no_pengadaan" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- HEADER -->
        <div class="flex items-start gap-3">
            <Link
                href="/pengadaan"
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
                        <StatusBadge :status="pengadaan.status" />
                    </template>
                </PageHeader>
            </div>
        </div>

        <!-- BANNER (always visible, color depends on status) -->
        <Section
            v-if="bannerConfig"
            class="border-2"
            :style="`border-color: var(--color-brand-${bannerConfig.tone}); background-color: var(--color-brand-${bannerConfig.tone}-bg);`"
        >
            <div class="flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                    :style="`background-color: var(--color-brand-${bannerConfig.tone});`"
                >
                    <component :is="bannerConfig.icon" class="h-5 w-5" />
                </div>
                <div>
                    <div class="text-eyebrow">Status Pengadaan</div>
                    <h2
                        class="font-display text-xl font-semibold"
                        :style="`color: var(--color-brand-${bannerConfig.tone});`"
                    >
                        {{ bannerConfig.title }}
                    </h2>
                    <p
                        class="mt-1 text-sm"
                        :style="`color: var(--color-brand-${bannerConfig.tone});`"
                    >
                        {{ bannerConfig.description }}
                    </p>
                </div>
            </div>
        </Section>

        <!-- INFO BAR (always visible) -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <Section>
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <ShoppingCart class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Metode</div>
                        <div class="mt-0.5 text-sm font-semibold">{{ formatMetode(pengadaan.metode) }}</div>
                    </div>
                </div>
            </Section>
            <Section>
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-info);"
                    >
                        <Calendar class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Tanggal Mulai</div>
                        <div class="mt-0.5 text-sm font-semibold">{{ formatDate(pengadaan.tanggal_mulai) }}</div>
                    </div>
                </div>
            </Section>
            <Section>
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-copper);"
                    >
                        <UserIcon class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Pejabat Pengadaan</div>
                        <div class="mt-0.5 text-sm font-semibold">{{ pengadaan.pejabat?.name ?? '—' }}</div>
                    </div>
                </div>
            </Section>
        </div>

        <!-- ===== FORM atau READ-ONLY ===== -->
        <form @submit.prevent="submitKontrak" class="space-y-6">

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">

                <!-- LEFT COLUMN -->
                <div class="space-y-6 xl:col-span-8">

                <!-- PENYEDIA -->
                <Section title="Penyedia Terpilih" eyebrow="Vendor">
                    <!-- Form mode -->
                    <div v-if="isEditable">
                        <PenyediaSearchSelect
                            v-model="kontrakForm.penyedia_id"
                            :items="props.penyediaOptions ?? []"
                            :error="kontrakForm.errors.penyedia_id"
                            label="Vendor"
                            placeholder="Cari penyedia berdasarkan nama, NPWP, PIC, alamat, atau telepon..."
                        />

                        <p class="mt-1 text-xs text-muted-foreground">
                            Belum ada di daftar?
                            <Link href="/penyedia/create" class="text-primary hover:underline">
                                Tambah penyedia baru
                            </Link>
                        </p>
                    </div>

                    <!-- Read-only mode -->
                    <div v-else-if="pengadaan.penyedia">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <div class="text-eyebrow">Nama Penyedia</div>
                                <div class="mt-0.5 text-sm font-semibold">
                                    {{ pengadaan.penyedia.nama }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ pengadaan.penyedia.jenis_badan ?? '—' }}
                                </div>
                            </div>

                            <div v-if="pengadaan.penyedia.npwp">
                                <div class="text-eyebrow">NPWP</div>
                                <div class="mt-0.5 font-mono text-sm font-semibold">
                                    {{ pengadaan.penyedia.npwp }}
                                </div>
                            </div>

                            <div v-if="pengadaan.penyedia.nama_pic">
                                <div class="text-eyebrow">PIC</div>
                                <div class="mt-0.5 text-sm font-semibold">
                                    {{ pengadaan.penyedia.nama_pic }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty fallback -->
                    <div v-else class="text-sm italic text-muted-foreground">
                        Belum ada penyedia.
                    </div>
                </Section>

                    <!-- DETAIL KONTRAK -->
                    <Section title="Detail Kontrak" eyebrow="Kontrak">

                        <!-- Form mode (editable) -->
                        <div v-if="isEditable" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-semibold">
                                    Nomor Kontrak <span class="text-destructive">*</span>
                                </label>
                                <input
                                    v-model="kontrakForm.no_kontrak"
                                    type="text"
                                    placeholder="Contoh: 027/SPK/V/2026"
                                    class="h-11 w-full rounded-md border border-input bg-background px-3 font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    :class="{ 'border-destructive': kontrakForm.errors.no_kontrak }"
                                />
                                <p v-if="kontrakForm.errors.no_kontrak" class="mt-1 text-xs text-destructive">
                                    {{ kontrakForm.errors.no_kontrak }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-semibold">
                                    Tanggal Kontrak <span class="text-destructive">*</span>
                                </label>
                                <input
                                    v-model="kontrakForm.tanggal_kontrak"
                                    type="date"
                                    class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-semibold">
                                    Tanggal Selesai <span class="text-destructive">*</span>
                                </label>
                                <input
                                    v-model="kontrakForm.tanggal_selesai"
                                    type="date"
                                    class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-semibold">
                                    Nilai Kontrak <span class="text-destructive">*</span>
                                </label>
                                <input
                                    v-model.number="kontrakForm.nilai_kontrak"
                                    type="number"
                                    min="0"
                                    class="h-11 w-full rounded-md border border-input bg-background px-3 text-right font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                                <div class="mt-2 flex items-center justify-between rounded-md bg-secondary/40 p-3 text-xs">
                                    <span class="text-muted-foreground">
                                        Estimasi: {{ formatRupiah(pengadaan.usulan?.total_estimasi ?? 0) }}
                                    </span>
                                    <span
                                        class="font-mono font-semibold"
                                        :style="selisih > 0 ? 'color: var(--color-brand-danger);' : 'color: var(--color-brand-success);'"
                                    >
                                        {{ selisih > 0 ? '+' : '' }}{{ formatRupiah(selisih) }}
                                    </span>
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-semibold">
                                    Pejabat Penandatangan
                                </label>

                                <select
                                    v-model="kontrakForm.pejabat_penandatangan_id"
                                    class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    :class="{ 'border-destructive': kontrakForm.errors.pejabat_penandatangan_id }"
                                >
                                    <option :value="null">Pilih pejabat penandatangan</option>
                                    <option
                                        v-for="pejabat in props.pejabatOptions"
                                        :key="pejabat.id"
                                        :value="pejabat.id"
                                    >
                                        {{ pejabat.name }}
                                        <template v-if="pejabat.jabatan"> - {{ pejabat.jabatan }}</template>
                                        <template v-if="pejabat.alamat"> - {{ pejabat.alamat }}</template>                                   
                                    </option>
                                </select>

                                <p v-if="kontrakForm.errors.pejabat_penandatangan_id" class="mt-1 text-xs text-destructive">
                                    {{ kontrakForm.errors.pejabat_penandatangan_id }}
                                </p>

                                <p class="mt-1 text-xs text-muted-foreground">
                                    Pejabat ini akan digunakan sebagai penandatangan pada dokumen cetak.
                                </p>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-semibold">
                                    KPA/Direktur Penandatangan Penerimaan
                                </label>

                                <select
                                    v-model="kontrakForm.kpa_penandatangan_id"
                                    class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    :class="{ 'border-destructive': kontrakForm.errors.kpa_penandatangan_id }"
                                >
                                    <option :value="null">Pilih KPA/Direktur</option>
                                    <option
                                        v-for="kpa in props.kpaOptions"
                                        :key="kpa.id"
                                        :value="kpa.id"
                                    >
                                        {{ kpa.name }}
                                        <template v-if="kpa.jabatan"> - {{ kpa.jabatan }}</template>
                                    </option>
                                </select>

                                <p v-if="kontrakForm.errors.kpa_penandatangan_id" class="mt-1 text-xs text-destructive">
                                    {{ kontrakForm.errors.kpa_penandatangan_id }}
                                </p>

                                <p class="mt-1 text-xs text-muted-foreground">
                                    Digunakan sebagai penandatangan BAPRHP dan BAPP.
                                </p>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-semibold">Catatan</label>
                                <textarea
                                    v-model="kontrakForm.catatan"
                                    rows="3"
                                    placeholder="Catatan tambahan..."
                                    class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                            </div>
                        </div>

                        <!-- Read-only mode -->
                        <div v-else class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <div class="text-eyebrow">Nomor Kontrak</div>
                                    <div class="mt-0.5 font-mono text-sm font-semibold">
                                        {{ pengadaan.no_kontrak ?? '—' }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-eyebrow">Tanggal Kontrak</div>
                                    <div class="mt-0.5 text-sm font-semibold">
                                        {{ formatDate(pengadaan.tanggal_kontrak) }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-eyebrow">Tanggal Selesai</div>
                                    <div class="mt-0.5 text-sm font-semibold">
                                        {{ formatDate(pengadaan.tanggal_selesai) }}
                                    </div>
                                </div>
                                <div class="sm:col-span-2">
                                    <div class="text-eyebrow">Nilai Kontrak</div>
                                    <div class="mt-0.5 font-mono text-lg font-bold text-primary">
                                        {{ formatRupiah(pengadaan.nilai_kontrak) }}
                                    </div>
                                    <div class="mt-1 flex items-center gap-2 text-xs">
                                        <span class="text-muted-foreground">
                                            vs Estimasi: {{ formatRupiah(pengadaan.usulan?.total_estimasi ?? 0) }}
                                        </span>
                                        <span
                                            class="font-mono font-semibold"
                                            :style="selisih > 0 ? 'color: var(--color-brand-danger);' : 'color: var(--color-brand-success);'"
                                        >
                                            ({{ selisih > 0 ? '+' : '' }}{{ formatRupiah(selisih) }})
                                        </span>
                                    </div>
                                </div>
                                <div class="sm:col-span-2">
                                    <div class="text-eyebrow">Pejabat Penandatangan</div>
                                    <div class="mt-0.5 text-sm font-semibold">
                                        {{ pengadaan.pejabat_penandatangan?.name ?? '—' }}
                                    </div>
                                    <div v-if="pengadaan.pejabat_penandatangan?.jabatan" class="text-xs text-muted-foreground">
                                        {{ pengadaan.pejabat_penandatangan.jabatan }}
                                    </div>
                                    <div v-if="pengadaan.pejabat_penandatangan?.alamat" class="mt-1 text-xs text-muted-foreground">
                                        {{ pengadaan.pejabat_penandatangan.alamat }}
                                    </div>
                                </div>        
                                <div class="sm:col-span-2">
                                    <div class="text-eyebrow">KPA/Direktur Penandatangan</div>
                                    <div class="mt-0.5 text-sm font-semibold">
                                        {{ pengadaan.kpa_penandatangan?.name ?? '—' }}
                                    </div>
                                    <div v-if="pengadaan.kpa_penandatangan?.jabatan" class="text-xs text-muted-foreground">
                                        {{ pengadaan.kpa_penandatangan.jabatan }}
                                    </div>
                                    <div v-if="pengadaan.kpa_penandatangan?.alamat" class="mt-1 text-xs text-muted-foreground">
                                        {{ pengadaan.kpa_penandatangan.alamat }}
                                    </div>
                                </div>                        
                                <div v-if="pengadaan.catatan" class="sm:col-span-2">
                                    <div class="text-eyebrow">Catatan</div>
                                    <p class="mt-0.5 whitespace-pre-line text-sm">{{ pengadaan.catatan }}</p>
                                </div>
                            </div>
                        </div>
                    </Section>

                    <!-- ACTION BUTTONS (only for editable) -->
                    <div
                        v-if="isEditable"
                        class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
                    >
                        <PrimaryButton
                            type="button"
                            variant="danger"
                            class="sm:mr-auto"
                            @click="cancelPengadaan"
                        >
                            <X class="h-4 w-4" />
                            Batalkan Pengadaan
                        </PrimaryButton>

                        <PrimaryButton
                            type="submit"
                            variant="primary"
                            size="lg"
                            :disabled="kontrakForm.processing"
                        >
                            {{ kontrakForm.processing ? 'Menyimpan...' : 'Simpan Kontrak & Lanjut ke UPBJ' }}
                        </PrimaryButton>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div class="space-y-6 xl:col-span-4">

                    <!-- DOKUMEN -->
                    <Section title="Dokumen" eyebrow="File" description="Format PDF/DOC untuk kontrak dan PDF/Excel untuk HPS.">
                        <div class="space-y-4">
                            <div
                                v-if="canUploadDokumenKontrak && !isEditable"
                                class="rounded-md border border-[var(--color-brand-warning)] bg-[var(--color-brand-warning-bg)] p-3 text-xs"
                                style="color: var(--color-brand-warning);"
                            >
                                Pastikan file yang diupload adalah dokumen final dari sistem e-purchasing.
                                Jika memilih file baru, file dokumen sebelumnya akan diganti setelah disimpan.
                            </div>
                            <!-- File Kontrak -->
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold">File Kontrak</label>

                                <a
                                    v-if="pengadaan.file_kontrak"
                                    :href="`/storage/${pengadaan.file_kontrak}`"
                                    target="_blank"
                                    class="mb-2 flex items-center gap-2 rounded-md border border-border bg-card p-3 text-sm font-medium transition hover:border-primary hover:bg-muted/30"
                                >
                                    <FileText class="h-4 w-4 text-primary" />
                                    <span class="flex-1">Lihat file kontrak</span>
                                    <ExternalLink class="h-3 w-3 text-muted-foreground" />
                                </a>

                                <label
                                    v-if="canUploadDokumenKontrak"
                                    class="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-border bg-background px-3 py-2.5 text-sm transition hover:border-primary hover:bg-muted/30"
                                >
                                    <Upload class="h-4 w-4 text-muted-foreground" />
                                    <span class="flex-1 truncate text-muted-foreground">
                                        {{ fileKontrakName || (pengadaan.file_kontrak ? 'Ganti file...' : 'Pilih file...') }}
                                    </span>
                                    <input
                                        type="file"
                                        accept=".pdf,.doc,.docx"
                                        @change="onFileKontrak"
                                        class="hidden"
                                    />
                                </label>
                                <p v-if="!isEditable && !pengadaan.file_kontrak" class="text-xs text-muted-foreground italic">
                                    File belum diupload.
                                </p>
                            </div>

                            <!-- File HPS -->
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold">File HPS</label>

                                <a
                                    v-if="pengadaan.file_hps"
                                    :href="`/storage/${pengadaan.file_hps}`"
                                    target="_blank"
                                    class="mb-2 flex items-center gap-2 rounded-md border border-border bg-card p-3 text-sm font-medium transition hover:border-primary hover:bg-muted/30"
                                >
                                    <FileText class="h-4 w-4 text-primary" />
                                    <span class="flex-1">Lihat file HPS</span>
                                    <ExternalLink class="h-3 w-3 text-muted-foreground" />
                                </a>

                                    <label
                                        v-if="canUploadDokumenKontrak"
                                        class="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-border bg-background px-3 py-2.5 text-sm transition hover:border-primary hover:bg-muted/30"
                                    >
                                    <Upload class="h-4 w-4 text-muted-foreground" />
                                    <span class="flex-1 truncate text-muted-foreground">
                                        {{ fileHpsName || (pengadaan.file_hps ? 'Ganti file...' : 'Pilih file...') }}
                                    </span>
                                    <input
                                        type="file"
                                        accept=".pdf,.xls,.xlsx"
                                        @change="onFileHps"
                                        class="hidden"
                                    />
                                </label>
                                <p v-if="!isEditable && !pengadaan.file_hps" class="text-xs text-muted-foreground italic">
                                    File belum diupload.
                                </p>
                            </div>
                        </div>
                    </Section>

                    <div
                        v-if="canUploadDokumenKontrak && !isEditable"
                        class="flex justify-end"
                    >
                        <PrimaryButton
                            type="button"
                            variant="primary"
                            :disabled="kontrakForm.processing"
                            @click="submitUploadDokumen"
                        >
                            {{ kontrakForm.processing ? 'Mengupload...' : 'Simpan Upload Dokumen' }}
                        </PrimaryButton>
                    </div>

                    <!-- RINCIAN BARANG -->
                    <Section
                        v-if="pengadaan.usulan?.items.length"
                        title="Rincian Barang"
                        eyebrow="Dari Usulan"
                        :description="`${pengadaan.usulan.items.length} item`"
                    >
                        <div class="-mx-6 -my-6 divide-y divide-border">
                            <div
                                v-for="item in pengadaan.usulan.items"
                                :key="item.id"
                                class="px-6 py-3"
                            >
                                <div class="text-sm font-medium">{{ item.nama_barang }}</div>
                                <div v-if="item.spesifikasi" class="mt-1 text-xs text-muted-foreground">
                                    {{ item.spesifikasi }}
                                </div>
                                <div class="mt-2 flex items-center justify-between text-xs">
                                    <span class="text-muted-foreground">
                                        {{ item.jumlah }} {{ item.satuan }}
                                    </span>
                                    <span class="font-mono font-medium">{{ formatRupiah(item.subtotal) }}</span>
                                </div>
                            </div>
                        </div>
                    </Section>
                </div>
            </div>
        </form>
    </div>
</template>