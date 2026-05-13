<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import Section from '@/components/ev/Section.vue';

type FormData = {
    nama: string;
    jenis_badan: string;
    npwp: string | null;
    alamat: string | null;
    telepon: string | null;
    email: string | null;
    nama_pic: string | null;
    rekening_bank: string | null;
    nama_bank: string | null;
    atas_nama_rekening: string | null;
    is_active: boolean;
};

defineProps<{
    form: InertiaForm<FormData>;
}>();

const jenisBadanOptions = ['PT', 'CV', 'UD', 'Firma', 'Koperasi', 'Perorangan'];
</script>

<template>
    <div class="space-y-6">
        <!-- Section: Identitas -->
        <Section title="Identitas Penyedia" eyebrow="Langkah 1">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Nama Penyedia <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.nama"
                        type="text"
                        placeholder="Contoh: PT Mitra Sejati Abadi"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.nama }"
                    />
                    <p v-if="form.errors.nama" class="mt-1 text-xs text-destructive">
                        {{ form.errors.nama }}
                    </p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Jenis Badan <span class="text-destructive">*</span>
                    </label>
                    <select
                        v-model="form.jenis_badan"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.jenis_badan }"
                    >
                        <option v-for="opt in jenisBadanOptions" :key="opt" :value="opt">
                            {{ opt }}
                        </option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold">NPWP</label>
                    <input
                        v-model="form.npwp"
                        type="text"
                        placeholder="00.000.000.0-000.000"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
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
                        <span>Aktif</span>
                    </label>
                </div>

                <div class="sm:col-span-3">
                    <label class="mb-1.5 block text-sm font-semibold">Alamat</label>
                    <textarea
                        v-model="form.alamat"
                        rows="2"
                        placeholder="Alamat lengkap penyedia"
                        class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>
            </div>
        </Section>

        <!-- Section: Kontak -->
        <Section title="Kontak" eyebrow="Langkah 2">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Telepon</label>
                    <input
                        v-model="form.telepon"
                        type="text"
                        placeholder="021-xxx atau 08xx"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        placeholder="contact@penyedia.com"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.email }"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold">Nama PIC (Person in Charge)</label>
                    <input
                        v-model="form.nama_pic"
                        type="text"
                        placeholder="Nama orang yang bisa dihubungi"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>
            </div>
        </Section>

        <!-- Section: Rekening Bank -->
        <Section
            title="Rekening Bank"
            eyebrow="Langkah 3"
            description="Untuk pembayaran transaksi pengadaan"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Nama Bank</label>
                    <input
                        v-model="form.nama_bank"
                        type="text"
                        placeholder="Contoh: BNI, BRI, Mandiri"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">No. Rekening</label>
                    <input
                        v-model="form.rekening_bank"
                        type="text"
                        placeholder="No. rekening"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold">Nama Pemilik Rekening</label>
                    <input
                        v-model="form.atas_nama_rekening"
                        type="text"
                        placeholder="Atas nama (sesuai buku tabungan)"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>
            </div>
        </Section>

        <!-- Submit row (slot) -->
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <slot name="actions" />
        </div>
    </div>
</template>