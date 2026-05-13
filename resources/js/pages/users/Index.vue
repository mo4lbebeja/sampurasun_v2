<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { Plus, Search, Inbox } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

type UserItem = {
    id: number;
    name: string;
    email: string;
    nip: string | null;
    jabatan: string | null;
    role: { id: number; name: string } | null;
    unit_kerja: { id: number; nama: string } | null;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = withDefaults(
    defineProps<{
        users?: Paginated<UserItem>;
        filters?: {
            search: string;
        };
    }>(),
    {
        users: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            from: null,
            to: null,
            total: 0,
            links: [],
        }),
        filters: () => ({
            search: '',
        }),
    },
);

const search = ref(props.filters.search);

const reload = () => {
    router.get(
        '/users',
        {
            search: search.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const debouncedReload = debounce(reload, 400);

watch(search, () => debouncedReload());

const isEmpty = computed(() => props.users.data.length === 0);
const hasActiveFilters = computed(() => !!props.filters.search);

const resetFilters = () => {
    search.value = '';
    reload();
};
</script>

<template>
    <Head title="User" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <PageHeader
            title="User"
            :subtitle="`${users.total} user terdaftar`"
            eyebrow="Master Data"
        >
            <template #actions>
                <Link href="/users/create">
                    <PrimaryButton variant="primary">
                        <Plus class="h-4 w-4" />
                        Tambah User
                    </PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <div class="relative">
            <Search class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground" />

            <input
                v-model="search"
                type="search"
                placeholder="Cari nama, email, NIP, jabatan, role, atau unit kerja..."
                class="h-14 w-full rounded-md border border-input bg-background py-2 pl-12 pr-4 text-base placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            />
        </div>

        <EmptyState
            v-if="isEmpty"
            :title="hasActiveFilters ? 'Tidak ada hasil yang cocok' : 'Belum ada user'"
            :description="
                hasActiveFilters
                    ? 'Coba ubah kata kunci pencarian atau reset pencarian.'
                    : 'Tambahkan user untuk mengelola akses aplikasi.'
            "
            :icon="Inbox"
        >
            <template v-if="hasActiveFilters" #action>
                <PrimaryButton variant="primary" @click="resetFilters">
                    Reset Pencarian
                </PrimaryButton>
            </template>
        </EmptyState>

        <Section v-else>
            <div class="-mx-6 -my-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-secondary/40 text-left text-[10px] uppercase tracking-widest text-muted-foreground">
                            <th class="px-6 py-3 font-semibold">Nama</th>
                            <th class="px-6 py-3 font-semibold">Email</th>
                            <th class="px-6 py-3 font-semibold">NIP / Jabatan</th>
                            <th class="px-6 py-3 font-semibold">Role</th>
                            <th class="px-6 py-3 font-semibold">Unit Kerja</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="transition hover:bg-muted/30"
                        >
                            <td class="px-6 py-4">
                                <div class="font-semibold">
                                    {{ user.name }}
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    {{ user.email }}
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-mono text-xs">
                                    {{ user.nip ?? '—' }}
                                </div>
                                <div class="mt-0.5 text-xs text-muted-foreground">
                                    {{ user.jabatan ?? '—' }}
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span class="badge-status badge-info">
                                    {{ user.role?.name ?? '—' }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                {{ user.unit_kerja?.nama ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <Link
                                    :href="`/users/${user.id}/edit`"
                                    class="text-xs font-semibold text-primary hover:underline"
                                >
                                    Edit
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="users.last_page > 1"
                class="-mx-6 -mb-6 mt-6 flex items-center justify-between border-t border-border bg-secondary/20 px-6 py-3"
            >
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ users.from }}–{{ users.to }} dari {{ users.total }}
                </div>

                <div class="flex gap-1">
                    <template
                        v-for="(link, idx) in users.links"
                        :key="idx"
                    >
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            preserve-state
                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border border-border bg-card px-3 text-xs font-medium transition hover:bg-muted"
                            :class="{ 'border-primary bg-primary text-primary-foreground hover:bg-primary/90': link.active }"
                            v-html="link.label"
                        />

                        <span
                            v-else
                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border border-border px-3 text-xs text-muted-foreground/50"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </Section>
    </div>
</template>