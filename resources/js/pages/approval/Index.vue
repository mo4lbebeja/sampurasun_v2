<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Stamp, FileText } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import StatusBadge from '@/components/ev/StatusBadge.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

type Usulan = {
    id: number;
    no_usulan: string;
    judul: string;
    submitted_at: string | null;
    total_estimasi: string;
    pemohon: {
        id: number;
        name: string;
        unit_kerja: { id: number; nama: string } | null;
    } | null;
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

const props = defineProps<{ usulan: Paginated<Usulan> }>();

const formatRupiah = (val: string | number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(val));

const formatDateTime = (val: string | null) => {
    if (!val) return '—';
    return new Date(val).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const isEmpty = computed(() => props.usulan.data.length === 0);
</script>

<template>
    <Head title="Approval Pending" />

        <div class="space-y-6 p-4 sm:p-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                    <Stamp class="h-5 w-5" />
                </div>
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Approval Pending</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ usulan.total }} usulan menunggu keputusan Anda
                    </p>
                </div>
            </div>

            <div
                v-if="isEmpty"
                class="rounded-lg border border-dashed border-border p-12 text-center"
            >
                <FileText class="mx-auto h-10 w-10 text-muted-foreground" />
                <h3 class="mt-3 text-sm font-medium">Tidak ada usulan pending</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Semua usulan sudah diproses. Cek lagi nanti.
                </p>
            </div>

            <div v-else class="space-y-3">
                <Link
                    v-for="row in usulan.data"
                    :key="row.id"
                    :href="`/usulan/${row.id}`"
                    class="block rounded-lg border border-border bg-card p-5 transition hover:border-primary/50 hover:shadow-sm"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="font-mono text-xs text-muted-foreground">
                                {{ row.no_usulan }}
                            </div>
                            <h3 class="mt-1 text-base font-semibold">{{ row.judul }}</h3>
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                <span>{{ row.pemohon?.name ?? '—' }}</span>
                                <span>·</span>
                                <span>{{ row.pemohon?.unit_kerja?.nama ?? '—' }}</span>
                                <span>·</span>
                                <span>Diajukan {{ formatDateTime(row.submitted_at) }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-muted-foreground">Estimasi</div>
                            <div class="font-mono text-sm font-semibold text-primary">
                                {{ formatRupiah(row.total_estimasi) }}
                            </div>
                            <div class="mt-2 text-xs font-medium text-primary">
                                Review →
                            </div>
                        </div>
                    </div>
                </Link>
            </div>

            <!-- Pagination -->
            <div
                v-if="usulan.last_page > 1"
                class="flex items-center justify-between"
            >
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ usulan.from }}–{{ usulan.to }} dari {{ usulan.total }}
                </div>
                <div class="flex gap-1">
                    <template v-for="(link, idx) in usulan.links" :key="idx">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            preserve-state
                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border border-border px-3 text-xs hover:bg-muted"
                            :class="{ 'bg-primary text-primary-foreground hover:bg-primary/90': link.active }"
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
        </div>
</template>