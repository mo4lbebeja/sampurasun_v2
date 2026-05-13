<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from 'lucide-vue-next';

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

type UserData = {
    id: number;
    role_id: number | null;
    unit_kerja_id: number | null;
    name: string;
    email: string;
    nip: string | null;
    jabatan: string | null;
    alamat: string | null;
};

const props = withDefaults(
    defineProps<{
        userData: UserData;
        roleOptions?: RoleOption[];
        unitKerjaOptions?: UnitKerjaOption[];
    }>(),
    {
        roleOptions: () => [],
        unitKerjaOptions: () => [],
    },
);

const form = useForm({
    role_id: props.userData.role_id ?? null,
    unit_kerja_id: props.userData.unit_kerja_id ?? null,
    name: props.userData.name,
    email: props.userData.email,
    password: '',
    password_confirmation: '',
    nip: props.userData.nip ?? null,
    jabatan: props.userData.jabatan ?? null,
    alamat: props.userData.alamat ?? null,
});

const submit = () => {
    form.put(`/users/${props.userData.id}`, { preserveScroll: true });
};

const deleteForm = useForm({});

const confirmDelete = () => {
    if (!confirm(`Yakin hapus user "${props.userData.name}"?`)) return;

    deleteForm.delete(`/users/${props.userData.id}`);
};
</script>

<template>
    <Head :title="`Edit ${userData.name}`" />

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
                    title="Edit User"
                    :subtitle="userData.name"
                    eyebrow="Master Data"
                />
            </div>
        </div>

        <UserForm
            :form="form"
            :is-edit="true"
            :role-options="props.roleOptions"
            :unit-kerja-options="props.unitKerjaOptions"
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

                <Link href="/users">
                    <PrimaryButton type="button" variant="secondary">Batal</PrimaryButton>
                </Link>

                <PrimaryButton type="submit" variant="primary" size="lg" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </PrimaryButton>
            </template>
        </UserForm>
    </form>
</template>