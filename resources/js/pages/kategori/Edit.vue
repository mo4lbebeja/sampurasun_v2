<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';
import KategoriForm from './_KategoriForm.vue';

type Kategori = {
    id: number;
    kode: string;
    nama: string;
    deskripsi: string | null;
    is_active: boolean;
};

const props = defineProps<{ kategori: Kategori }>();

const form = useForm({
    kode: props.kategori.kode,
    nama: props.kategori.nama,
    deskripsi: props.kategori.deskripsi,
    is_active: props.kategori.is_active,
});

const submit = () => {
    form.put(`/kategori/${props.kategori.id}`, { preserveScroll: true });
};

const deleteForm = useForm({});
const confirmDelete = () => {
    if (!confirm(`Yakin hapus kategori "${props.kategori.nama}"?`)) return;
    deleteForm.delete(`/kategori/${props.kategori.id}`);
};
</script>

<template>
    <Head :title="`Edit ${kategori.nama}`" />

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
                    title="Edit Kategori"
                    :subtitle="kategori.nama"
                    eyebrow="Master Data"
                />
            </div>
        </div>

        <KategoriForm :form="form">
            <template #actions>
                <PrimaryButton
                    type="button"
                    variant="danger"
                    class="sm:mr-auto"
                    @click="confirmDelete"
                >
                    <Trash2 class="h-4 w-4" />
                    Hapus
                </PrimaryButton>
                <Link href="/kategori">
                    <PrimaryButton type="button" variant="secondary">Batal</PrimaryButton>
                </Link>
                <PrimaryButton type="submit" variant="primary" size="lg" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </PrimaryButton>
            </template>
        </KategoriForm>
    </form>
</template>