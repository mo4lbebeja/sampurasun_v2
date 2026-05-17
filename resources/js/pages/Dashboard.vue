<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import {
    FileText, Stamp, ShoppingCart, CheckCircle2, TrendingUp,
    Plus, Clock, Wallet, AlertCircle, Star, FileCheck, BarChart3,
} from 'lucide-vue-next';

import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import StatusBadge from '@/components/ev/StatusBadge.vue';
import EmptyState from '@/components/ev/EmptyState.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] },
});

type Stats = {
    total_usulan: number;
    menunggu_approval: number;
    dalam_proses: number;
    selesai_bulan_ini: number;
    total_estimasi: number;
    baru_bulan_ini: number;
    total_pagu: number;
    total_komitmen: number;
    total_realisasi: number;
    total_terpakai: number;
    total_sisa: number;
    persen_realisasi: number;
    persen_terpakai: number;
    pembayaran_bulan_ini: number;
};

type Usulan = {
    id: number; no_usulan: string; judul: string; tanggal_usulan: string;
    total_estimasi: string; status: string;
    pemohon: { id: number; name: string } | null;
};

type ActivityItem = {
    type: 'approval' | 'submission';
    keputusan: string | null; actor: string;
    no_usulan: string; judul: string;
    usulan_id: number | null; timestamp: string;
};

type MetodeItem = { metode: string; count: number; total_nilai: number };

type RoleData = {
    role: string;
    draft_count?: number;
    approval_pending?: number; approval_overdue?: number;
    pengadaan_aktif?: number; nilai_kontrak_aktif?: number;
    dokumen_incomplete?: number; dokumen_complete_bulan_ini?: number;
    pembayaran_pending?: number; nilai_pembayaran_pending?: number; pembayaran_lunas_bulan_ini?: number;
    evaluasi_pending?: number; rata_rating_penyedia?: number | null; evaluasi_bulan_ini?: number;
};

const props = withDefaults(
    defineProps<{
        stats?: Stats;
        recentUsulan?: Usulan[];
        statusDistribution?: Record<string, number>;
        activityFeed?: ActivityItem[];
        metodeDistribution?: MetodeItem[];
        roleData?: RoleData;
        userRole?: string;
        workflowCounts?: {          // ← tambahkan di sini, dalam defineProps
            usulan: number;
            approval: number;
            pengadaan: number;
            dokumen: number;
            pembayaran: number;
            evaluasi: number;
        };
    }>(),
    {
        stats: () => ({
            total_usulan: 0, menunggu_approval: 0, dalam_proses: 0, selesai_bulan_ini: 0,
            total_estimasi: 0, baru_bulan_ini: 0,
            total_pagu: 0, total_komitmen: 0, total_realisasi: 0,
            total_terpakai: 0, total_sisa: 0,
            persen_realisasi: 0, persen_terpakai: 0,
            pembayaran_bulan_ini: 0,
        }),
        recentUsulan: () => [],
        statusDistribution: () => ({}),
        activityFeed: () => [],
        metodeDistribution: () => [],
        roleData: () => ({ role: '' }),
        userRole: '',
        workflowCounts: () => ({    // ← tambahkan di sini, dalam objek defaults
            usulan: 0,
            approval: 0,
            pengadaan: 0,
            dokumen: 0,
            pembayaran: 0,
            evaluasi: 0,
        }),
    },
);

const page = usePage();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value?.is_admin === true);

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 11) return 'Selamat pagi';
    if (hour < 15) return 'Selamat siang';
    if (hour < 18) return 'Selamat sore';
    return 'Selamat malam';
});

const formatRupiahFull = (val: string | number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(val) || 0);

const formatRupiahCompact = (val: string | number) => formatRupiahFull(val);

const formatDate = (val: string) =>
    new Date(val).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

const formatRelative = (val: string | null | undefined): string => {
    if (!val) return '—';
    const d = new Date(val);
    if (isNaN(d.getTime())) return '—';
    const diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return 'Baru saja';
    if (diff < 3600) return `${Math.floor(diff / 60)} menit lalu`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`;
    if (diff < 604800) return `${Math.floor(diff / 86400)} hari lalu`;
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
};

const activityFeedLimited = computed(() => props.activityFeed.slice(0, 6));

const formatMetode = (val: string) =>
    val.split('_').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');

const workflowStages = computed(() => [
    { num: 1, name: 'Usulan',       role: 'Sarana & Umum',     count: props.workflowCounts?.usulan     ?? 0, href: '/usulan' },
    { num: 2, name: 'Approval',     role: 'PPTK',              count: props.workflowCounts?.approval   ?? 0, href: '/approval' },
    { num: 3, name: 'Pengadaan',    role: 'Pejabat Pengadaan', count: props.workflowCounts?.pengadaan  ?? 0, href: '/pengadaan?status=proses' },
    { num: 4, name: 'Dokumen UPBJ', role: 'UPBJ',              count: props.workflowCounts?.dokumen    ?? 0, href: '/dokumen' },
    { num: 5, name: 'Pembayaran',   role: 'Keuangan',          count: props.workflowCounts?.pembayaran ?? 0, href: '/pembayaran' },
    { num: 6, name: 'Selesai',      role: 'Perencanaan',       count: props.workflowCounts?.evaluasi   ?? 0, href: '/pembayaran?status=lunas' },
]);

const quickAction = computed(() => {
    if (props.userRole === 'sarana_umum' || isAdmin.value) {
        return { label: 'Buat Usulan', href: '/usulan/create', icon: Plus };
    }
    if (props.userRole === 'pptk') return { label: 'Lihat Approval', href: '/approval', icon: Stamp };
    if (props.userRole === 'pejabat_pengadaan') return { label: 'Lihat Pengadaan', href: '/pengadaan', icon: ShoppingCart };
    if (props.userRole === 'upbj') return { label: 'Lihat Dokumen', href: '/dokumen', icon: FileCheck };
    if (props.userRole === 'keuangan') return { label: 'Lihat Pembayaran', href: '/pembayaran', icon: Wallet };
    if (props.userRole === 'perencanaan') return { label: 'Lihat Evaluasi', href: '/evaluasi', icon: Star };
    return null;
});

const activityMeta = (item: ActivityItem) => {
    if (item.type === 'submission') return { color: 'bg-[var(--color-brand-info)]', label: 'Mengajukan' };
    if (item.keputusan === 'disetujui') return { color: 'bg-[var(--color-brand-success)]', label: 'Menyetujui' };
    if (item.keputusan === 'ditolak') return { color: 'bg-[var(--color-brand-danger)]', label: 'Menolak' };
    return { color: 'bg-[var(--color-brand-warning)]', label: 'Meminta revisi untuk' };
};

const showRoleSection = computed(() => {
    const data = props.roleData;
    if (!data) return false;
    return Object.keys(data).length > 1;
});

const totalMetodeCount = computed(() =>
    props.metodeDistribution.reduce((sum, m) => sum + m.count, 0),
);

const metodeWithPercent = computed(() =>
    props.metodeDistribution.map((m) => ({
        ...m,
        percent: totalMetodeCount.value > 0
            ? Math.round((m.count / totalMetodeCount.value) * 100)
            : 0,
    })),
);

const metodeColor = (idx: number) => {
    const colors = [
        'var(--color-brand-primary)',
        'var(--color-brand-info)',
        'var(--color-brand-success)',
        'var(--color-brand-copper)',
        'var(--color-brand-warning)',
        'var(--color-brand-muted)',
    ];
    return colors[idx % colors.length];
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <!-- Header dengan greeting -->
        <PageHeader
            :title="`${greeting}, ${user?.name?.split(' ')[0] ?? ''}`"
            :subtitle="`Berikut ringkasan aktivitas pengadaan ${user?.unit_kerja ? '— ' + user.unit_kerja : ''}`"
            eyebrow="Dashboard"
        >
            <template v-if="quickAction" #actions>
                <Link :href="quickAction.href">
                    <PrimaryButton variant="primary">
                        <component :is="quickAction.icon" class="h-4 w-4" />
                        {{ quickAction.label }}
                    </PrimaryButton>
                </Link>
            </template>
        </PageHeader>

        <!-- 4 KPI Cards (count + nilai rupiah compressed) -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Card 1 -->
            <div class="relative overflow-hidden rounded-lg border border-border bg-card p-5">
                <div class="absolute inset-x-0 top-0 h-1 bg-primary" />
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="text-eyebrow">Total Usulan</div>
                        <div class="mt-2 font-display text-3xl font-bold leading-none">
                            {{ stats.total_usulan }}
                        </div>
                        <div class="mt-1 text-xs text-muted-foreground">
                            <span v-if="stats.baru_bulan_ini > 0" class="font-semibold text-foreground">
                                +{{ stats.baru_bulan_ini }}
                            </span>
                            <span v-else>Belum ada</span>
                            bulan ini
                        </div>
                        <div class="mt-2.5 border-t border-border pt-2.5">
                            <div class="text-eyebrow">Estimasi Aktif</div>
                            <div class="mt-0.5 font-mono text-sm font-semibold text-primary">
                                {{ formatRupiahCompact(stats.total_estimasi) }}
                            </div>
                        </div>
                    </div>
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <FileText class="h-4 w-4" />
                    </div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="relative overflow-hidden rounded-lg border border-border bg-card p-5">
                <div class="absolute inset-x-0 top-0 h-1" style="background-color: var(--color-brand-warning);" />
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="text-eyebrow">Menunggu Approval</div>
                        <div class="mt-2 font-display text-3xl font-bold leading-none">
                            {{ stats.menunggu_approval }}
                        </div>
                        <div class="mt-1 text-xs text-muted-foreground">
                            {{ stats.menunggu_approval > 0 ? 'Perlu diproses' : 'Semua sudah ditangani' }}
                        </div>
                        <div class="mt-2.5 border-t border-border pt-2.5">
                            <div class="text-eyebrow">Komitmen Kontrak</div>
                            <div class="mt-0.5 font-mono text-sm font-semibold" style="color: var(--color-brand-info);">
                                {{ formatRupiahCompact(stats.total_komitmen) }}
                            </div>
                        </div>
                    </div>
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-warning);"
                    >
                        <Stamp class="h-4 w-4" />
                    </div>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="relative overflow-hidden rounded-lg border border-border bg-card p-5">
                <div class="absolute inset-x-0 top-0 h-1" style="background-color: var(--color-brand-info);" />
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="text-eyebrow">Dalam Proses</div>
                        <div class="mt-2 font-display text-3xl font-bold leading-none">
                            {{ stats.dalam_proses }}
                        </div>
                        <div class="mt-1 text-xs text-muted-foreground">Sedang berjalan</div>
                        <div class="mt-2.5 border-t border-border pt-2.5">
                            <div class="text-eyebrow">Realisasi Bayar</div>
                            <div class="mt-0.5 font-mono text-sm font-semibold" style="color: var(--color-brand-success);">
                                {{ formatRupiahCompact(stats.total_realisasi) }}
                            </div>
                        </div>
                    </div>
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-info);"
                    >
                        <ShoppingCart class="h-4 w-4" />
                    </div>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="relative overflow-hidden rounded-lg border border-border bg-card p-5">
                <div class="absolute inset-x-0 top-0 h-1" style="background-color: var(--color-brand-success);" />
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="text-eyebrow">Selesai Bulan Ini</div>
                        <div class="mt-2 font-display text-3xl font-bold leading-none">
                            {{ stats.selesai_bulan_ini }}
                        </div>
                        <div class="mt-1 text-xs text-muted-foreground">Tuntas evaluasi</div>
                        <div class="mt-2.5 border-t border-border pt-2.5">
                            <div class="text-eyebrow">Sisa Anggaran</div>
                            <div class="mt-0.5 font-mono text-sm font-semibold text-primary">
                                {{ formatRupiahCompact(stats.total_sisa) }}
                            </div>
                        </div>
                    </div>
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-white"
                        style="background-color: var(--color-brand-success);"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Anggaran progress banner -->
        <div v-if="stats.total_pagu > 0" class="rounded-lg border border-border bg-card p-5">
            <div class="mb-3 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-md bg-accent/10 text-accent">
                        <TrendingUp class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-eyebrow">Realisasi Anggaran {{ new Date().getFullYear() }}</div>
                        <div class="font-display text-xl font-semibold">
                            {{ stats.persen_terpakai }}%
                            <span class="text-base text-muted-foreground">dari pagu</span>
                        </div>
                    </div>
                </div>
                <div class="hidden text-right text-xs text-muted-foreground sm:block">
                    Total Pagu<br>
                    <span class="font-mono text-sm font-semibold text-foreground">
                        {{ formatRupiahFull(stats.total_pagu) }}
                    </span>
                </div>
            </div>

            <div class="relative h-3 overflow-hidden rounded-full bg-secondary">
                <div class="absolute left-0 top-0 h-full transition-all"
                    :style="`width: ${stats.persen_realisasi}%; background-color: var(--color-brand-success);`" />
                <div class="absolute top-0 h-full transition-all"
                    :style="`left: ${stats.persen_realisasi}%; width: ${stats.persen_terpakai - stats.persen_realisasi}%; background-color: var(--color-brand-info);`" />
            </div>

            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs">
                <div class="flex items-center gap-1.5">
                    <span class="inline-block h-2 w-2 rounded-full" style="background-color: var(--color-brand-success);" />
                    <span class="text-muted-foreground">Realisasi: <span class="font-semibold text-foreground">{{ stats.persen_realisasi }}%</span></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block h-2 w-2 rounded-full" style="background-color: var(--color-brand-info);" />
                    <span class="text-muted-foreground">Komitmen: <span class="font-semibold text-foreground">{{ (stats.persen_terpakai - stats.persen_realisasi).toFixed(1) }}%</span></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block h-2 w-2 rounded-full bg-secondary ring-1 ring-border" />
                    <span class="text-muted-foreground">Tersedia: <span class="font-semibold text-foreground">{{ (100 - stats.persen_terpakai).toFixed(1) }}%</span></span>
                </div>
            </div>
        </div>

        <!-- Workflow 6 Tahap -->
        <!-- Ganti description di Section Distribusi Tahapan -->
        <Section
            title="Distribusi Tahapan Workflow"
            eyebrow="Status Real-time"
            description="Tahap 1–2: jumlah usulan · Tahap 3–6: jumlah paket pengadaan"
        >
            <div class="flex flex-col gap-2 lg:flex-row lg:items-stretch">
                <template v-for="(stage, idx) in workflowStages" :key="stage.num">
                    <Link
                        :href="stage.href"
                        class="flex flex-1 flex-col gap-2 rounded-md border p-3 transition hover:shadow-sm"
                        :class="stage.count > 0
                            ? 'border-primary/30 bg-primary/5 hover:border-primary hover:bg-primary/10'
                            : 'border-border bg-secondary/30 hover:border-muted-foreground/30'"
                    >
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full font-display text-sm font-semibold"
                                :class="stage.count > 0 ? 'bg-primary text-primary-foreground' : 'bg-card text-muted-foreground ring-1 ring-border'"
                            >{{ stage.num }}</div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium"
                                    :class="stage.count > 0 ? 'text-foreground' : 'text-muted-foreground'"
                                >{{ stage.name }}</div>
                                <div class="truncate text-[10px] uppercase tracking-wider text-muted-foreground">{{ stage.role }}</div>
                            </div>
                        </div>
                        <div class="font-display text-2xl font-semibold leading-none"
                            :class="stage.count > 0 ? 'text-primary' : 'text-muted-foreground'"
                        >{{ stage.count }}</div>
                        <!-- Tambahkan ini: -->
                        <div class="text-[10px] text-muted-foreground">
                            {{ stage.num <= 2 ? 'usulan' : 'paket' }}
                        </div>
                    </Link>
                    <div v-if="idx < workflowStages.length - 1" class="hidden items-center text-border lg:flex">→</div>
                </template>
            </div>
        </Section>

        <!-- Per-role customization -->
        <Section v-if="showRoleSection" :title="`Untuk Anda: ${userRole}`" eyebrow="Tindakan Prioritas">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link v-if="(userRole === 'sarana_umum' || isAdmin) && roleData.draft_count !== undefined"
                    href="/usulan?status=draft" class="rounded-md border border-border bg-card p-4 transition hover:border-primary"
                >
                    <div class="flex items-start gap-3">
                        <FileText class="h-5 w-5 shrink-0 text-primary" />
                        <div class="min-w-0">
                            <div class="text-eyebrow">Draft Saya</div>
                            <div class="font-display text-2xl font-bold">{{ roleData.draft_count }}</div>
                            <div class="text-xs text-muted-foreground">Usulan belum diajukan</div>
                        </div>
                    </div>
                </Link>

                <Link v-if="(userRole === 'pptk' || isAdmin) && roleData.approval_pending !== undefined"
                    href="/approval"
                    class="rounded-md border p-4 transition"
                    :class="(roleData.approval_overdue ?? 0) > 0 ? 'border-[var(--color-brand-danger)] bg-[var(--color-brand-danger-bg)]' : 'border-border bg-card hover:border-primary'"
                >
                    <div class="flex items-start gap-3">
                        <Stamp class="h-5 w-5 shrink-0"
                            :style="(roleData.approval_overdue ?? 0) > 0 ? 'color: var(--color-brand-danger);' : 'color: var(--color-brand-warning);'" />
                        <div class="min-w-0">
                            <div class="text-eyebrow">Approval Pending</div>
                            <div class="font-display text-2xl font-bold">{{ roleData.approval_pending }}</div>
                            <div v-if="(roleData.approval_overdue ?? 0) > 0" class="text-xs font-semibold"
                                style="color: var(--color-brand-danger);"
                            >⚠ {{ roleData.approval_overdue }} sudah > 3 hari</div>
                            <div v-else class="text-xs text-muted-foreground">Menunggu keputusan</div>
                        </div>
                    </div>
                </Link>

                <Link v-if="(userRole === 'pejabat_pengadaan' || isAdmin) && roleData.pengadaan_aktif !== undefined"
                    href="/pengadaan" class="rounded-md border border-border bg-card p-4 transition hover:border-primary"
                >
                    <div class="flex items-start gap-3">
                        <ShoppingCart class="h-5 w-5 shrink-0" style="color: var(--color-brand-info);" />
                        <div class="min-w-0">
                            <div class="text-eyebrow">Pengadaan Aktif</div>
                            <div class="font-display text-2xl font-bold">{{ roleData.pengadaan_aktif }}</div>
                            <div class="font-mono text-xs text-muted-foreground">
                                {{ formatRupiahCompact(roleData.nilai_kontrak_aktif ?? 0) }}
                            </div>
                        </div>
                    </div>
                </Link>

                <Link v-if="(userRole === 'upbj' || isAdmin) && roleData.dokumen_incomplete !== undefined"
                    href="/dokumen"
                    class="rounded-md border p-4 transition"
                    :class="(roleData.dokumen_incomplete ?? 0) > 0 ? 'border-[var(--color-brand-warning)] bg-[var(--color-brand-warning-bg)]' : 'border-border bg-card hover:border-primary'"
                >
                    <div class="flex items-start gap-3">
                        <FileCheck class="h-5 w-5 shrink-0"
                            :style="(roleData.dokumen_incomplete ?? 0) > 0 ? 'color: var(--color-brand-warning);' : 'color: var(--color-brand-success);'" />
                        <div class="min-w-0">
                            <div class="text-eyebrow">Dokumen Belum Lengkap</div>
                            <div class="font-display text-2xl font-bold">{{ roleData.dokumen_incomplete }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ roleData.dokumen_complete_bulan_ini ?? 0 }} selesai bulan ini
                            </div>
                        </div>
                    </div>
                </Link>

                <Link v-if="(userRole === 'keuangan' || isAdmin) && roleData.pembayaran_pending !== undefined"
                    href="/pembayaran"
                    class="rounded-md border p-4 transition"
                    :class="(roleData.pembayaran_pending ?? 0) > 0 ? 'border-[var(--color-brand-info)] bg-[var(--color-brand-info-bg)]' : 'border-border bg-card hover:border-primary'"
                >
                    <div class="flex items-start gap-3">
                        <Wallet class="h-5 w-5 shrink-0" style="color: var(--color-brand-info);" />
                        <div class="min-w-0">
                            <div class="text-eyebrow">Pembayaran Pending</div>
                            <div class="font-display text-2xl font-bold">{{ roleData.pembayaran_pending }}</div>
                            <div class="font-mono text-xs text-muted-foreground">
                                {{ formatRupiahCompact(roleData.nilai_pembayaran_pending ?? 0) }}
                            </div>
                        </div>
                    </div>
                </Link>

                <Link v-if="(userRole === 'perencanaan' || isAdmin) && roleData.evaluasi_pending !== undefined"
                    href="/evaluasi" class="rounded-md border border-border bg-card p-4 transition hover:border-primary"
                >
                    <div class="flex items-start gap-3">
                        <Star class="h-5 w-5 shrink-0" style="color: var(--color-brand-warning);" />
                        <div class="min-w-0">
                            <div class="text-eyebrow">Evaluasi Pending</div>
                            <div class="font-display text-2xl font-bold">{{ roleData.evaluasi_pending }}</div>
                            <div v-if="roleData.rata_rating_penyedia" class="text-xs text-muted-foreground">
                                ⭐ {{ roleData.rata_rating_penyedia }}/5 rata-rata
                            </div>
                            <div v-else class="text-xs text-muted-foreground">Belum ada evaluasi</div>
                        </div>
                    </div>
                </Link>
            </div>
        </Section>

        <!-- Distribusi Metode -->
        <Section v-if="metodeWithPercent.length > 0" title="Distribusi Metode Pengadaan" eyebrow="Analitik"
            description="Metode pengadaan yang paling sering digunakan"
        >
            <div class="space-y-3">
                <div v-for="(m, idx) in metodeWithPercent" :key="m.metode" class="space-y-1.5">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-3 w-3 rounded-sm" :style="`background-color: ${metodeColor(idx)};`" />
                            <span class="font-medium">{{ formatMetode(m.metode) }}</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-muted-foreground">
                            <span>{{ m.count }} pengadaan</span>
                            <span class="font-mono">{{ formatRupiahCompact(m.total_nilai) }}</span>
                            <span class="font-mono font-semibold text-foreground">{{ m.percent }}%</span>
                        </div>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-secondary">
                        <div class="h-full rounded-full transition-all"
                            :style="`width: ${m.percent}%; background-color: ${metodeColor(idx)};`" />
                    </div>
                </div>
            </div>
        </Section>

        <!-- Recent Usulan + Activity Feed -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <Section title="Usulan Terbaru" eyebrow="Aktivitas">
                    <template #actions>
                        <Link href="/usulan" class="text-xs font-semibold text-primary hover:underline">
                            Lihat semua →
                        </Link>
                    </template>

                    <EmptyState v-if="recentUsulan.length === 0"
                        title="Belum ada usulan" description="Usulan terbaru akan muncul di sini." :icon="FileText" />

                    <div v-else class="-mx-6 -my-4 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-border text-left text-[10px] uppercase tracking-widest text-muted-foreground">
                                    <th class="px-6 py-3 font-semibold">No. Usulan</th>
                                    <th class="px-6 py-3 font-semibold">Judul</th>
                                    <th class="px-6 py-3 text-right font-semibold">Estimasi</th>
                                    <th class="px-6 py-3 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="row in recentUsulan" :key="row.id"
                                    class="cursor-pointer transition hover:bg-muted/30"
                                    @click="$inertia.visit(`/usulan/${row.id}`)"
                                >
                                    <td class="whitespace-nowrap px-6 py-3 font-mono text-xs">{{ row.no_usulan }}</td>
                                    <td class="px-6 py-3">
                                        <div class="font-medium">{{ row.judul }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ row.pemohon?.name ?? '—' }} · {{ formatDate(row.tanggal_usulan) }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-3 text-right font-mono text-xs">
                                        {{ formatRupiahCompact(row.total_estimasi) }}
                                    </td>
                                    <td class="px-6 py-3">
                                        <StatusBadge :status="row.status" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Section>
            </div>

            <div>
                <Section title="Aktivitas Terkini" eyebrow="Audit Trail">
                    <EmptyState
                        v-if="activityFeed.length === 0"
                        title="Belum ada aktivitas"
                        description="Aktivitas pengguna akan muncul di sini."
                        :icon="Clock"
                    />
            
                    <template v-else>
                        <!-- List dibatasi 6 item, overflow scroll -->
                        <ul class="-mx-6 max-h-[420px] divide-y divide-border overflow-y-auto">
                            <li
                                v-for="(item, idx) in activityFeedLimited"
                                :key="`activity-${idx}`"
                                class="flex gap-3 px-6 py-3"
                            >
                                <!-- Dot + connector line -->
                                <div class="relative flex flex-col items-center">
                                    <div
                                        class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                                        :class="activityMeta(item).color"
                                    />
                                    <div
                                        v-if="idx < activityFeedLimited.length - 1"
                                        class="mt-1 w-px flex-1 bg-border"
                                    />
                                </div>
            
                                <!-- Konten -->
                                <div class="min-w-0 flex-1 pb-3">
                                    <!-- FIX spasi: gunakan string interpolation, bukan dua <span> -->
                                    <div class="text-sm leading-snug">
                                        <span class="font-medium">{{ item.actor }}</span>
                                        <span class="text-muted-foreground">
                                            {{ ' ' + activityMeta(item).label.toLowerCase() }}
                                        </span>
                                    </div>
            
                                    <!-- Link ke usulan -->
                                    <Link
                                        v-if="item.usulan_id"
                                        :href="`/usulan/${item.usulan_id}`"
                                        class="mt-0.5 block truncate text-xs font-medium text-primary hover:underline"
                                    >
                                        {{ item.no_usulan }} · {{ item.judul }}
                                    </Link>
            
                                    <!-- Waktu relatif — aman dari null/Invalid Date -->
                                    <div class="mt-1 text-[11px] text-muted-foreground">
                                        {{ formatRelative(item.timestamp) }}
                                    </div>
                                </div>
                            </li>
                        </ul>
            
                        <!-- Footer: tampilkan info jika ada lebih dari 6 -->
                        <div
                            v-if="activityFeed.length > 6"
                            class="-mx-6 -mb-6 border-t border-border bg-muted/20 px-6 py-2.5 text-center text-xs text-muted-foreground"
                        >
                            Menampilkan 6 dari {{ activityFeed.length }} aktivitas terbaru
                        </div>
                    </template>
                </Section>
            </div>
        </div>
    </div>
</template>