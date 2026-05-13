<script setup lang="ts">
import { computed, ref } from 'vue';
import type { InertiaForm } from '@inertiajs/vue3';

import Section from '@/components/ev/Section.vue';

type FormData = {
    sub_kegiatan_id: number | null;
    kode_rekening: string;
    nama_rekening: string;
    uraian: string | null;
    pagu: number | string;
    terpakai: number | string;
    submit_action?: 'save_add_more' | 'save_back';
};

type DpaAnggaran = {
    id: number;
    tahun_anggaran: number;
    no_dpa: string;
    tanggal_dpa?: string | null;
    nama_dpa?: string | null;
};

type SubKegiatanOption = {
    id: number;
    dpa_anggaran_id: number;
    kode_sub_kegiatan: string | null;
    nama_kegiatan: string;
    tahun_anggaran: number;
    dpa_anggaran?: DpaAnggaran | null;
};

type Summary = {
    total_pagu: number | string;
    total_terpakai: number | string;
    total_sisa: number | string;
};

const props = withDefaults(
    defineProps<{
        form: InertiaForm<FormData>;
        isEdit?: boolean;
        tahunAnggaran: number;
        selectedSubKegiatan: SubKegiatanOption;
        summary?: Summary;
    }>(),
    {
        isEdit: false,
        summary: () => ({
            total_pagu: 0,
            total_terpakai: 0,
            total_sisa: 0,
        }),
    },
);

const formatRupiah = (val: number | string) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(val) || 0);

const currentSisa = computed(() =>
    Math.max(
        0,
        Number(props.form.pagu || 0) - Number(props.form.terpakai || 0),
    ),
);

const persenTerpakai = computed(() => {
    const pagu = Number(props.form.pagu || 0);

    if (pagu <= 0) return 0;

    return Math.min(
        100,
        Math.round((Number(props.form.terpakai || 0) / pagu) * 100),
    );
});

const progressTone = computed(() => {
    if (persenTerpakai.value >= 90) return 'var(--color-brand-danger)';
    if (persenTerpakai.value >= 75) return 'var(--color-brand-warning)';
    if (persenTerpakai.value >= 50) return 'var(--color-brand-info)';

    return 'var(--color-brand-success)';
});

const dpaLabel = computed(() => {
    const dpa = props.selectedSubKegiatan?.dpa_anggaran;

    if (!dpa) return '-';

    return dpa.no_dpa || dpa.nama_dpa || '-';
});
const kodeRekeningInput = ref<HTMLInputElement | null>(null);

const focusKodeRekening = () => {
    kodeRekeningInput.value?.focus();
};

defineExpose({
    focusKodeRekening,
});

</script>

<template>
    <div class="space-y-6">
        <!-- Context: Sub Kegiatan -->
        <Section
            title="Konteks Pos Anggaran"
            eyebrow="Sub Kegiatan Aktif"
        >
            <div
                class="grid gap-4 rounded-lg border border-border bg-background p-5 md:grid-cols-[150px_minmax(0,1fr)_220px_220px]"
            >
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Tahun Anggaran
                    </div>

                    <div class="mt-2 text-sm font-semibold text-foreground">
                        {{ tahunAnggaran }}
                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Sub Kegiatan
                    </div>

                    <div class="mt-2 text-sm font-semibold leading-snug text-foreground">
                        {{ selectedSubKegiatan.kode_sub_kegiatan ?? '-' }} —
                        {{ selectedSubKegiatan.nama_kegiatan }}
                    </div>

                    <div class="mt-1 text-xs text-muted-foreground">
                        {{ dpaLabel }}
                    </div>
                </div>

                <div class="md:text-right">
                    <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Pagu Saat Ini
                    </div>

                    <div class="mt-2 text-sm font-semibold text-foreground">
                        {{ formatRupiah(summary.total_pagu) }}
                    </div>

                    <div class="mt-1 text-xs text-muted-foreground">
                        Terpakai:
                        <span>
                            {{ formatRupiah(summary.total_terpakai) }}
                        </span>
                    </div>
                </div>

                <div class="md:text-right">
                    <div class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Sisa Saat Ini
                    </div>

                    <div class="mt-2 text-sm font-semibold text-primary">
                        {{ formatRupiah(summary.total_sisa) }}
                    </div>
                </div>
            </div>

            <p class="mt-3 text-xs text-muted-foreground">
                Tahun anggaran, sub kegiatan, dan status ditentukan otomatis dari pilihan sub kegiatan aktif. Field tersebut tidak perlu diisi ulang pada form ini.
            </p>
        </Section>

        <!-- Section: Identitas Pos Anggaran -->
        <Section
            title="Identitas Pos Anggaran"
            eyebrow="Langkah 1"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Kode Rekening <span class="text-destructive">*</span>
                    </label>

                        <input
                            ref="kodeRekeningInput"
                            v-model="form.kode_rekening"
                            type="text"
                            placeholder="Contoh: 5.1.02.01.01.024"
                            class="h-11 w-full rounded-md border border-input bg-background px-3 font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            :class="{ 'border-destructive': form.errors.kode_rekening }"
                        />

                    <p
                        v-if="form.errors.kode_rekening"
                        class="mt-1 text-xs text-destructive"
                    >
                        {{ form.errors.kode_rekening }}
                    </p>
                </div>

                <div class="sm:col-span-3">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Nama Rekening <span class="text-destructive">*</span>
                    </label>

                    <input
                        v-model="form.nama_rekening"
                        type="text"
                        placeholder="Contoh: Belanja Modal Peralatan dan Mesin"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.nama_rekening }"
                    />

                    <p
                        v-if="form.errors.nama_rekening"
                        class="mt-1 text-xs text-destructive"
                    >
                        {{ form.errors.nama_rekening }}
                    </p>
                </div>

                <div class="sm:col-span-3">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Uraian
                    </label>

                    <textarea
                        v-model="form.uraian"
                        rows="3"
                        placeholder="Penjelasan tambahan tentang pos anggaran ini (opsional)"
                        class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.uraian }"
                    />

                    <p
                        v-if="form.errors.uraian"
                        class="mt-1 text-xs text-destructive"
                    >
                        {{ form.errors.uraian }}
                    </p>
                </div>

                <input
                    v-model="form.sub_kegiatan_id"
                    type="hidden"
                />
            </div>
        </Section>

        <!-- Section: Nilai Anggaran -->
        <Section
            title="Nilai Anggaran"
            eyebrow="Langkah 2"
        >
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">
                            Pagu Anggaran <span class="text-destructive">*</span>
                        </label>

                        <input
                            v-model.number="form.pagu"
                            type="number"
                            min="0"
                            class="h-11 w-full rounded-md border border-input bg-background px-3 text-right font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            :class="{ 'border-destructive': form.errors.pagu }"
                        />

                        <p
                            v-if="form.errors.pagu"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ form.errors.pagu }}
                        </p>

                        <p class="mt-1 text-xs text-muted-foreground">
                            Total dana yang dialokasikan untuk kode rekening ini.
                        </p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">
                            Terpakai
                        </label>

                        <input
                            v-model.number="form.terpakai"
                            type="number"
                            min="0"
                            placeholder="0"
                            class="h-11 w-full rounded-md border border-input bg-background px-3 text-right font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            :class="{ 'border-destructive': form.errors.terpakai }"
                        />

                        <p
                            v-if="form.errors.terpakai"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ form.errors.terpakai }}
                        </p>

                        <p class="mt-1 text-xs text-muted-foreground">
                            Isi jika ada nilai awal yang sudah terpakai. Jika belum ada, biarkan 0.
                        </p>
                    </div>
                </div>

                <!-- Summary realtime -->
                <div class="rounded-md border border-primary/30 bg-primary/5 p-5">
                    <div class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Ringkasan Input
                    </div>

                    <dl class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <dt class="text-muted-foreground">Pagu</dt>
                            <dd class="font-mono font-medium">
                                {{ formatRupiah(form.pagu) }}
                            </dd>
                        </div>

                        <div class="flex items-center justify-between text-sm">
                            <dt class="text-muted-foreground">Terpakai</dt>
                            <dd
                                class="font-mono font-medium"
                                style="color: var(--color-brand-warning);"
                            >
                                {{ formatRupiah(form.terpakai) }}
                            </dd>
                        </div>

                        <div class="border-t border-primary/20 pt-2">
                            <div class="flex items-center justify-between">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Sisa Anggaran
                                </dt>

                                <dd class="text-lg font-bold text-primary">
                                    {{ formatRupiah(currentSisa) }}
                                </dd>
                            </div>
                        </div>
                    </dl>

                    <!-- Progress bar -->
                    <div class="mt-4 space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Realisasi</span>

                            <span
                                class="font-mono font-bold"
                                :style="`color: ${progressTone};`"
                            >
                                {{ persenTerpakai }}%
                            </span>
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-card">
                            <div
                                class="h-full rounded-full transition-all"
                                :style="`width: ${persenTerpakai}%; background-color: ${progressTone};`"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </Section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <slot name="actions" />
        </div>
    </div>
</template>