<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft, Info } from 'lucide-vue-next';
import PageHeader       from '@/components/ev/PageHeader.vue';
import Section          from '@/components/ev/Section.vue';
import PrimaryButton    from '@/components/ev/PrimaryButton.vue';
import SearchableSelect from '@/components/ev/SearchableSelect.vue';

// ── Types ──────────────────────────────────────────────────────────

type Anggaran = {
    id: number;
    kode_rekening: string;
    nama_rekening: string;
    pagu: string;
    sisa: string;
};

type Belanja = {
    id:                  number;
    anggaran_id:         number;
    no_nota:             string | null;
    tanggal_belanja:     string;
    uraian:              string;
    jenis:               string;
    nominal:             string;
    catatan:             string | null;
    status:              string;
    catatan_penolakan:   string | null;
    file_nota:           string | null;
    tanggal_dibayar:     string | null;
};

// ── Props ──────────────────────────────────────────────────────────

const props = withDefaults(
    defineProps<{
        belanja:       Belanja;
        anggaranList:  Anggaran[];
        jenisOptions:  Record<string, string>;
        isKeuangan?:   boolean;
        threshold?:    number;
    }>(),
    {
        isKeuangan: false,
        threshold:  1000000,
    },
);

// ── Form — pre-filled dari data lama ──────────────────────────────

const form = useForm({
    anggaran_id:      props.belanja.anggaran_id,
    no_nota:          props.belanja.no_nota         ?? '',
    tanggal_belanja:  props.belanja.tanggal_belanja,
    uraian:           props.belanja.uraian,
    jenis:            props.belanja.jenis,
    nominal:          Number(props.belanja.nominal),
    catatan:          props.belanja.catatan         ?? '',
    file_nota:        null as File | null,
    tanggal_dibayar:  props.belanja.tanggal_dibayar
                        ?? new Date().toISOString().slice(0, 10),
    langsung_ajukan:  false,
    _method:          'PUT',
});

// ── Computed alur ──────────────────────────────────────────────────

const isLangsungBayar = computed(() =>
    props.isKeuangan &&
    form.nominal > 0 &&
    form.nominal < (props.threshold ?? 1000000)
);

const isSkipPptk = computed(() =>
    !props.isKeuangan &&
    form.nominal > 0 &&
    form.nominal < (props.threshold ?? 1000000)
);

const isWajibPptk = computed(() =>
    form.nominal > 0 &&
    form.nominal >= (props.threshold ?? 1000000)
);

// ── Helpers ────────────────────────────────────────────────────────

const fmt = (val: string | number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0,
    }).format(Number(val));

const selectedAnggaran = computed(() =>
    props.anggaranList.find(a => a.id === form.anggaran_id)
);

const onFile = (e: Event) => {
    form.file_nota = (e.target as HTMLInputElement).files?.[0] ?? null;
};

// ── Submit ─────────────────────────────────────────────────────────

const submit = (ajukan: boolean) => {
    form.langsung_ajukan = ajukan;
    form.post(`/belanja-langsung/${props.belanja.id}`, {
        forceFormData:  true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Edit Nota`" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Header -->
        <div class="flex items-start gap-3">
            <Link href="/belanja-langsung"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-card transition hover:bg-muted">
                <ArrowLeft class="h-4 w-4" />
            </Link>
            <PageHeader
                title="Edit Nota Belanja"
                eyebrow="Reimburse"
                :subtitle="belanja.status === 'ditolak'
                    ? 'Perbaiki nota yang ditolak PPTK'
                    : 'Edit draft nota belanja'"
            />
        </div>

        <!-- Banner ditolak -->
        <div v-if="belanja.catatan_penolakan"
            class="flex items-start gap-3 rounded-lg border border-red-300 bg-red-50 p-4 dark:border-red-700 dark:bg-red-950">
            <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0 text-red-600 dark:text-red-400" />
            <div>
                <p class="text-sm font-semibold text-red-700 dark:text-red-300">
                    Nota Ditolak oleh PPTK
                </p>
                <p class="mt-0.5 text-sm text-red-600 dark:text-red-400">
                    {{ belanja.catatan_penolakan }}
                </p>
            </div>
        </div>

        <!-- Info threshold -->
        <div class="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300">
            <Info class="mt-0.5 h-4 w-4 shrink-0" />
            <div>
                <span class="font-semibold">Threshold reimburse langsung: {{ fmt(threshold) }}</span>
                <span class="ml-2 text-xs opacity-80">
                    (Nominal di bawah ini bisa
                    {{ isKeuangan ? 'langsung dicatat sebagai dibayar' : 'langsung ke Keuangan tanpa PPTK' }})
                </span>
            </div>
        </div>

        <Section title="Detail Nota" eyebrow="Informasi">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                <!-- Anggaran -->
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Mata Anggaran <span class="text-destructive">*</span>
                    </label>
                    <SearchableSelect
                        v-model="form.anggaran_id"
                        :items="anggaranList"
                        value-key="id"
                        label-key="nama_rekening"
                        :search-keys="['kode_rekening', 'nama_rekening']"
                        placeholder="Cari kode atau nama rekening..."
                        :render-label="(a) => `${a.kode_rekening} — ${a.nama_rekening}`"
                        :render-subtext="(a) => `Sisa: ${fmt(a.sisa)}`"
                    />
                    <p v-if="form.errors.anggaran_id" class="mt-1 text-xs text-destructive">
                        {{ form.errors.anggaran_id }}
                    </p>
                    <div v-if="selectedAnggaran"
                        class="mt-2 rounded-md bg-secondary/40 px-3 py-2 text-xs">
                        Sisa anggaran:
                        <span class="font-mono font-semibold">{{ fmt(selectedAnggaran.sisa) }}</span>
                    </div>
                </div>

                <!-- Tanggal Belanja -->
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Tanggal Belanja <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.tanggal_belanja"
                        type="date"
                        class="h-11 w-full rounded-md border bg-background px-3 text-sm focus:outline-none focus:ring-1"
                        :class="form.errors.tanggal_belanja
                            ? 'border-destructive focus:ring-destructive'
                            : 'border-input focus:border-primary focus:ring-primary'"
                    />
                    <p v-if="form.errors.tanggal_belanja" class="mt-1 text-xs text-destructive">
                        {{ form.errors.tanggal_belanja }}
                    </p>
                </div>

                <!-- Jenis -->
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Jenis Belanja <span class="text-destructive">*</span>
                    </label>
                    <select
                        v-model="form.jenis"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    >
                        <option v-for="(label, key) in jenisOptions" :key="key" :value="key">
                            {{ label }}
                        </option>
                    </select>
                </div>

                <!-- No Nota -->
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Nomor Nota / Kwitansi</label>
                    <input
                        v-model="form.no_nota"
                        type="text"
                        placeholder="Opsional"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>

                <!-- Nominal -->
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Nominal <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model.number="form.nominal"
                        type="number"
                        min="0"
                        class="h-11 w-full rounded-md border bg-background px-3 text-right font-mono text-sm focus:outline-none focus:ring-1"
                        :class="form.errors.nominal
                            ? 'border-destructive focus:ring-destructive'
                            : 'border-input focus:border-primary focus:ring-primary'"
                    />
                    <p v-if="form.errors.nominal" class="mt-1 text-xs text-destructive">
                        {{ form.errors.nominal }}
                    </p>
                    <p v-else-if="form.nominal > 0" class="mt-1 text-right text-xs text-muted-foreground font-mono">
                        {{ fmt(form.nominal) }}
                    </p>
                </div>

                <!-- Banner alur -->
                <div v-if="form.nominal > 0" class="sm:col-span-2">
                    <div v-if="isLangsungBayar"
                        class="rounded-md border border-green-300 bg-green-50 p-4 dark:border-green-700 dark:bg-green-950">
                        <p class="text-sm font-semibold text-green-700 dark:text-green-300">
                            ✓ Nominal di bawah threshold — langsung dicatat sebagai dibayar
                        </p>
                        <p class="mt-0.5 text-xs text-green-600 dark:text-green-400">
                            Tembusan notifikasi akan dikirim ke PPTK secara otomatis.
                        </p>
                    </div>
                    <div v-else-if="isSkipPptk"
                        class="rounded-md border border-blue-300 bg-blue-50 p-4 dark:border-blue-700 dark:bg-blue-950">
                        <p class="text-sm font-semibold text-blue-700 dark:text-blue-300">
                            ✓ Nominal di bawah threshold — langsung ke Keuangan tanpa PPTK
                        </p>
                        <p class="mt-0.5 text-xs text-blue-600 dark:text-blue-400">
                            Nota akan langsung masuk antrian pembayaran di Keuangan.
                        </p>
                    </div>
                    <div v-else-if="isWajibPptk"
                        class="rounded-md border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950">
                        <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">
                            ⚠ Nominal ≥ {{ fmt(threshold) }} — wajib disetujui PPTK terlebih dahulu
                        </p>
                        <p class="mt-0.5 text-xs text-amber-600 dark:text-amber-400">
                            Nota akan diajukan ke PPTK untuk direview sebelum bisa dibayar.
                        </p>
                    </div>
                </div>

                <!-- Tanggal Dibayar — hanya untuk Keuangan dengan nominal kecil -->
                <div v-if="isLangsungBayar">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Tanggal Dibayar <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.tanggal_dibayar"
                        type="date"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                    <p v-if="form.errors.tanggal_dibayar" class="mt-1 text-xs text-destructive">
                        {{ form.errors.tanggal_dibayar }}
                    </p>
                </div>

                <!-- Uraian -->
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Uraian Kegiatan <span class="text-destructive">*</span>
                    </label>
                    <textarea
                        v-model="form.uraian"
                        rows="3"
                        class="w-full rounded-md border bg-background px-3 py-2.5 text-sm focus:outline-none focus:ring-1"
                        :class="form.errors.uraian
                            ? 'border-destructive focus:ring-destructive'
                            : 'border-input focus:border-primary focus:ring-primary'"
                    />
                    <p v-if="form.errors.uraian" class="mt-1 text-xs text-destructive">
                        {{ form.errors.uraian }}
                    </p>
                </div>

                <!-- Upload Nota -->
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold">Foto / Scan Nota</label>
                    <div v-if="belanja.file_nota" class="mb-2 flex items-center gap-2 text-xs">
                        <a :href="`/storage/${belanja.file_nota}`" target="_blank"
                            class="text-primary hover:underline">
                            Lihat nota saat ini
                        </a>
                        <span class="text-muted-foreground">·</span>
                        <span class="text-muted-foreground">Upload file baru untuk mengganti</span>
                    </div>
                    <label
                        class="flex cursor-pointer items-center gap-3 rounded-lg border border-dashed border-border bg-background px-4 py-3 text-sm transition hover:border-primary hover:bg-muted/30"
                    >
                        <span class="flex-1 text-muted-foreground">
                            {{ form.file_nota
                                ? form.file_nota.name
                                : 'Pilih file baru (opsional)...' }}
                        </span>
                        <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="onFile" />
                    </label>
                    <p class="mt-1 text-xs text-muted-foreground">JPG, PNG, atau PDF · Maks 5MB</p>
                </div>

                <!-- Catatan -->
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold">Catatan Tambahan</label>
                    <textarea
                        v-model="form.catatan"
                        rows="2"
                        placeholder="Opsional"
                        class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>
            </div>
        </Section>

        <!-- Tombol aksi -->
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <Link href="/belanja-langsung">
                <PrimaryButton type="button" variant="secondary">Batal</PrimaryButton>
            </Link>

            <!-- Tombol Simpan Draft — hanya untuk nominal besar (wajib PPTK) -->
            <PrimaryButton
                v-if="isWajibPptk || form.nominal === 0"
                type="button"
                variant="secondary"
                size="lg"
                :disabled="form.processing"
                @click="submit(false)"
            >
                {{ form.processing ? 'Menyimpan...' : 'Simpan Draft' }}
            </PrimaryButton>

            <!-- Tombol utama -->
            <PrimaryButton
                type="button"
                variant="primary"
                size="lg"
                :disabled="form.processing"
                @click="submit(true)"
            >
                {{ form.processing
                    ? 'Menyimpan...'
                    : isLangsungBayar
                        ? 'Catat Pembayaran'
                        : isSkipPptk
                            ? 'Ajukan ke Keuangan'
                            : 'Simpan & Ajukan ke PPTK' }}
            </PrimaryButton>
        </div>

    </div>
</template>