<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import Section from '@/components/ev/Section.vue';

type DpaOption = {
    id: number;
    tahun_anggaran: number;
    no_dpa: string;
    tanggal_dpa: string | null;
    nama_dpa: string | null;
};

type FormData = {
    dpa_anggaran_id: number | null;
    kode_sub_kegiatan: string | null;
    nama_kegiatan: string;
    tahun_anggaran: number;
    is_active: boolean;
};

const props = withDefaults(
    defineProps<{
        form: InertiaForm<FormData>;
        dpaOptions?: DpaOption[];
        isEdit?: boolean;
    }>(),
    {
        dpaOptions: () => [],
        isEdit: false,
    },
);
</script>

<template>
    <div class="space-y-6">
        <Section title="Identitas Sub Kegiatan" eyebrow="Master Data">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="sm:col-span-3">
                    <label class="mb-1.5 block text-sm font-semibold">
                        DPA Anggaran
                    </label>

                    <select
                        v-model="form.dpa_anggaran_id"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.dpa_anggaran_id }"
                    >
                        <option :value="null">Pilih DPA Anggaran</option>
                        <option
                            v-for="dpa in props.dpaOptions"
                            :key="dpa.id"
                            :value="dpa.id"
                        >
                            {{ dpa.tahun_anggaran }} - {{ dpa.no_dpa }}
                            <template v-if="dpa.nama_dpa"> - {{ dpa.nama_dpa }}</template>
                        </option>
                    </select>

                    <p v-if="form.errors.dpa_anggaran_id" class="mt-1 text-xs text-destructive">
                        {{ form.errors.dpa_anggaran_id }}
                    </p>

                    <p class="mt-1 text-xs text-muted-foreground">
                        DPA digunakan untuk nomor dan tanggal DPA pada dokumen cetak.
                    </p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Tahun Anggaran <span class="text-destructive">*</span>
                    </label>

                    <input
                        v-model.number="form.tahun_anggaran"
                        type="number"
                        min="2020"
                        max="2100"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.tahun_anggaran }"
                    />

                    <p v-if="form.errors.tahun_anggaran" class="mt-1 text-xs text-destructive">
                        {{ form.errors.tahun_anggaran }}
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Kode Sub Kegiatan
                    </label>

                    <input
                        v-model="form.kode_sub_kegiatan"
                        type="text"
                        placeholder="Contoh: 1.01.01.2.01.0001"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.kode_sub_kegiatan }"
                    />

                    <p v-if="form.errors.kode_sub_kegiatan" class="mt-1 text-xs text-destructive">
                        {{ form.errors.kode_sub_kegiatan }}
                    </p>
                </div>

                <div class="sm:col-span-3">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Nama Kegiatan <span class="text-destructive">*</span>
                    </label>

                    <input
                        v-model="form.nama_kegiatan"
                        type="text"
                        placeholder="Contoh: Pengadaan Barang Milik Daerah"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.nama_kegiatan }"
                    />

                    <p v-if="form.errors.nama_kegiatan" class="mt-1 text-xs text-destructive">
                        {{ form.errors.nama_kegiatan }}
                    </p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Status</label>

                    <label class="flex h-11 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-3 text-sm">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-input text-primary focus:ring-primary"
                        />
                        <span>Aktif</span>
                    </label>
                </div>
            </div>
        </Section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <slot name="actions" />
        </div>
    </div>
</template>