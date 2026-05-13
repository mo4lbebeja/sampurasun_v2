<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Star,
    Upload,
    CheckCircle2,
    Building2,
    Hash,
    Calendar,
    Wallet,
    FileText,
    Award,
    AlertCircle,
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
    tanggal_kontrak: string | null;
    tanggal_selesai: string | null;
    usulan: {
        id: number;
        no_usulan: string;
        judul: string;
        total_estimasi: string;
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
    } | null;
    pejabat: { id: number; name: string } | null;
    pembayaran: {
        id: number;
        nilai_bersih: string;
        tanggal_bayar: string | null;
    } | null;
};

type Evaluasi = {
    id: number;
    tanggal_evaluasi: string;
    nilai_kinerja_penyedia: number;
    ketepatan_waktu: number;
    kesesuaian_spesifikasi: number;
    kualitas_barang: number;
    nilai_rata_rata: string;
    rekomendasi: string;
    catatan_evaluasi: string | null;
    rekomendasi_perbaikan: string | null;
    file_laporan: string | null;
    evaluator: { id: number; name: string } | null;
};

const props = defineProps<{
    pengadaan: Pengadaan;
    evaluasi: Evaluasi | null;
}>();

const isReadOnly = computed(() => !!props.evaluasi);

const form = useForm({
    tanggal_evaluasi: props.evaluasi?.tanggal_evaluasi ?? new Date().toISOString().slice(0, 10),
    nilai_kinerja_penyedia: props.evaluasi?.nilai_kinerja_penyedia ?? 0,
    ketepatan_waktu: props.evaluasi?.ketepatan_waktu ?? 0,
    kesesuaian_spesifikasi: props.evaluasi?.kesesuaian_spesifikasi ?? 0,
    kualitas_barang: props.evaluasi?.kualitas_barang ?? 0,
    rekomendasi: props.evaluasi?.rekomendasi ?? '',
    catatan_evaluasi: props.evaluasi?.catatan_evaluasi ?? '',
    rekomendasi_perbaikan: props.evaluasi?.rekomendasi_perbaikan ?? '',
    file_laporan: null as File | null,
});

const fileLaporanName = ref('');

const onFileChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    form.file_laporan = file;
    fileLaporanName.value = file?.name ?? '';
};

// =====================
// Auto-calc nilai rata-rata real-time
// =====================
const nilaiRataRata = computed(() => {
    const total =
        Number(form.nilai_kinerja_penyedia || 0) +
        Number(form.ketepatan_waktu || 0) +
        Number(form.kesesuaian_spesifikasi || 0) +
        Number(form.kualitas_barang || 0);
    return total > 0 ? Number((total / 4).toFixed(2)) : 0;
});

// Auto-suggest rekomendasi berdasarkan rata-rata
watch(nilaiRataRata, (val) => {
    if (isReadOnly.value || form.rekomendasi) return;
    if (val >= 4.5) form.rekomendasi = 'sangat_baik';
    else if (val >= 3.5) form.rekomendasi = 'baik';
    else if (val >= 2.5) form.rekomendasi = 'cukup';
    else if (val >= 1.5) form.rekomendasi = 'kurang';
    else if (val > 0) form.rekomendasi = 'tidak_direkomendasikan';
});

// =====================
// Submit
// =====================
const submitEvaluasi = () => {
    if (!confirm('Yakin submit evaluasi?\n\nEvaluasi yang sudah disimpan tidak bisa diubah lagi. Pengadaan akan ditandai SELESAI.')) return;

    form.post(`/evaluasi/${props.pengadaan.id}`, {
        forceFormData: true,
        preserveScroll: true,
    });
};

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

const formatRekomendasi = (val: string) =>
    val.split('_').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');

const ratingCriteria = [
    {
        field: 'nilai_kinerja_penyedia',
        label: 'Kinerja Penyedia',
        description: 'Komunikasi, responsivitas, dan profesionalisme',
    },
    {
        field: 'ketepatan_waktu',
        label: 'Ketepatan Waktu',
        description: 'Kesesuaian dengan jadwal kontrak',
    },
    {
        field: 'kesesuaian_spesifikasi',
        label: 'Kesesuaian Spesifikasi',
        description: 'Sesuai dengan yang disepakati di kontrak',
    },
    {
        field: 'kualitas_barang',
        label: 'Kualitas Barang/Jasa',
        description: 'Mutu produk akhir yang diterima',
    },
];

const ratingLabel = (val: number) => {
    if (val === 5) return 'Sangat Baik';
    if (val === 4) return 'Baik';
    if (val === 3) return 'Cukup';
    if (val === 2) return 'Kurang';
    if (val === 1) return 'Sangat Kurang';
    return 'Belum dinilai';
};

const rekomendasiOptions = [
    { value: 'sangat_baik',           label: 'Sangat Baik',           tone: 'success' },
    { value: 'baik',                  label: 'Baik',                  tone: 'info' },
    { value: 'cukup',                 label: 'Cukup',                 tone: 'warning' },
    { value: 'kurang',                label: 'Kurang',                tone: 'danger' },
    { value: 'tidak_direkomendasikan', label: 'Tidak Direkomendasikan', tone: 'danger' },
];

const setRating = (field: string, value: number) => {
    if (isReadOnly.value) return;
    (form as any)[field] = value;
};

const canSubmit = computed(() => {
    return form.nilai_kinerja_penyedia > 0
        && form.ketepatan_waktu > 0
        && form.kesesuaian_spesifikasi > 0
        && form.kualitas_barang > 0
        && form.rekomendasi !== ''
        && form.tanggal_evaluasi !== '';
});

const missingFields = computed(() => {
    const list: string[] = [];
    if (form.nilai_kinerja_penyedia === 0) list.push('Kinerja Penyedia');
    if (form.ketepatan_waktu === 0) list.push('Ketepatan Waktu');
    if (form.kesesuaian_spesifikasi === 0) list.push('Kesesuaian Spesifikasi');
    if (form.kualitas_barang === 0) list.push('Kualitas Barang');
    if (!form.rekomendasi) list.push('Rekomendasi');
    return list;
});
</script>

<template>
    <Head :title="`Evaluasi ${pengadaan.no_pengadaan}`" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <!-- Header -->
        <div class="flex items-start gap-3">
            <Link
                href="/evaluasi"
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
                            :status="isReadOnly ? 'selesai' : 'evaluasi'"
                            :label="isReadOnly ? 'Selesai Dievaluasi' : 'Menunggu Evaluasi'"
                        />
                    </template>
                </PageHeader>
            </div>
        </div>

        <!-- Banner: Tindakan Diperlukan -->
        <Section
            v-if="!isReadOnly"
            class="border-2"
            style="border-color: var(--color-brand-warning); background-color: var(--color-brand-warning-bg);"
        >
            <div class="flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                    style="background-color: var(--color-brand-warning);"
                >
                    <Star class="h-5 w-5" />
                </div>
                <div>
                    <div class="text-eyebrow">Tindakan Perencanaan</div>
                    <h2 class="font-display text-xl font-semibold" style="color: var(--color-brand-warning);">
                        Evaluasi Kinerja Penyedia
                    </h2>
                    <p class="mt-1 text-sm" style="color: var(--color-brand-warning);">
                        Berikan penilaian objektif untuk 4 kriteria di bawah. Evaluasi ini akan menjadi referensi penilaian penyedia di masa mendatang. <strong>Setelah disubmit, evaluasi tidak dapat diubah.</strong>
                    </p>
                </div>
            </div>
        </Section>

        <!-- Banner: Sudah Dievaluasi -->
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
                        Evaluasi Telah Selesai
                    </h2>
                    <p class="mt-1 text-sm" style="color: var(--color-brand-success);">
                        Pengadaan ini sudah selesai sepenuhnya pada {{ formatDate(evaluasi?.tanggal_evaluasi ?? null) }}.
                        Dievaluasi oleh <strong>{{ evaluasi?.evaluator?.name ?? '—' }}</strong>.
                    </p>
                </div>
            </div>
        </Section>

        <!-- Info Pengadaan (4 cards) -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Section>
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <Building2 class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Penyedia</div>
                        <div class="mt-0.5 truncate text-sm font-semibold">
                            {{ pengadaan.penyedia?.nama ?? '—' }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ pengadaan.penyedia?.jenis_badan }}
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
                        <Calendar class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Periode Kontrak</div>
                        <div class="mt-0.5 text-xs font-medium">
                            {{ formatDate(pengadaan.tanggal_kontrak) }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            sampai {{ formatDate(pengadaan.tanggal_selesai) }}
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
                        <Wallet class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-eyebrow">Nilai Bersih</div>
                        <div class="mt-0.5 truncate font-mono text-sm font-semibold text-primary">
                            {{ formatRupiah(pengadaan.pembayaran?.nilai_bersih ?? pengadaan.nilai_kontrak) }}
                        </div>
                        <div v-if="pengadaan.pembayaran?.tanggal_bayar" class="text-xs text-muted-foreground">
                            {{ formatDate(pengadaan.pembayaran.tanggal_bayar) }}
                        </div>
                    </div>
                </div>
            </Section>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitEvaluasi" class="space-y-6">
            <!-- Section 1: Tanggal Evaluasi -->
            <Section title="Identitas Evaluasi" eyebrow="Langkah 1">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Tanggal Evaluasi <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.tanggal_evaluasi"
                        type="date"
                        :disabled="isReadOnly"
                        class="h-11 w-full max-w-xs rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                        :class="{ 'border-destructive': form.errors.tanggal_evaluasi }"
                    />
                </div>
            </Section>

            <!-- Section 2: 4 Rating Criteria -->
            <Section
                title="Kriteria Penilaian"
                eyebrow="Langkah 2"
                description="Berikan penilaian skala 1 (sangat kurang) sampai 5 (sangat baik) untuk setiap kriteria"
            >
                <div class="space-y-4">
                    <div
                        v-for="criteria in ratingCriteria"
                        :key="criteria.field"
                        class="rounded-md border border-border bg-secondary/30 p-4"
                    >
                        <div class="mb-3 flex items-start justify-between gap-3">
                            <div>
                                <div class="font-display text-base font-semibold">{{ criteria.label }}</div>
                                <div class="text-xs text-muted-foreground">{{ criteria.description }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-display text-2xl font-bold text-primary">
                                    {{ (form as any)[criteria.field] || '—' }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ ratingLabel((form as any)[criteria.field]) }}
                                </div>
                            </div>
                        </div>

                        <!-- Rating buttons 1-5 -->
                        <div class="grid grid-cols-5 gap-1.5">
                            <button
                                v-for="n in 5"
                                :key="n"
                                type="button"
                                :disabled="isReadOnly"
                                class="flex h-12 items-center justify-center rounded-md border-2 font-display text-lg font-semibold transition disabled:cursor-not-allowed"
                                :class="
                                    (form as any)[criteria.field] === n
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : isReadOnly
                                            ? 'border-border bg-card text-muted-foreground opacity-60'
                                            : 'border-border bg-card hover:border-primary hover:bg-primary/5'
                                "
                                @click="setRating(criteria.field, n)"
                            >
                                {{ n }}
                            </button>
                        </div>

                        <p
                            v-if="form.errors[criteria.field as keyof typeof form.errors]"
                            class="mt-2 text-xs text-destructive"
                        >
                            {{ form.errors[criteria.field as keyof typeof form.errors] }}
                        </p>
                    </div>
                </div>

                <!-- Summary nilai rata-rata -->
                <div class="mt-4 rounded-md border border-primary/30 bg-primary/5 p-5">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                <Award class="h-6 w-6" />
                            </div>
                            <div>
                                <div class="text-eyebrow">Nilai Rata-rata</div>
                                <div class="text-xs text-muted-foreground">Otomatis dihitung dari 4 kriteria</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-display text-3xl font-bold text-primary">
                                {{ nilaiRataRata > 0 ? nilaiRataRata.toFixed(2) : '—' }}
                            </div>
                            <div class="text-xs text-muted-foreground">/ 5.00</div>
                        </div>
                    </div>
                </div>
            </Section>

            <!-- Section 3: Rekomendasi -->
            <Section
                title="Rekomendasi"
                eyebrow="Langkah 3"
                description="Tingkat rekomendasi penyedia ini untuk pengadaan di masa depan"
            >
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-5">
                    <button
                        v-for="opt in rekomendasiOptions"
                        :key="opt.value"
                        type="button"
                        :disabled="isReadOnly"
                        class="flex flex-col items-center justify-center rounded-md border-2 px-4 py-3 text-sm font-semibold transition disabled:cursor-not-allowed"
                        :class="[
                            form.rekomendasi === opt.value
                                ? `border-[var(--color-brand-${opt.tone})] bg-[var(--color-brand-${opt.tone}-bg)] text-[var(--color-brand-${opt.tone})]`
                                : isReadOnly
                                    ? 'border-border bg-card text-muted-foreground opacity-60'
                                    : 'border-border bg-card hover:border-primary'
                        ]"
                        @click="!isReadOnly && (form.rekomendasi = opt.value)"
                    >
                        {{ opt.label }}
                    </button>
                </div>

                <p v-if="form.errors.rekomendasi" class="mt-2 text-xs text-destructive">
                    {{ form.errors.rekomendasi }}
                </p>
            </Section>

            <!-- Section 4: Catatan & Rekomendasi Perbaikan -->
            <Section title="Catatan Tambahan" eyebrow="Langkah 4 (opsional)">
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">Catatan Evaluasi</label>
                        <textarea
                            v-model="form.catatan_evaluasi"
                            rows="3"
                            placeholder="Catatan umum tentang pelaksanaan pengadaan dan kinerja penyedia..."
                            :disabled="isReadOnly"
                            class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">Rekomendasi Perbaikan</label>
                        <textarea
                            v-model="form.rekomendasi_perbaikan"
                            rows="3"
                            placeholder="Saran perbaikan untuk pengadaan serupa di masa depan..."
                            :disabled="isReadOnly"
                            class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                        />
                    </div>

                    <!-- File laporan (existing display) -->
                    <div v-if="evaluasi?.file_laporan">
                        <label class="mb-1.5 block text-sm font-semibold">File Laporan</label>
                        <a
                            :href="`/storage/${evaluasi.file_laporan}`"
                            target="_blank"
                            class="flex items-center gap-2 rounded-md border border-border bg-card p-3 text-sm font-medium transition hover:border-primary hover:bg-muted/30"
                        >
                            <FileText class="h-4 w-4 text-primary" />
                            <span>Lihat laporan evaluasi</span>
                            <span class="ml-auto text-xs text-muted-foreground">↗</span>
                        </a>
                    </div>

                    <!-- File upload (kalau belum readonly) -->
                    <div v-if="!isReadOnly">
                        <label class="mb-1.5 block text-sm font-semibold">
                            Upload Laporan (opsional)
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-border bg-background px-3 py-2.5 text-sm transition hover:border-primary hover:bg-muted/30">
                            <Upload class="h-4 w-4 text-muted-foreground" />
                            <span class="flex-1 truncate text-muted-foreground">
                                {{ fileLaporanName || 'Pilih file...' }}
                            </span>
                            <input
                                type="file"
                                accept=".pdf,.doc,.docx"
                                class="hidden"
                                @change="onFileChange"
                            />
                        </label>
                        <p class="mt-1 text-xs text-muted-foreground">Format: PDF, DOC, DOCX. Maksimal 5MB.</p>
                    </div>
                </div>
            </Section>

            <!-- Read-only summary tampil kalau sudah dievaluasi -->
            <Section
                v-if="isReadOnly && evaluasi"
                title="Hasil Evaluasi"
                eyebrow="Audit Trail"
            >
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <div class="text-eyebrow">Evaluator</div>
                        <div class="mt-0.5 text-sm font-semibold">{{ evaluasi.evaluator?.name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-eyebrow">Tanggal Evaluasi</div>
                        <div class="mt-0.5 text-sm font-semibold">{{ formatDate(evaluasi.tanggal_evaluasi) }}</div>
                    </div>
                    <div>
                        <div class="text-eyebrow">Nilai Rata-rata Final</div>
                        <div class="mt-0.5 font-display text-xl font-bold text-primary">
                            {{ Number(evaluasi.nilai_rata_rata).toFixed(2) }} / 5
                        </div>
                    </div>
                    <div>
                        <div class="text-eyebrow">Rekomendasi</div>
                        <div class="mt-0.5 text-sm font-semibold">
                            {{ formatRekomendasi(evaluasi.rekomendasi) }}
                        </div>
                    </div>
                </div>
            </Section>

            <!-- Action buttons -->
            <div v-if="!isReadOnly" class="space-y-3">
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <Link href="/evaluasi">
                        <PrimaryButton type="button" variant="secondary">
                            Kembali ke Daftar
                        </PrimaryButton>
                    </Link>

                    <PrimaryButton
                        type="submit"
                        variant="primary"
                        size="lg"
                        :disabled="!canSubmit || form.processing"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        {{ form.processing ? 'Menyimpan...' : 'Submit Evaluasi & Tandai Selesai' }}
                    </PrimaryButton>
                </div>

                <p
                    v-if="!canSubmit"
                    class="text-right text-xs text-muted-foreground"
                >
                    Belum lengkap: {{ missingFields.join(', ') }}
                </p>
            </div>

            <div v-else class="flex items-center justify-end">
                <Link href="/evaluasi">
                    <PrimaryButton type="button" variant="secondary">
                        Kembali ke Daftar
                    </PrimaryButton>
                </Link>
            </div>
        </form>
    </div>
</template>