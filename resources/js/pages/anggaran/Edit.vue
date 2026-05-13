<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';
import AnggaranForm from './_AnggaranForm.vue';

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

type Anggaran = {
    id: number;
    sub_kegiatan_id: number | null;
    tahun: number;
    kode_rekening: string;
    nama_rekening: string;
    uraian: string | null;
    pagu: string | number;
    terpakai: string | number;
    sisa: string | number;
    is_active: boolean;
};

const props = defineProps<{
    anggaran: Anggaran;
    selectedSubKegiatan: SubKegiatanOption;
    tahunAnggaran: number;
    summary: Summary;
}>();

const form = useForm({
    sub_kegiatan_id: props.anggaran.sub_kegiatan_id ?? props.selectedSubKegiatan.id,
    kode_rekening: props.anggaran.kode_rekening,
    nama_rekening: props.anggaran.nama_rekening,
    uraian: props.anggaran.uraian,
    pagu: Number(props.anggaran.pagu || 0),
    terpakai: Number(props.anggaran.terpakai || 0),
    submit_action: 'save_back' as 'save_back',
});

const submit = () => {
    if (form.terpakai === null || (form.terpakai as unknown) === '') {
        form.terpakai = 0;
    }

    if (form.pagu === null || (form.pagu as unknown) === '') {
        form.pagu = 0;
    }

    form.put(`/anggaran/${props.anggaran.id}`, {
        preserveScroll: true,
    });
};

const deleteForm = useForm({});

const confirmDelete = () => {
    if (!confirm(`Yakin hapus anggaran "${props.anggaran.kode_rekening}"?`)) {
        return;
    }

    deleteForm.delete(`/anggaran/${props.anggaran.id}`);
};
</script>

<template>
    <Head :title="`Edit ${anggaran.kode_rekening}`" />

    <form
        class="space-y-6 p-4 sm:p-6 lg:p-8"
        @submit.prevent="submit"
    >
        <div class="flex items-start gap-3">
            <Link
                :href="`/anggaran?sub_kegiatan_id=${props.selectedSubKegiatan.id}`"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-card transition hover:bg-muted"
            >
                <ArrowLeft class="h-4 w-4" />
            </Link>

            <div class="flex-1">
                <PageHeader
                    title="Edit Anggaran"
                    :subtitle="`${anggaran.kode_rekening} — ${anggaran.nama_rekening}`"
                    eyebrow="Master Data"
                />
            </div>
        </div>

        <AnggaranForm
            :form="form"
            :is-edit="true"
            :tahun-anggaran="props.tahunAnggaran"
            :selected-sub-kegiatan="props.selectedSubKegiatan"
            :summary="props.summary"
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
                    {{ deleteForm.processing ? 'Menghapus...' : 'Hapus' }}
                </PrimaryButton>

                <Link :href="`/anggaran?sub_kegiatan_id=${props.selectedSubKegiatan.id}`">
                    <PrimaryButton
                        type="button"
                        variant="secondary"
                    >
                        Batal
                    </PrimaryButton>
                </Link>

                <PrimaryButton
                    type="submit"
                    variant="primary"
                    size="lg"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </PrimaryButton>
            </template>
        </AnggaranForm>
    </form>
</template>