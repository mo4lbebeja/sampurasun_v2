<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Breadcrumbs      from '@/components/Breadcrumbs.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page         = usePage();
const tahun        = computed(() => (page.props as Record<string, unknown>).tahunAnggaranAktif ?? '');
const userRoleLabel = computed(() => {
    const auth = (page.props as Record<string, unknown>).auth as { user?: { role_label?: string } } | undefined;
    return auth?.user?.role_label ?? '';
});

// Isi running text — bisa dikustomisasi sesuai kebutuhan
const runningTexts = computed(() => [
    `✦ Sistem Administrasi Manajemen Perencanaan tur Realisasi Anggaran nu Nyata`,
    `✦ Tahun Anggaran ${tahun.value}`,
    `✦ Pastikan setiap dokumen pengadaan telah diverifikasi sebelum dikirim`,
    `✦ Selamat bekerja — ${userRoleLabel.value}`,
]);

const tickerText = computed(() => runningTexts.value.join('     '));
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <!-- Kiri: trigger + breadcrumb -->
        <div class="flex shrink-0 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Tengah: Running text / ticker -->
        <div class="mx-4 min-w-0 flex-1 overflow-hidden">
            <div class="ticker-wrapper">
                <span class="ticker-text text-xs text-muted-foreground">
                    {{ tickerText }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ tickerText }}
                </span>
            </div>
        </div>

        <!-- Kanan: notification bell -->
        <div class="flex shrink-0 items-center gap-1">
            <NotificationBell />
        </div>
    </header>
</template>

<style scoped>
.ticker-wrapper {
    width: 100%;
    overflow: hidden;
    white-space: nowrap;
}

.ticker-text {
    display: inline-block;
    animation: ticker 40s linear infinite;
    padding-left: 100%;
}

.ticker-text:hover {
    animation-play-state: paused;
}

@keyframes ticker {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
</style>