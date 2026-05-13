<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';
import AnggaranForm from './_AnggaranForm.vue';
import { nextTick, ref } from 'vue';

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

const props = defineProps<{
    selectedSubKegiatan: SubKegiatanOption;
    tahunAnggaran: number;
    defaultStatusAktif: boolean;
    summary: Summary;
}>();

const form = useForm({
    sub_kegiatan_id: props.selectedSubKegiatan.id,
    kode_rekening: '',
    nama_rekening: '',
    uraian: '',
    pagu: 0,
    terpakai: 0,
    submit_action: 'save_back' as 'save_add_more' | 'save_back',
});

const anggaranFormRef = ref<InstanceType<typeof AnggaranForm> | null>(null);

const submit = (action: 'save_add_more' | 'save_back') => {
    form.submit_action = action;

    form.post('/anggaran', {
        preserveScroll: true,

        onSuccess: () => {
            if (action !== 'save_add_more') {
                return;
            }

            form.reset(
                'kode_rekening',
                'nama_rekening',
                'uraian',
                'pagu',
                'terpakai',
            );

            form.clearErrors();

            form.sub_kegiatan_id = props.selectedSubKegiatan.id;
            form.terpakai = 0;
            form.pagu = 0;
            form.submit_action = 'save_back';

            nextTick(() => {
                anggaranFormRef.value?.focusKodeRekening();
            });
        },
    });
};
</script>

<template>
    <Head title="Tambah Anggaran" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <PageHeader
            title="Tambah Pos Anggaran"
            subtitle="Tambahkan kode rekening pada sub kegiatan aktif."
            eyebrow="Master Data"
        >
            <template #actions>
                <Link :href="`/anggaran?sub_kegiatan_id=${props.selectedSubKegiatan.id}`">
                    <PrimaryButton variant="secondary">
                        <ArrowLeft class="h-4 w-4" />
                        Kembali
                    </PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <AnggaranForm
            ref="anggaranFormRef"
            :form="form"
            :tahun-anggaran="props.tahunAnggaran"
            :selected-sub-kegiatan="props.selectedSubKegiatan"
            :summary="props.summary"
        >
            <template #actions>
                <Link :href="`/anggaran?sub_kegiatan_id=${props.selectedSubKegiatan.id}`">
                    <PrimaryButton
                        type="button"
                        variant="secondary"
                    >
                        Batal
                    </PrimaryButton>
                </Link>

                <PrimaryButton
                    type="button"
                    variant="secondary"
                    :disabled="form.processing"
                    @click="submit('save_add_more')"
                >
                    Simpan & Tambah Lagi
                </PrimaryButton>

                <PrimaryButton
                    type="button"
                    variant="primary"
                    :disabled="form.processing"
                    @click="submit('save_back')"
                >
                    Simpan & Kembali
                </PrimaryButton>
            </template>
        </AnggaranForm>
    </div>
</template>