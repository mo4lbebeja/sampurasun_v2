<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';
import PenyediaForm from './_PenyediaForm.vue';

const form = useForm({
    nama: '',
    jenis_badan: 'CV',
    npwp: null as string | null,
    alamat: null as string | null,
    telepon: null as string | null,
    email: null as string | null,
    nama_pic: null as string | null,
    rekening_bank: null as string | null,
    nama_bank: null as string | null,
    atas_nama_rekening: null as string | null,
    is_active: true,
});

const submit = () => {
    form.post('/penyedia', { preserveScroll: true });
};
</script>

<template>
    <Head title="Tambah Penyedia" />

    <form @submit.prevent="submit" class="space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex items-start gap-3">
            <Link
                href="/penyedia"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-card transition hover:bg-muted"
            >
                <ArrowLeft class="h-4 w-4" />
            </Link>
            <div class="flex-1">
                <PageHeader
                    title="Tambah Penyedia"
                    subtitle="Daftarkan vendor baru untuk pengadaan"
                    eyebrow="Master Data"
                />
            </div>
        </div>

        <PenyediaForm :form="form">
            <template #actions>
                <Link href="/penyedia">
                    <PrimaryButton type="button" variant="secondary">
                        Batal
                    </PrimaryButton>
                </Link>
                <PrimaryButton type="submit" variant="primary" size="lg" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Penyedia' }}
                </PrimaryButton>
            </template>
        </PenyediaForm>
    </form>
</template>