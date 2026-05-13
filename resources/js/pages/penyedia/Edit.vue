<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';
import PenyediaForm from './_PenyediaForm.vue';

type Penyedia = {
    id: number;
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

const props = defineProps<{ penyedia: Penyedia }>();

const form = useForm({
    nama: props.penyedia.nama,
    jenis_badan: props.penyedia.jenis_badan,
    npwp: props.penyedia.npwp,
    alamat: props.penyedia.alamat,
    telepon: props.penyedia.telepon,
    email: props.penyedia.email,
    nama_pic: props.penyedia.nama_pic,
    rekening_bank: props.penyedia.rekening_bank,
    nama_bank: props.penyedia.nama_bank,
    atas_nama_rekening: props.penyedia.atas_nama_rekening,
    is_active: props.penyedia.is_active,
});

const submit = () => {
    form.put(`/penyedia/${props.penyedia.id}`, { preserveScroll: true });
};

const deleteForm = useForm({});
const confirmDelete = () => {
    if (!confirm(`Yakin hapus penyedia "${props.penyedia.nama}"?`)) return;
    deleteForm.delete(`/penyedia/${props.penyedia.id}`);
};
</script>

<template>
    <Head :title="`Edit ${penyedia.nama}`" />

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
                    title="Edit Penyedia"
                    :subtitle="penyedia.nama"
                    eyebrow="Master Data"
                />
            </div>
        </div>

        <PenyediaForm :form="form">
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
                <Link href="/penyedia">
                    <PrimaryButton type="button" variant="secondary">
                        Batal
                    </PrimaryButton>
                </Link>
                <PrimaryButton type="submit" variant="primary" size="lg" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </PrimaryButton>
            </template>
        </PenyediaForm>
    </form>
</template>