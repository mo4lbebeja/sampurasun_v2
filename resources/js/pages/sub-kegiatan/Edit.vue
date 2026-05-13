<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';
import SubKegiatanForm from './_SubKegiatanForm.vue';

type DpaOption = {
    id: number;
    tahun_anggaran: number;
    no_dpa: string;
    tanggal_dpa: string | null;
    nama_dpa: string | null;
};

type SubKegiatan = {
    id: number;
    dpa_anggaran_id: number | null;
    kode_sub_kegiatan: string | null;
    nama_kegiatan: string;
    tahun_anggaran: number;
    is_active: boolean;
};

const props = withDefaults(
    defineProps<{
        subKegiatan: SubKegiatan;
        dpaOptions?: DpaOption[];
    }>(),
    {
        dpaOptions: () => [],
    },
);

const form = useForm({
    dpa_anggaran_id: props.subKegiatan.dpa_anggaran_id ?? null,
    kode_sub_kegiatan: props.subKegiatan.kode_sub_kegiatan ?? '',
    nama_kegiatan: props.subKegiatan.nama_kegiatan,
    tahun_anggaran: props.subKegiatan.tahun_anggaran,
    is_active: props.subKegiatan.is_active,
});

const submit = () => {
    form.put(`/sub-kegiatan/${props.subKegiatan.id}`, { preserveScroll: true });
};

const deleteForm = useForm({});

const confirmDelete = () => {
    if (!confirm(`Yakin hapus sub kegiatan "${props.subKegiatan.nama_kegiatan}"?`)) return;

    deleteForm.delete(`/sub-kegiatan/${props.subKegiatan.id}`);
};
</script>

<template>
    <Head :title="`Edit ${subKegiatan.nama_kegiatan}`" />

    <form @submit.prevent="submit" class="space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex items-start gap-3">
            <Link
                href="/sub-kegiatan"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-card transition hover:bg-muted"
            >
                <ArrowLeft class="h-4 w-4" />
            </Link>

            <div class="flex-1">
                <PageHeader
                    title="Edit Sub Kegiatan"
                    :subtitle="subKegiatan.nama_kegiatan"
                    eyebrow="Master Data"
                />
            </div>
        </div>

        <SubKegiatanForm
            :form="form"
            :is-edit="true"
            :dpa-options="props.dpaOptions"
        >
            <template #actions>
                <PrimaryButton
                    type="button"
                    variant="danger"
                    class="sm:mr-auto"
                    :disabled="deleteForm.processing"
                    @click="confirmDelete"
                >
                    <Trash2 class="h-4 w-4" />
                    Hapus
                </PrimaryButton>

                <Link href="/sub-kegiatan">
                    <PrimaryButton type="button" variant="secondary">Batal</PrimaryButton>
                </Link>

                <PrimaryButton type="submit" variant="primary" size="lg" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </PrimaryButton>
            </template>
        </SubKegiatanForm>
    </form>
</template>