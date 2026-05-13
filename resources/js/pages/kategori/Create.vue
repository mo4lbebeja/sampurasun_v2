<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';
import KategoriForm from './_KategoriForm.vue';

const form = useForm({
    kode: '',
    nama: '',
    deskripsi: null as string | null,
    is_active: true,
});

const submit = () => {
    form.post('/kategori', { preserveScroll: true });
};
</script>

<template>
    <Head title="Tambah Kategori" />

    <form @submit.prevent="submit" class="space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex items-start gap-3">
            <Link
                href="/kategori"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-card transition hover:bg-muted"
            >
                <ArrowLeft class="h-4 w-4" />
            </Link>
            <div class="flex-1">
                <PageHeader
                    title="Tambah Kategori"
                    subtitle="Daftarkan kategori baru untuk mengelompokkan barang/jasa"
                    eyebrow="Master Data"
                />
            </div>
        </div>

        <KategoriForm :form="form">
            <template #actions>
                <Link href="/kategori">
                    <PrimaryButton type="button" variant="secondary">Batal</PrimaryButton>
                </Link>
                <PrimaryButton type="submit" variant="primary" size="lg" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Kategori' }}
                </PrimaryButton>
            </template>
        </KategoriForm>
    </form>
</template>