<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import Section from '@/components/ev/Section.vue';

type FormData = {
    kode: string;
    nama: string;
    deskripsi: string | null;
    is_active: boolean;
};

defineProps<{
    form: InertiaForm<FormData>;
}>();
</script>

<template>
    <div class="space-y-6">
        <Section title="Informasi Kategori" eyebrow="Detail">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Kode <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.kode"
                        type="text"
                        placeholder="Contoh: ATK, ELEK"
                        maxlength="20"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 font-mono text-sm uppercase focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.kode }"
                    />
                    <p v-if="form.errors.kode" class="mt-1 text-xs text-destructive">
                        {{ form.errors.kode }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Singkatan unik, max 20 karakter
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Nama Kategori <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.nama"
                        type="text"
                        placeholder="Contoh: Alat Tulis Kantor"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.nama }"
                    />
                    <p v-if="form.errors.nama" class="mt-1 text-xs text-destructive">
                        {{ form.errors.nama }}
                    </p>
                </div>

                <div class="sm:col-span-3">
                    <label class="mb-1.5 block text-sm font-semibold">Deskripsi</label>
                    <textarea
                        v-model="form.deskripsi"
                        rows="3"
                        placeholder="Penjelasan tambahan tentang kategori ini (opsional)"
                        class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Status</label>
                    <label class="flex h-11 cursor-pointer items-center gap-2 rounded-md border border-input bg-background px-3 text-sm">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-input text-primary focus:ring-primary"
                        />
                        <span>Aktif (bisa dipilih saat buat usulan)</span>
                    </label>
                </div>
            </div>
        </Section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <slot name="actions" />
        </div>
    </div>
</template>