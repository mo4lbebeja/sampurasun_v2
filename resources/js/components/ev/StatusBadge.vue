<script setup lang="ts">
import { computed } from 'vue';

type StatusKey =
    // Usulan
    | 'draft' | 'diajukan' | 'disetujui' | 'ditolak'
    | 'dalam_pengadaan' | 'dokumen' | 'pembayaran'
    | 'evaluasi' | 'selesai' | 'dibatalkan'
    // Pengadaan
    | 'proses' | 'kontrak' | 'batal'
    // Approval keputusan
    | 'revisi'
    // Generic
    | 'aktif' | 'nonaktif';

const props = defineProps<{
    status: string;
    label?: string; // override label kalau perlu
}>();

// Mapping status ke variant badge
const variantMap: Record<string, 'success' | 'warning' | 'danger' | 'info' | 'muted'> = {
    // Usulan flow
    draft:           'muted',
    diajukan:        'warning',
    disetujui:       'success',
    ditolak:         'danger',
    dalam_pengadaan: 'info',
    dokumen:         'info',
    pembayaran:      'info',
    evaluasi:        'info',
    selesai:         'success',
    dibatalkan:      'muted',

    // Pengadaan flow
    proses:  'warning',
    kontrak: 'info',
    batal:   'muted',

    // Approval keputusan
    revisi: 'warning',

    // Generic
    aktif:    'success',
    nonaktif: 'muted',
};

const labelMap: Record<string, string> = {
    dalam_pengadaan: 'Dalam Pengadaan',
    dibatalkan: 'Dibatalkan',
};

const variant = computed(() => variantMap[props.status] ?? 'muted');

const displayLabel = computed(() => {
    if (props.label) return props.label;
    if (labelMap[props.status]) return labelMap[props.status];
    // Default: title-case dari status (snake_case → Title Case)
    return props.status
        .split('_')
        .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
        .join(' ');
});
</script>

<template>
    <span class="badge-status" :class="`badge-${variant}`">
        {{ displayLabel }}
    </span>
</template>