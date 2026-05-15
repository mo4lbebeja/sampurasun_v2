<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
    kopSurat: string | null;
}>();

const form = useForm({
    kop_surat: null as File | null,
});

const submit = () => {
    form.post('/settings/kop-surat', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('kop_surat');
        },
    });
};
</script>

<template>
    <Head title="Setting Kop Surat" />

    <div class="mx-auto max-w-4xl space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-bold">
                Setting Kop Surat
            </h1>
            <p class="text-sm text-muted-foreground">
                Upload gambar kop surat yang akan digunakan pada dokumen cetak PDF.
            </p>
        </div>

        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-base font-semibold">
                Preview Kop Surat Saat Ini
            </h2>

            <div v-if="kopSurat" class="rounded border bg-white p-3">
                <img
                    :src="kopSurat"
                    alt="Kop Surat"
                    class="w-full max-w-3xl rounded border object-contain"
                />
            </div>

            <div
                v-else
                class="flex h-32 items-center justify-center rounded border border-dashed text-sm text-gray-500"
            >
                Belum ada kop surat yang diupload.
            </div>
        </div>

        <form
            @submit.prevent="submit"
            class="space-y-4 rounded-xl border bg-white p-5 shadow-sm"
        >
            <div>
                <label class="mb-2 block text-sm font-medium">
                    Upload Kop Surat
                </label>

                <input
                    type="file"
                    accept="image/png,image/jpeg,image/jpg,image/webp"
                    class="block w-full rounded border p-2 text-sm"
                    @change="form.kop_surat = ($event.target as HTMLInputElement).files?.[0] ?? null"
                />

                <p class="mt-2 text-xs text-gray-500">
                    Rekomendasi ukuran: 1600 x 250 px. Format: PNG/JPG. Maksimal 2 MB.
                </p>

                <p v-if="form.errors.kop_surat" class="mt-2 text-sm text-red-600">
                    {{ form.errors.kop_surat }}
                </p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="rounded bg-black px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
            >
                {{ form.processing ? 'Menyimpan...' : 'Simpan Kop Surat' }}
            </button>
        </form>
    </div>
</template>