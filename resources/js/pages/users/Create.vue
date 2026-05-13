<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';
import UserForm from './_UserForm.vue';

type RoleOption = {
    id: number;
    name: string;
};

type UnitKerjaOption = {
    id: number;
    nama: string;
};

const props = withDefaults(
    defineProps<{
        roleOptions?: RoleOption[];
        unitKerjaOptions?: UnitKerjaOption[];
    }>(),
    {
        roleOptions: () => [],
        unitKerjaOptions: () => [],
    },
);

const form = useForm({
    role_id: null as number | null,
    unit_kerja_id: null as number | null,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    nip: null as string | null,
    jabatan: null as string | null,
    alamat: null as string | null,
});

const submit = () => {
    form.post('/users', { preserveScroll: true });
};
</script>

<template>
    <Head title="Tambah User" />

    <form @submit.prevent="submit" class="space-y-6 p-4 sm:p-6 lg:p-8">
        <div class="flex items-start gap-3">
            <Link
                href="/users"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-card transition hover:bg-muted"
            >
                <ArrowLeft class="h-4 w-4" />
            </Link>

            <div class="flex-1">
                <PageHeader
                    title="Tambah User"
                    subtitle="Daftarkan pengguna aplikasi"
                    eyebrow="Master Data"
                />
            </div>
        </div>

        <UserForm
            :form="form"
            :role-options="props.roleOptions"
            :unit-kerja-options="props.unitKerjaOptions"
        >
            <template #actions>
                <Link href="/users">
                    <PrimaryButton type="button" variant="secondary">Batal</PrimaryButton>
                </Link>

                <PrimaryButton type="submit" variant="primary" size="lg" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan User' }}
                </PrimaryButton>
            </template>
        </UserForm>
    </form>
</template>