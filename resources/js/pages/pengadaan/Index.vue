<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ShoppingCart, FileText, ArrowRight, Inbox } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import StatusBadge from '@/components/ev/StatusBadge.vue';
import EmptyState from '@/components/ev/EmptyState.vue';

type UsulanSiap = {
    id: number;
    no_usulan: string;
    judul: string;
    total_estimasi: string;
    pemohon: {
        id: number;
        name: string;
        unit_kerja: { id: number; nama: string } | null;
    } | null;
};

type PengadaanBerjalan = {
    id: number;
    no_pengadaan: string;
    metode: string;
    status: string;
    tanggal_mulai: string;
    nilai_kontrak: string;
    usulan: {
        id: number;
        no_usulan: string;
        judul: string;
        total_estimasi: string;
    } | null;
    penyedia: { id: number; nama: string } | null;
};

const props = withDefaults(
    defineProps<{
        usulanSiap?: UsulanSiap[];
        pengadaanBerjalan?: PengadaanBerjalan[];
    }>(),
    {
        usulanSiap: () => [],
        pengadaanBerjalan: () => [],
    },
);

const formatRupiah = (val: string | number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(val));

const formatMetode = (val: string) =>
    val.split('_').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');

const totalCount = computed(
    () => props.usulanSiap.length + props.pengadaanBerjalan.length,
);

const pengadaanDisplayStatus = (row: any) => {
    if (row.status === 'batal') {
        return {
            status: 'batal',
            label: 'Batal',
        };
    }

    if (row.status === 'proses') {
        return {
            status: 'dalam_pengadaan',
            label: 'Sedang Diproses',
        };
    }

    const usulanStatus = row.usulan?.status;

    if (usulanStatus === 'dokumen') {
        return {
            status: 'dokumen',
            label: 'Dokumen UPBJ',
        };
    }

    if (usulanStatus === 'pembayaran') {
        return {
            status: 'pembayaran',
            label: 'Tahap Pembayaran',
        };
    }

    if (usulanStatus === 'evaluasi') {
        return {
            status: 'evaluasi',
            label: 'Tahap Evaluasi',
        };
    }

    if (usulanStatus === 'selesai') {
        return {
            status: 'selesai',
            label: 'Selesai',
        };
    }

    if (row.status === 'kontrak') {
        return {
            status: 'kontrak',
            label: 'Kontrak',
        };
    }

    return {
        status: row.status,
        label: row.status,
    };
};

</script>

<template>
    <Head title="Pengadaan" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <PageHeader
            title="Pengadaan"
            :subtitle="`${totalCount} antrean kerja saat ini`"
            eyebrow="Pejabat Pengadaan"
        />

        <!-- Section: Usulan Siap Dimulai -->
        <Section
            title="Usulan Siap Dimulai"
            eyebrow="Antrean Baru"
            :description="`${usulanSiap.length} usulan menunggu untuk diproses`"
        >
            <EmptyState
                v-if="usulanSiap.length === 0"
                title="Tidak ada usulan baru"
                description="Tunggu approval dari PPTK untuk usulan baru."
                :icon="Inbox"
            />

            <div v-else class="-mx-6 -my-6 divide-y divide-border">
                <Link
                    v-for="row in usulanSiap"
                    :key="row.id"
                    :href="`/usulan/${row.id}`"
                    class="flex items-center gap-4 px-6 py-4 transition hover:bg-muted/30"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-success);"
                    >
                        <FileText class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="font-mono text-xs text-muted-foreground">
                            {{ row.no_usulan }}
                        </div>
                        <div class="mt-0.5 font-medium">{{ row.judul }}</div>
                        <div class="mt-0.5 text-xs text-muted-foreground">
                            {{ row.pemohon?.name }} · {{ row.pemohon?.unit_kerja?.nama ?? '—' }}
                        </div>
                    </div>
                    <div class="hidden shrink-0 text-right sm:block">
                        <div class="text-eyebrow">Estimasi</div>
                        <div class="mt-0.5 font-mono text-sm font-semibold">
                            {{ formatRupiah(row.total_estimasi) }}
                        </div>
                    </div>
                    <ArrowRight class="h-4 w-4 shrink-0 text-muted-foreground" />
                </Link>
            </div>
        </Section>

        <!-- Section: Pengadaan Berjalan -->
        <Section
            title="Pengadaan Berjalan"
            eyebrow="Sedang Diproses"
            :description="`${pengadaanBerjalan.length} pengadaan dalam proses`"
        >
            <EmptyState
                v-if="pengadaanBerjalan.length === 0"
                title="Belum ada pengadaan"
                description="Pengadaan yang sedang berjalan akan muncul di sini."
                :icon="ShoppingCart"
            />

            <div v-else class="-mx-6 -my-6 divide-y divide-border">
                <Link
                    v-for="row in pengadaanBerjalan"
                    :key="row.id"
                    :href="`/pengadaan/${row.id}`"
                    class="flex flex-col gap-3 px-6 py-4 transition hover:bg-muted/30 sm:flex-row sm:items-center"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs text-muted-foreground">
                                {{ row.no_pengadaan }}
                            </span>
                            <StatusBadge
                                :status="pengadaanDisplayStatus(row).status"
                                :label="pengadaanDisplayStatus(row).label"
                            />
                        </div>
                        <div class="mt-1 font-medium">{{ row.usulan?.judul }}</div>
                        <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-muted-foreground">
                            <span>{{ formatMetode(row.metode) }}</span>
                            <span v-if="row.penyedia">· {{ row.penyedia.nama }}</span>
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <div class="text-eyebrow">
                            {{ row.status === 'kontrak' ? 'Nilai Kontrak' : 'Estimasi' }}
                        </div>
                        <div class="mt-0.5 font-mono text-sm font-semibold">
                            {{ formatRupiah(row.status === 'kontrak' ? row.nilai_kontrak : (row.usulan?.total_estimasi ?? 0)) }}
                        </div>
                    </div>
                    <ArrowRight class="hidden h-4 w-4 shrink-0 text-muted-foreground sm:block" />
                </Link>
            </div>
        </Section>
    </div>
</template>