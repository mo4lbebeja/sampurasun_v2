<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import Section from '@/components/ev/Section.vue';

type RoleOption = {
    id: number;
    name: string;
};

type UnitKerjaOption = {
    id: number;
    nama: string;
};

type FormData = {
    role_id: number | null;
    unit_kerja_id: number | null;
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    nip: string | null;
    jabatan: string | null;
    alamat: string | null;
};

const props = withDefaults(
    defineProps<{
        form: InertiaForm<FormData>;
        roleOptions?: RoleOption[];
        unitKerjaOptions?: UnitKerjaOption[];
        isEdit?: boolean;
    }>(),
    {
        roleOptions: () => [],
        unitKerjaOptions: () => [],
        isEdit: false,
    },
);
</script>

<template>
    <div class="space-y-6">
        <Section title="Identitas User" eyebrow="Langkah 1">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Nama <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Email <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.email"
                        type="email"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.email }"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-destructive">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        NIP
                    </label>
                    <input
                        v-model="form.nip"
                        type="text"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.nip }"
                    />
                    <p v-if="form.errors.nip" class="mt-1 text-xs text-destructive">
                        {{ form.errors.nip }}
                    </p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Jabatan
                    </label>
                    <input
                        v-model="form.jabatan"
                        type="text"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.jabatan }"
                    />
                    <p v-if="form.errors.jabatan" class="mt-1 text-xs text-destructive">
                        {{ form.errors.jabatan }}
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold">
                        Alamat
                    </label>
                    <textarea
                        v-model="form.alamat"
                        rows="3"
                        class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.alamat }"
                    />
                    <p v-if="form.errors.alamat" class="mt-1 text-xs text-destructive">
                        {{ form.errors.alamat }}
                    </p>
                </div>
            </div>
        </Section>

        <Section title="Akses & Unit Kerja" eyebrow="Langkah 2">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Role <span class="text-destructive">*</span>
                    </label>
                    <select
                        v-model="form.role_id"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.role_id }"
                    >
                        <option :value="null">Pilih role</option>
                        <option
                            v-for="role in props.roleOptions"
                            :key="role.id"
                            :value="role.id"
                        >
                            {{ role.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.role_id" class="mt-1 text-xs text-destructive">
                        {{ form.errors.role_id }}
                    </p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Unit Kerja
                    </label>
                    <select
                        v-model="form.unit_kerja_id"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.unit_kerja_id }"
                    >
                        <option :value="null">Pilih unit kerja</option>
                        <option
                            v-for="unit in props.unitKerjaOptions"
                            :key="unit.id"
                            :value="unit.id"
                        >
                            {{ unit.nama }}
                        </option>
                    </select>
                    <p v-if="form.errors.unit_kerja_id" class="mt-1 text-xs text-destructive">
                        {{ form.errors.unit_kerja_id }}
                    </p>
                </div>
            </div>
        </Section>

        <Section
            :title="isEdit ? 'Password Baru' : 'Password'"
            :eyebrow="isEdit ? 'Opsional' : 'Langkah 3'"
            :description="isEdit ? 'Kosongkan jika tidak ingin mengganti password.' : ''"
        >
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Password <span v-if="!isEdit" class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.password"
                        type="password"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.password }"
                    />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-destructive">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Konfirmasi Password <span v-if="!isEdit" class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>
            </div>
        </Section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <slot name="actions" />
        </div>
    </div>
</template>