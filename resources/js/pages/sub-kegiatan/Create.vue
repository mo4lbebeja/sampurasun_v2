<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

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

const props = withDefaults(
    defineProps<{
        currentYear?: number;
        dpaOptions?: DpaOption[];
    }>(),
    {
        currentYear: new Date().getFullYear(),
        dpaOptions: () => [],
    },
);

const form = useForm({
    dpa_anggaran_id: null as number | null,
    kode_sub_kegiatan: '',
    nama_kegiatan: '',
    tahun_anggaran: props.currentYear,
    is_active: true,
});

const submit = () => {
    form.post('/sub-kegiatan', { preserveScroll: true });
};
</script>

<template>
    <Head title="Tambah Sub Kegiatan" />

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
                    title="Tambah Sub Kegiatan"
                    subtitle="Daftarkan sub kegiatan dan hubungkan dengan DPA Anggaran"
                    eyebrow="Master Data"
                />
            </div>
        </div>

        <SubKegiatanForm
            :form="form"
            :dpa-options="props.dpaOptions"
        >
            <template #actions>
                <Link href="/sub-kegiatan">
                    <PrimaryButton type="button" variant="secondary">Batal</PrimaryButton>
                </Link>

                <PrimaryButton type="submit" variant="primary" size="lg" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Sub Kegiatan' }}
                </PrimaryButton>
            </template>
        </SubKegiatanForm>
    </form>
</template>