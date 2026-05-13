<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { FileCheck, FileText, ArrowRight, Inbox, Clock } from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

// ── Types ────────────────────────────────────────────────────────

type Pengadaan = {
    id: number;
    no_pengadaan: string;
    no_kontrak: string | null;
    nilai_kontrak: string;
    metode: string;
    usulan: {
        id: number;
        no_usulan: string;
        judul: string;
        total_estimasi: string;
        status: string;
        pemohon: {
            id: number;
            name: string;
            unit_kerja: { id: number; nama: string } | null;
        } | null;
    } | null;
    penyedia: { id: number; nama: string } | null;
    dokumen_upbj: {
        id: number;
        is_complete: boolean;
        no_bast: string | null;
        completed_at: string | null;
        updated_at: string;
    } | null;
};

// SEBELUM: Pengadaan[] (array biasa)
// SESUDAH: Paginated<Pengadaan> (objek paginator Laravel)
type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

// ── Props ────────────────────────────────────────────────────────

// SEBELUM: pengadaan?: Pengadaan[]
// SESUDAH: pengadaan: Paginated<Pengadaan>
const props = defineProps<{
    pengadaan: Paginated<Pengadaan>;
}>();

// ── Helpers ──────────────────────────────────────────────────────

const formatRupiah = (val: string | number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(val));

const formatMetode = (val: string) =>
    val.split('_').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');

const formatRelative = (val: string) => {
    const diff = (Date.now() - new Date(val).getTime()) / 1000;
    if (diff < 60) return 'Baru saja';
    if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`;
    if (diff < 604800) return `${Math.floor(diff / 86400)} hari lalu`;
    return new Date(val).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
};

// SEBELUM: props.pengadaan.filter(...)
// SESUDAH: props.pengadaan.data.filter(...) — filter dari data halaman ini
const belumDimulai = computed(() =>
    props.pengadaan.data.filter((p) => !p.dokumen_upbj),
);

const dalamProses = computed(() =>
    props.pengadaan.data.filter((p) => p.dokumen_upbj && !p.dokumen_upbj.is_complete),
);

// SEBELUM: props.pengadaan.length
// SESUDAH: props.pengadaan.total (total seluruh data, bukan hanya halaman ini)
const totalCount = computed(() => props.pengadaan.total);
</script>

<template>
    <Head title="Dokumen UPBJ" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <PageHeader
            title="Dokumen UPBJ"
            :subtitle="`${totalCount} pengadaan dalam tahap pengurusan dokumen`"
            eyebrow="Unit Pengelola Barang & Jasa"
        >
            <template #actions>
                <Link href="/dokumen/rekap">
                    <PrimaryButton variant="secondary">
                        <FileText class="h-4 w-4" />
                        Lihat Rekap
                    </PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <!-- Empty state global -->
        <EmptyState
            v-if="totalCount === 0"
            title="Belum ada pengadaan untuk diproses"
            description="Pengadaan dengan kontrak yang sudah selesai akan otomatis muncul di sini untuk pengurusan dokumen BAST, Invoice, Faktur, Kuitansi, dan SPP."
            :icon="FileCheck"
        />

        <template v-else>
            <!-- ── Section: Belum Dimulai ─────────────────────────────── -->
            <Section
                v-if="belumDimulai.length > 0"
                title="Belum Dimulai"
                eyebrow="Antrean Baru"
                :description="`${belumDimulai.length} pengadaan menunggu pengurusan dokumen`"
            >
                <div class="-mx-6 -my-6 divide-y divide-border">
                    <!-- SEBELUM: v-for="row in belumDimulai" (dari props.pengadaan.filter) -->
                    <!-- SESUDAH: v-for="row in belumDimulai" (dari props.pengadaan.data.filter) -->
                    <Link
                        v-for="row in belumDimulai"
                        :key="row.id"
                        :href="`/dokumen/${row.id}`"
                        class="flex items-center gap-4 px-6 py-4 transition hover:bg-muted/30"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                            style="background-color: var(--color-brand-warning);"
                        >
                            <FileText class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs text-muted-foreground">
                                    {{ row.no_pengadaan }}
                                </span>
                                <span class="badge-status badge-warning">Belum Dimulai</span>
                            </div>
                            <div class="mt-1 font-medium">{{ row.usulan?.judul }}</div>
                            <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-muted-foreground">
                                <span>{{ formatMetode(row.metode) }}</span>
                                <span v-if="row.penyedia">· {{ row.penyedia.nama }}</span>
                                <span v-if="row.no_kontrak">· {{ row.no_kontrak }}</span>
                            </div>
                        </div>
                        <div class="hidden shrink-0 text-right sm:block">
                            <div class="text-eyebrow">Nilai Kontrak</div>
                            <div class="mt-0.5 font-mono text-sm font-semibold">
                                {{ formatRupiah(row.nilai_kontrak) }}
                            </div>
                        </div>
                        <ArrowRight class="h-4 w-4 shrink-0 text-muted-foreground" />
                    </Link>
                </div>
            </Section>

            <!-- ── Section: Dalam Proses ──────────────────────────────── -->
            <Section
                v-if="dalamProses.length > 0"
                title="Dalam Proses"
                eyebrow="Sedang Dikerjakan"
                :description="`${dalamProses.length} pengadaan sedang dilengkapi dokumennya`"
            >
                <div class="-mx-6 -my-6 divide-y divide-border">
                    <Link
                        v-for="row in dalamProses"
                        :key="row.id"
                        :href="`/dokumen/${row.id}`"
                        class="flex items-center gap-4 px-6 py-4 transition hover:bg-muted/30"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-white"
                            style="background-color: var(--color-brand-info);"
                        >
                            <Clock class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs text-muted-foreground">
                                    {{ row.no_pengadaan }}
                                </span>
                                <span class="badge-status badge-info">Dalam Proses</span>
                            </div>
                            <div class="mt-1 font-medium">{{ row.usulan?.judul }}</div>
                            <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-muted-foreground">
                                <span>{{ formatMetode(row.metode) }}</span>
                                <span v-if="row.penyedia">· {{ row.penyedia.nama }}</span>
                                <span v-if="row.dokumen_upbj?.updated_at">
                                    · Diperbarui {{ formatRelative(row.dokumen_upbj.updated_at) }}
                                </span>
                            </div>
                        </div>
                        <div class="hidden shrink-0 text-right sm:block">
                            <div class="text-eyebrow">Nilai Kontrak</div>
                            <div class="mt-0.5 font-mono text-sm font-semibold">
                                {{ formatRupiah(row.nilai_kontrak) }}
                            </div>
                        </div>
                        <ArrowRight class="h-4 w-4 shrink-0 text-muted-foreground" />
                    </Link>
                </div>
            </Section>

            <!-- ── Pagination (satu untuk semua section) ─────────────── -->
            <div
                v-if="pengadaan.last_page > 1"
                class="flex items-center justify-between rounded-lg border border-border bg-card px-5 py-3"
            >
                <div class="text-xs text-muted-foreground">
                    Menampilkan {{ pengadaan.from }}–{{ pengadaan.to }} dari {{ pengadaan.total }}
                </div>
                <div class="flex gap-1">
                    <template v-for="(link, idx) in pengadaan.links" :key="idx">
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
        </template>
    </div>
</template>