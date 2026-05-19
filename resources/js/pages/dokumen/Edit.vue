<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    FileText,
    Upload,
    CheckCircle2,
    AlertCircle,
    Building2,
    Wallet,
    Hash,
    ShieldCheck,
} from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import StatusBadge from '@/components/ev/StatusBadge.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';
import NomorDokumenPengadaanCard from '@/components/ev/NomorDokumenPengadaanCard.vue';
// ==================== TYPES ====================

type UnitKerja = {
    id: number;
    nama: string;
};

type Pemohon = {
    id: number;
    name: string;
    unit_kerja: UnitKerja | null;
};

type Usulan = {
    id: number;
    no_usulan: string;
    judul: string;
    total_estimasi: string;
    status: string;
    pemohon: Pemohon | null;
};

type Penyedia = {
    id: number;
    nama: string;
    jenis_badan: string;
    npwp: string | null;
};

type Pejabat = {
    id: number;
    name: string;
};

type Pengadaan = {
    id: number;
    no_pengadaan: string;
    no_kontrak: string | null;
    tanggal_kontrak: string | null;
    tanggal_selesai: string | null;
    nilai_kontrak: string;
    metode: string;
    status: string;
    usulan: Usulan | null;
    penyedia: Penyedia | null;
    pejabat: Pejabat | null;
    dokumen_pengadaan?: DokumenPengadaan[];
};

type Dokumen = {
    id: number;
    no_bast: string | null;
    tanggal_bast: string | null;
    file_bast: string | null;
    file_invoice: string | null;
    file_faktur_pajak: string | null;
    file_kuitansi: string | null;
    file_spp: string | null;
    is_complete: boolean;
    keterangan: string | null;
    completed_at: string | null;
};

type DokumenPengadaan = {
    id: number;
    jenis: string;
    nama_dokumen: string;
    nomor: string;
    tanggal: string | null;
};

// ==================== PROPS ====================

const props = defineProps<{
    pengadaan: Pengadaan;
    dokumen: Dokumen;
}>();

// ==================== FORM ====================

const form = useForm({
    no_bast: props.dokumen.no_bast ?? '',
    tanggal_bast: props.dokumen.tanggal_bast ?? '',
    keterangan: props.dokumen.keterangan ?? '',
    file_bast: null as File | null,
    file_invoice: null as File | null,
    file_faktur_pajak: null as File | null,
    file_kuitansi: null as File | null,
});

// State untuk display nama file yang baru dipilih
const selectedFileNames = ref<Record<string, string>>({});

// Type untuk field file
type FileField = 'file_bast' | 'file_invoice' | 'file_faktur_pajak' | 'file_kuitansi';

const onFileChange = (field: FileField, e: Event) => {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;

    // Update form data secara type-safe
    if (field === 'file_bast') form.file_bast = file;
    else if (field === 'file_invoice') form.file_invoice = file;
    else if (field === 'file_faktur_pajak') form.file_faktur_pajak = file;
    else if (field === 'file_kuitansi') form.file_kuitansi = file;

    selectedFileNames.value[field] = file?.name ?? '';
};

const submitDraft = () => {
    if (generatedBastNumber.value) {
        form.no_bast = generatedBastNumber.value;
    }

    form.post(`/dokumen/${props.pengadaan.id}`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.file_bast = null;
            form.file_invoice = null;
            form.file_faktur_pajak = null;
            form.file_kuitansi = null;
            selectedFileNames.value = {};
        },
    });
};

// Form untuk complete
const completeForm = useForm<{
    complete: string;
}>({
    complete: '',
});

const completePengadaan = () => {
    if (!generatedBastNumber.value) {
        alert('Nomor BAST belum dibuat. Silakan klik Generate Nomor Dokumen pada panel kanan terlebih dahulu.');
        return;
    }

    if (!allUploaded.value) {
        alert('Lengkapi semua dokumen wajib terlebih dahulu sebelum melanjutkan ke Keuangan.');
        return;
    }

    if (!confirm('Yakin selesaikan tahap dokumen?\n\nSetelah ini, pengadaan akan diteruskan ke Bagian Keuangan untuk pembayaran.')) return;

    completeForm.post(`/dokumen/${props.pengadaan.id}/complete`, {
        preserveScroll: true,
    });
};

// ==================== HELPERS ====================

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

// ==================== COMPUTED ====================
const generatedBast = computed(() => {
    return props.pengadaan.dokumen_pengadaan?.find((item) => item.jenis === 'bast') ?? null;
});

const generatedBastNumber = computed(() => {
    return generatedBast.value?.nomor ?? '';
});

const dokumenList = computed(() => [
    {
        field: 'file_bast' as FileField,
        label: 'BAST',
        fullLabel: 'Berita Acara Serah Terima',
        accept: '.pdf,.doc,.docx,.jpg,.png',
        existing: props.dokumen.file_bast,
    },
    {
        field: 'file_invoice' as FileField,
        label: 'Invoice',
        fullLabel: 'Invoice / Tagihan',
        accept: '.pdf,.jpg,.png',
        existing: props.dokumen.file_invoice,
    },
    {
        field: 'file_faktur_pajak' as FileField,
        label: 'Faktur Pajak',
        fullLabel: 'Faktur Pajak',
        accept: '.pdf,.jpg,.png',
        existing: props.dokumen.file_faktur_pajak,
    },
    {
        field: 'file_kuitansi' as FileField,
        label: 'Kuitansi',
        fullLabel: 'Kuitansi Pembayaran',
        accept: '.pdf,.jpg,.png',
        existing: props.dokumen.file_kuitansi,
    },
]);

const completedCount = computed(
    () => dokumenList.value.filter((d) => d.existing).length,
);

const completionPercent = computed(() =>
    Math.round((completedCount.value / dokumenList.value.length) * 100),
);

const allUploaded = computed(() => completedCount.value === dokumenList.value.length);
const canComplete = computed(() => allUploaded.value && !!generatedBastNumber.value);
</script>
<template>
    <Head :title="`Dokumen ${pengadaan.no_pengadaan}`" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <!-- ==================== HEADER ==================== -->
        <div class="flex items-start gap-3">
            <Link
                href="/dokumen"
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
                            :status="dokumen.is_complete ? 'selesai' : 'dalam_pengadaan'"
                            :label="dokumen.is_complete ? 'Selesai' : 'Sedang Diproses'"
                        />
                    </template>
                </PageHeader>
            </div>
        </div>

        <!-- ==================== BANNER: TINDAKAN DIPERLUKAN ==================== -->
        <Section
            v-if="!dokumen.is_complete"
            class="border-2"
            style="border-color: var(--color-brand-info); background-color: var(--color-brand-info-bg);"
        >
            <div class="flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                    style="background-color: var(--color-brand-info);"
                >
                    <ShieldCheck class="h-5 w-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-eyebrow">Tindakan UPBJ</div>
                    <h2 class="font-display text-xl font-semibold" style="color: var(--color-brand-info);">
                        Lengkapi Dokumen Pengadaan
                    </h2>
                    <p class="mt-1 text-sm" style="color: var(--color-brand-info);">
                        Upload 5 dokumen wajib dan isi nomor BAST. Setelah lengkap, klik
                        "Selesaikan & Lanjut ke Keuangan" untuk meneruskan ke tahap pembayaran.
                    </p>

                    <!-- Progress bar -->
                    <div class="mt-4 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span style="color: var(--color-brand-info);">
                                <span class="font-mono font-semibold">{{ completedCount }}</span> dari
                                <span class="font-mono font-semibold">{{ dokumenList.length }}</span> dokumen ter-upload
                            </span>
                            <span class="font-mono font-semibold" style="color: var(--color-brand-info);">
                                {{ completionPercent }}%
                            </span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-card">
                            <div
                                class="h-full rounded-full transition-all"
                                :style="`width: ${completionPercent}%; background-color: var(--color-brand-info);`"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </Section>

        <!-- ==================== BANNER: SUDAH SELESAI ==================== -->
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
                        Dokumen Telah Lengkap
                    </h2>
                    <p class="mt-1 text-sm" style="color: var(--color-brand-success);">
                        Pengadaan ini sudah diteruskan ke Bagian Keuangan untuk proses pembayaran.
                    </p>
                </div>
            </div>
        </Section>
                <!-- ==================== INFO PENGADAAN (3 CARDS) ==================== -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <!-- Card: Penyedia -->
            <Section>
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <Building2 class="h-4 w-4" />
                    </div>
                    <div>
                        <div class="text-eyebrow">Penyedia</div>
                        <div class="mt-0.5 text-sm font-semibold">{{ pengadaan.penyedia?.nama ?? '—' }}</div>
                        <div v-if="pengadaan.penyedia?.npwp" class="font-mono text-xs text-muted-foreground">
                            {{ pengadaan.penyedia.npwp }}
                        </div>
                    </div>
                </div>
            </Section>

            <!-- Card: Nomor Kontrak -->
            <Section>
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-copper);"
                    >
                        <Hash class="h-4 w-4" />
                    </div>
                    <div>
                        <div class="text-eyebrow">Nomor Kontrak</div>
                        <div class="mt-0.5 font-mono text-sm font-semibold">{{ pengadaan.no_kontrak ?? '—' }}</div>
                        <div class="text-xs text-muted-foreground">
                            {{ formatMetode(pengadaan.metode) }}
                        </div>
                    </div>
                </div>
            </Section>

            <!-- Card: Nilai Kontrak -->
            <Section>
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-info);"
                    >
                        <Wallet class="h-4 w-4" />
                    </div>
                    <div>
                        <div class="text-eyebrow">Nilai Kontrak</div>
                        <div class="mt-0.5 font-mono text-sm font-semibold text-primary">
                            {{ formatRupiah(pengadaan.nilai_kontrak) }}
                        </div>
                    </div>
                </div>
            </Section>
        </div>

        <!-- ==================== MAIN LAYOUT ==================== -->
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <!-- ==================== LEFT COLUMN: FORM INPUT ==================== -->
            <div class="space-y-6 xl:col-span-8">
                <form @submit.prevent="submitDraft" class="space-y-6">
                    <!-- BAST Info -->
                    <Section title="Informasi BAST" eyebrow="Langkah 1">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold">
                                    Nomor BAST <span class="text-destructive">*</span>
                                </label>
                                <input
                                    v-model="form.no_bast"
                                    type="text"
                                    placeholder="Contoh: 027/BAST/V/2026"
                                    :disabled="dokumen.is_complete"
                                    class="h-11 w-full rounded-md border border-input bg-background px-3 font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                                    :class="{ 'border-destructive': form.errors.no_bast }"
                                />
                                <p v-if="form.errors.no_bast" class="mt-1 text-xs text-destructive">
                                    {{ form.errors.no_bast }}
                                </p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold">Tanggal BAST</label>
                                <input
                                    v-model="form.tanggal_bast"
                                    type="date"
                                    :disabled="dokumen.is_complete"
                                    class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                                />
                            </div>
                        </div>
                    </Section>

                    <!-- Upload Dokumen -->
                    <Section
                        title="Dokumen Wajib"
                        eyebrow="Langkah 2"
                        :description="`${completedCount} dari ${dokumenList.length} dokumen ter-upload`"
                    >
                        <div class="space-y-3">
                            <div
                                v-for="dok in dokumenList"
                                :key="dok.field"
                                class="rounded-md border p-4 transition"
                                :class="dok.existing ? 'border-[var(--color-brand-success)] bg-[var(--color-brand-success-bg)]' : 'border-border bg-secondary/30'"
                            >
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <!-- Status icon + label -->
                                    <div class="flex flex-1 items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md"
                                            :class="dok.existing ? 'bg-[var(--color-brand-success)] text-white' : 'bg-card text-muted-foreground ring-1 ring-border'"
                                        >
                                            <CheckCircle2 v-if="dok.existing" class="h-5 w-5" />
                                            <FileText v-else class="h-5 w-5" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-display text-base font-semibold">{{ dok.label }}</span>
                                                <span
                                                    v-if="dok.existing"
                                                    class="badge-status badge-success"
                                                >
                                                    Tersimpan
                                                </span>
                                                <span
                                                    v-else
                                                    class="badge-status badge-warning"
                                                >
                                                    Belum
                                                </span>
                                            </div>
                                            <div class="text-xs text-muted-foreground">{{ dok.fullLabel }}</div>

                                            <!-- Link file existing -->
                                            <a
                                                v-if="dok.existing"
                                                :href="`/storage/${dok.existing}`"
                                                target="_blank"
                                                class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline"
                                            >
                                                Lihat file ↗
                                            </a>

                                            <!-- File baru dipilih -->
                                            <div
                                                v-if="selectedFileNames[dok.field]"
                                                class="mt-1 text-xs"
                                                style="color: var(--color-brand-info);"
                                            >
                                                Akan diunggah:
                                                <span class="font-medium">{{ selectedFileNames[dok.field] }}</span>
                                            </div>

                                            <p v-if="form.errors[dok.field]" class="mt-1 text-xs text-destructive">
                                                {{ form.errors[dok.field] }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Upload button -->
                                    <label
                                        v-if="!dokumen.is_complete"
                                        class="flex shrink-0 cursor-pointer items-center gap-2 rounded-md border border-dashed border-border bg-card px-4 py-2.5 text-sm font-medium transition hover:border-primary hover:bg-muted/30"
                                    >
                                        <Upload class="h-4 w-4" />
                                        {{ dok.existing ? 'Ganti' : 'Upload' }}
                                        <input
                                            type="file"
                                            :accept="dok.accept"
                                            class="hidden"
                                            @change="onFileChange(dok.field, $event)"
                                        />
                                    </label>
                                </div>
                            </div>
                        </div>
                    </Section>

                    <!-- Keterangan -->
                    <Section title="Keterangan" eyebrow="Langkah 3 (opsional)">
                        <textarea
                            v-model="form.keterangan"
                            rows="3"
                            placeholder="Catatan tambahan tentang dokumen pengadaan ini..."
                            :disabled="dokumen.is_complete"
                            class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                        />
                    </Section>

                    <!-- Action buttons -->
                    <div v-if="!dokumen.is_complete" class="space-y-3">
                        <!-- Error message kalau gagal complete -->
                        <div
                            v-if="completeForm.errors['complete']"
                            class="flex items-start gap-3 rounded-md p-3 text-sm"
                            style="background-color: var(--color-brand-danger-bg); color: var(--color-brand-danger);"
                        >
                            <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                            <div>{{ completeForm.errors['complete'] }}</div>
                        </div>

                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                            <Link href="/dokumen">
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
                                @click="completePengadaan"
                            >
                                <CheckCircle2 class="h-4 w-4" />
                                {{ completeForm.processing ? 'Memproses...' : 'Selesaikan & Lanjut ke Keuangan' }}
                            </PrimaryButton>
                        </div>

                        <p
                            v-if="!canComplete"
                            class="text-right text-xs text-muted-foreground"
                        >
                            {{
                                !allUploaded
                                    ? 'Tombol "Selesaikan" aktif setelah semua 5 dokumen ter-upload.'
                                    : 'Generate nomor dokumen terlebih dahulu untuk mendapatkan No BAST.'
                            }}
                        </p>
                    </div>

                    <!-- Info kalau sudah complete -->
                    <div
                        v-else
                        class="flex items-center justify-end gap-3"
                    >
                        <Link href="/dokumen">
                            <PrimaryButton type="button" variant="secondary">
                                Kembali ke Daftar
                            </PrimaryButton>
                        </Link>
                    </div>
                </form>
            </div>

            <!-- ==================== RIGHT COLUMN: NOMOR DOKUMEN ==================== -->
            <div class="space-y-6 xl:col-span-4">
                <NomorDokumenPengadaanCard
                    :pengadaan-id="pengadaan.id"
                    :dokumen-pengadaan="pengadaan.dokumen_pengadaan ?? []"
                />
            </div>
        </div>
    </div>
</template>