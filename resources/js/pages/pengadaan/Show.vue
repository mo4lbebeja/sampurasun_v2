<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    ExternalLink,
    FileText,
    ShoppingCart,
    Upload,
    X,
} from 'lucide-vue-next';

import PageHeader          from '@/components/ev/PageHeader.vue';
import Section             from '@/components/ev/Section.vue';
import StatusBadge         from '@/components/ev/StatusBadge.vue';
import PrimaryButton       from '@/components/ev/PrimaryButton.vue';
import PenyediaSearchSelect from '@/components/ev/PenyediaSearchSelect.vue';

// ── Types ──────────────────────────────────────────────────────────

type PenyediaOption = {
    id: number; nama: string; jenis_badan?: string | null;
    npwp?: string | null; nama_pic?: string | null;
    alamat?: string | null; telepon?: string | null;
};

type Penyedia = {
    id: number; nama: string; jenis_badan: string | null;
    npwp: string | null; nama_pic: string | null;
    alamat?: string | null; telepon?: string | null;
};

type PejabatOption = {
    id: number; name: string; nip?: string | null;
    jabatan?: string | null; alamat?: string | null;
};

type SiblingPaket = {
    id: number; no_pengadaan: string; nama_paket: string | null;
    status: string; metode: string;
    estimasi_paket: string | number; nilai_kontrak: string | number;
};

// Item usulan dengan pivot harga kontrak
type ItemWithPivot = {
    id: number; nama_barang: string; spesifikasi: string | null;
    satuan: string; jumlah: number;
    harga_satuan_estimasi: string | number;
    subtotal: string | number;
    kategori: { id: number; nama: string } | null;
    pivot?: { harga_satuan_kontrak: string | number };
};

type Pengadaan = {
    id: number; no_pengadaan: string; nama_paket: string | null;
    estimasi_paket: string | number; metode: string;
    tanggal_mulai: string; tanggal_selesai: string | null;
    no_kontrak: string | null; tanggal_kontrak: string | null;
    nilai_kontrak: string | number | null;
    file_kontrak: string | null; file_hps: string | null;
    status: string; catatan: string | null;
    pejabat_penandatangan_id?: number | null;
    pejabat_penandatangan?: PejabatOption | null;
    kpa_penandatangan_id?: number | null;
    kpa_penandatangan?: PejabatOption | null;
    usulan: {
        id: number; no_usulan: string; judul: string;
        total_estimasi: string | number; status: string;
        pemohon: { id: number; name: string; unit_kerja: { id: number; nama: string } | null } | null;
        items: ItemWithPivot[];
        pengadaan: SiblingPaket[];
    } | null;
    usulanItems?: ItemWithPivot[];  // items paket dengan pivot harga kontrak
    pejabat: { id: number; name: string; jabatan: string | null } | null;
    penyedia: Penyedia | null;
};

// ── Props ──────────────────────────────────────────────────────────

const props = withDefaults(
    defineProps<{
        pengadaan:        Pengadaan;
        penyediaList?:    PenyediaOption[];
        penyediaOptions?: PenyediaOption[];
        pejabatOptions?:  PejabatOption[];
        kpaOptions?:      PejabatOption[];
        itemIdsInPaket?:  number[];
    }>(),
    {
        penyediaList:    () => [],
        penyediaOptions: () => [],
        pejabatOptions:  () => [],
        kpaOptions:      () => [],
        itemIdsInPaket:  () => [],
    },
);

// ── Status helpers ─────────────────────────────────────────────────

const isProses  = computed(() => props.pengadaan.status === 'proses');
const isKontrak = computed(() => props.pengadaan.status === 'kontrak');
const isSelesai = computed(() => props.pengadaan.status === 'selesai');
const isBatal   = computed(() => props.pengadaan.status === 'batal');

const canInputKontrak         = computed(() => isProses.value);
const canUploadDokumenKontrak = computed(() =>
    ['proses', 'kontrak', 'dokumen'].includes(props.pengadaan.status)
);
const isEditable = computed(() => canInputKontrak.value);

// ── Multi-paket ────────────────────────────────────────────────────

const itemsInPaket = computed((): ItemWithPivot[] =>
    (props.pengadaan.usulan?.items ?? []).filter((item) =>
        (props.itemIdsInPaket ?? []).includes(item.id)
    )
);

const siblingPakets = computed(() =>
    (props.pengadaan.usulan?.pengadaan ?? []).filter((p) => p.id !== props.pengadaan.id)
);

const isMultiPaket = computed(() =>
    (props.pengadaan.usulan?.pengadaan?.length ?? 0) > 1
);

const labelPaket = computed(() =>
    props.pengadaan.nama_paket || props.pengadaan.no_pengadaan
);

// ── Harga kontrak per item (Tahap 2) ──────────────────────────────

// Prioritas: usulanItems dari relasi pivot (sudah ada harga kontrak)
// Fallback: itemsInPaket dari item assignment
// Fallback terakhir: semua item usulan
const itemsForKontrak = computed((): ItemWithPivot[] => {
    // Prioritas 1: dari relasi pivot — paling akurat, hanya item paket ini
    if (props.pengadaan.usulanItems && props.pengadaan.usulanItems.length > 0) {
        return props.pengadaan.usulanItems;
    }
    // Prioritas 2: dari itemIdsInPaket
    if (itemsInPaket.value.length > 0) {
        return itemsInPaket.value;
    }
    // Prioritas 3: semua item usulan HANYA jika single-paket
    // Di multi-paket jangan fallback ke semua item — item paket lain bisa ikut masuk
    if (!isMultiPaket.value) {
        return props.pengadaan.usulan?.items ?? [];
    }
    // Multi-paket tapi tidak ada data assignment → kosong saja
    return [];
});

const pakaiItemKontrak = computed(() => itemsForKontrak.value.length > 0);

// ── Form state ─────────────────────────────────────────────────────

const kontrakForm = useForm({
    penyedia_id:              props.pengadaan.penyedia?.id               ?? null as number | null,
    pejabat_penandatangan_id: props.pengadaan.pejabat_penandatangan?.id  ?? null as number | null,
    kpa_penandatangan_id:     props.pengadaan.kpa_penandatangan?.id      ?? null as number | null,
    no_kontrak:               props.pengadaan.no_kontrak                 ?? '',
    tanggal_kontrak:          props.pengadaan.tanggal_kontrak            ?? new Date().toISOString().slice(0, 10),
    tanggal_selesai:          props.pengadaan.tanggal_selesai            ?? '',
    catatan:                  props.pengadaan.catatan                    ?? '',
    file_kontrak:             null as File | null,
    file_hps:                 null as File | null,

    // Harga per item — dikirim ke backend, nilai_kontrak dihitung di sana
    item_kontrak: itemsForKontrak.value.map((item) => ({
        usulan_item_id:       item.id,
        // Pre-fill dari harga kontrak yang sudah tersimpan (jika ada),
        // fallback ke harga estimasi
        harga_satuan_kontrak: Number(item.pivot?.harga_satuan_kontrak) > 0
            ? Number(item.pivot!.harga_satuan_kontrak)
            : Number(item.harga_satuan_estimasi),
    })),

    // Dipakai hanya jika paket tidak punya item assignment
    nilai_kontrak: Number(props.pengadaan.nilai_kontrak) || 0,
});

// ── File upload ────────────────────────────────────────────────────

const fileKontrakName = ref('');
const fileHpsName     = ref('');

const onFileKontrak = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    kontrakForm.file_kontrak = file;
    fileKontrakName.value = file?.name ?? '';
};

const onFileHps = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    kontrakForm.file_hps = file;
    fileHpsName.value = file?.name ?? '';
};

// ── Per-item helpers ───────────────────────────────────────────────

const getHargaKontrak = (itemId: number): number => {
    const found = kontrakForm.item_kontrak.find((i) => i.usulan_item_id === itemId);
    return found ? Number(found.harga_satuan_kontrak) : 0;
};

const setHargaKontrak = (itemId: number, nilai: number) => {
    const idx = kontrakForm.item_kontrak.findIndex((i) => i.usulan_item_id === itemId);
    if (idx !== -1) kontrakForm.item_kontrak[idx].harga_satuan_kontrak = nilai;
};

const gunakanSemuaEstimasi = () => {
    kontrakForm.item_kontrak = itemsForKontrak.value.map((item) => ({
        usulan_item_id:       item.id,
        harga_satuan_kontrak: Number(item.harga_satuan_estimasi),
    }));
};

// ── Nilai kontrak otomatis dari per-item ───────────────────────────

const nilaiKontrakAuto = computed((): number => {
    if (kontrakForm.item_kontrak.length === 0) return Number(kontrakForm.nilai_kontrak);
    return kontrakForm.item_kontrak.reduce((sum, ik) => {
        const item = itemsForKontrak.value.find((i) => i.id === ik.usulan_item_id);
        return sum + (Number(ik.harga_satuan_kontrak) * Number(item?.jumlah ?? 0));
    }, 0);
});

// ── Selisih vs estimasi paket ──────────────────────────────────────

const selisih = computed((): number => {
    const nilaiSekarang = isEditable.value
        ? nilaiKontrakAuto.value
        : Number(props.pengadaan.nilai_kontrak);
    const estimasiAcuan = Number(props.pengadaan.estimasi_paket) > 0
        ? Number(props.pengadaan.estimasi_paket)
        : Number(props.pengadaan.usulan?.total_estimasi ?? 0);
    return nilaiSekarang - estimasiAcuan;
});

const hasErrors = computed(() => Object.keys(kontrakForm.errors).length > 0);

// ── inputClass helper ──────────────────────────────────────────────

const inputClass = (field: keyof typeof kontrakForm.errors): string =>
    kontrakForm.errors[field]
        ? 'border-destructive focus:border-destructive focus:ring-destructive'
        : 'border-input focus:border-primary focus:ring-primary';

// ── Submit ─────────────────────────────────────────────────────────

const submitKontrak = () => {
    kontrakForm.post(`/pengadaan/${props.pengadaan.id}/kontrak`, {
        forceFormData: true,
        preserveScroll: true,
    });
};

const submitUploadDokumen = () => {
    if (!kontrakForm.file_kontrak && !kontrakForm.file_hps) {
        alert('Pilih minimal satu file terlebih dahulu: File Kontrak atau File HPS.');
        return;
    }
    kontrakForm.post(`/pengadaan/${props.pengadaan.id}/kontrak`, {
        forceFormData: true,
        preserveScroll: true,
    });
};

const cancelForm = useForm({});

const cancelPengadaan = () => {
    if (!confirm('Yakin batalkan pengadaan ini? Usulan akan dikembalikan ke status disetujui.'))
        return;
    cancelForm.post(`/pengadaan/${props.pengadaan.id}/cancel`, { preserveScroll: true });
};

// ── Format helpers ─────────────────────────────────────────────────

const formatRupiah = (val: number | string | null) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0,
    }).format(Number(val) || 0);

const formatDate = (val: string | null) => {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'long', year: 'numeric',
    });
};

const formatMetode = (val: string) =>
    val.split('_').map((w) => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');

// ── Banner config ──────────────────────────────────────────────────

const bannerConfig = computed(() => {
    if (isProses.value)  return { tone: 'warning', icon: ShoppingCart, title: 'Input Detail Kontrak',    description: 'Pilih penyedia, isi detail kontrak, dan upload dokumen. Setelah disimpan, pengadaan akan diteruskan ke Bagian UPBJ.' };
    if (isKontrak.value) return { tone: 'info',    icon: CheckCircle2, title: 'Kontrak Aktif',           description: 'Kontrak sudah ditandatangani. Pengadaan saat ini sedang dalam proses pengurusan dokumen UPBJ.' };
    if (isSelesai.value) return { tone: 'success', icon: CheckCircle2, title: 'Pengadaan Selesai',       description: 'Seluruh proses pengadaan telah tuntas — kontrak, dokumen, pembayaran, dan evaluasi sudah selesai.' };
    if (isBatal.value)   return { tone: 'muted',   icon: X,            title: 'Pengadaan Dibatalkan',    description: 'Pengadaan ini telah dibatalkan. Usulan dikembalikan ke status disetujui.' };
    return null;
});
</script>

<template>
    <Head :title="`Pengadaan ${pengadaan.no_pengadaan}`" />

    <form @submit.prevent="submitKontrak" class="space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Header -->
        <div class="flex items-start gap-3">
            <Link href="/pengadaan"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-card transition hover:bg-muted">
                <ArrowLeft class="h-4 w-4" />
            </Link>
            <div class="flex-1">
                <PageHeader :title="labelPaket" :eyebrow="pengadaan.no_pengadaan" :subtitle="pengadaan.usulan?.judul">
                    <template #actions>
                        <StatusBadge :status="pengadaan.status" />
                    </template>
                </PageHeader>
            </div>
        </div>

        <!-- Banner status -->
        <div v-if="bannerConfig" class="rounded-lg border p-4"
            :class="{
                'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-950': bannerConfig.tone === 'warning',
                'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-950':    bannerConfig.tone === 'info',
                'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-950': bannerConfig.tone === 'success',
                'border-border bg-muted/30': bannerConfig.tone === 'muted',
            }">
            <div class="flex items-start gap-3">
                <component :is="bannerConfig.icon" class="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    <div class="text-sm font-medium">{{ bannerConfig.title }}</div>
                    <p class="mt-0.5 text-xs text-muted-foreground">{{ bannerConfig.description }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">

            <!-- LEFT COLUMN -->
            <div class="space-y-6 xl:col-span-8">

                <!-- Info Paket (multi-paket) -->
                <Section v-if="isMultiPaket" :title="labelPaket" eyebrow="Info Paket"
                    :description="`Bagian dari usulan ${pengadaan.usulan?.no_usulan}`">
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <div>
                            <div class="text-eyebrow">Estimasi Paket</div>
                            <div class="mt-1 font-mono font-semibold">{{ formatRupiah(pengadaan.estimasi_paket) }}</div>
                        </div>
                        <div>
                            <div class="text-eyebrow">Jumlah Item</div>
                            <div class="mt-1 font-semibold">{{ itemsInPaket.length }} item</div>
                        </div>
                        <div>
                            <div class="text-eyebrow">Metode</div>
                            <div class="mt-1">{{ formatMetode(pengadaan.metode) }}</div>
                        </div>
                    </div>
                </Section>

                <!-- Detail Kontrak -->
                <Section title="Detail Kontrak" eyebrow="Kontrak">

                    <!-- Error summary -->
                    <div v-if="hasErrors"
                        class="-mx-6 -mt-6 mb-4 flex items-start gap-3 border-b border-destructive/30 bg-destructive/5 px-6 py-4">
                        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0 text-destructive" />
                        <div>
                            <p class="text-sm font-semibold text-destructive">Mohon lengkapi data berikut:</p>
                            <ul class="mt-1 space-y-0.5 text-xs text-destructive">
                                <li v-for="(msg, field) in kontrakForm.errors" :key="field">• {{ msg }}</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Form mode (editable) -->
                    <div v-if="isEditable" class="space-y-5">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                            <!-- Penyedia -->
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-semibold">
                                    Penyedia <span class="text-destructive">*</span>
                                </label>
                                <PenyediaSearchSelect v-model="kontrakForm.penyedia_id"
                                    :items="penyediaOptions ?? []" :error="kontrakForm.errors.penyedia_id" />
                                <p v-if="kontrakForm.errors.penyedia_id" class="mt-1 text-xs text-destructive">
                                    {{ kontrakForm.errors.penyedia_id }}
                                </p>
                            </div>

                            <!-- Pejabat Penandatangan -->
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold">Pejabat Penandatangan</label>
                                <select v-model="kontrakForm.pejabat_penandatangan_id"
                                    class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                                    <option :value="null">— Pilih —</option>
                                    <option v-for="p in pejabatOptions" :key="p.id" :value="p.id">
                                        {{ p.name }}{{ p.jabatan ? ` — ${p.jabatan}` : '' }}
                                    </option>
                                </select>
                            </div>

                            <!-- KPA Penandatangan -->
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold">KPA Penandatangan</label>
                                <select v-model="kontrakForm.kpa_penandatangan_id"
                                    class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                                    <option :value="null">— Pilih —</option>
                                    <option v-for="p in kpaOptions" :key="p.id" :value="p.id">
                                        {{ p.name }}{{ p.jabatan ? ` — ${p.jabatan}` : '' }}
                                    </option>
                                </select>
                                <p class="mt-1 text-xs text-muted-foreground">Penandatangan BAPRHP dan BAPP.</p>
                            </div>

                            <!-- No Kontrak -->
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-semibold">
                                    Nomor Kontrak <span class="text-destructive">*</span>
                                </label>
                                <input v-model="kontrakForm.no_kontrak" type="text"
                                    placeholder="Contoh: 027/SPK/V/2026"
                                    class="h-11 w-full rounded-md border bg-background px-3 font-mono text-sm focus:outline-none focus:ring-1"
                                    :class="inputClass('no_kontrak')" />
                                <p v-if="kontrakForm.errors.no_kontrak" class="mt-1 text-xs text-destructive">
                                    {{ kontrakForm.errors.no_kontrak }}
                                </p>
                            </div>

                            <!-- Tanggal Kontrak -->
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold">
                                    Tanggal Kontrak <span class="text-destructive">*</span>
                                </label>
                                <input v-model="kontrakForm.tanggal_kontrak" type="date"
                                    class="h-11 w-full rounded-md border bg-background px-3 text-sm focus:outline-none focus:ring-1"
                                    :class="inputClass('tanggal_kontrak')" />
                                <p v-if="kontrakForm.errors.tanggal_kontrak" class="mt-1 text-xs text-destructive">
                                    {{ kontrakForm.errors.tanggal_kontrak }}
                                </p>
                            </div>

                            <!-- Tanggal Selesai -->
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold">
                                    Tanggal Selesai <span class="text-destructive">*</span>
                                </label>
                                <input v-model="kontrakForm.tanggal_selesai" type="date"
                                    class="h-11 w-full rounded-md border bg-background px-3 text-sm focus:outline-none focus:ring-1"
                                    :class="inputClass('tanggal_selesai')" />
                                <p v-if="kontrakForm.errors.tanggal_selesai" class="mt-1 text-xs text-destructive">
                                    {{ kontrakForm.errors.tanggal_selesai }}
                                </p>
                            </div>

                            <!-- Catatan -->
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-semibold">Catatan</label>
                                <textarea v-model="kontrakForm.catatan" rows="2" placeholder="Opsional"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                            </div>
                        </div>

                        <!-- ── Tabel harga kontrak per item ──────────────── -->
                        <div v-if="pakaiItemKontrak">
                            <div class="mb-2 flex items-center justify-between">
                                <label class="text-sm font-semibold">
                                    Harga Satuan Kontrak per Item
                                    <span class="text-destructive">*</span>
                                </label>
                                <button type="button" @click="gunakanSemuaEstimasi"
                                    class="text-xs text-primary hover:underline">
                                    Gunakan semua estimasi
                                </button>
                            </div>

                            <div class="overflow-hidden rounded-lg border border-border">
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[700px] text-sm">
                                        <thead class="border-b border-border bg-muted text-xs uppercase tracking-wide text-muted-foreground">
                                            <tr>
                                                <th class="px-4 py-3 text-left">Nama Barang</th>
                                                <th class="w-16 px-4 py-3 text-right">Jml</th>
                                                <th class="w-20 px-4 py-3 text-left">Satuan</th>
                                                <th class="w-40 px-4 py-3 text-right">Estimasi/Satuan</th>
                                                <th class="w-44 px-4 py-3 text-right">
                                                    Harga Kontrak/Satuan <span class="text-destructive">*</span>
                                                </th>
                                                <th class="w-40 px-4 py-3 text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in itemsForKontrak" :key="item.id"
                                                class="border-b border-border last:border-b-0 hover:bg-muted/20">
                                                <td class="px-4 py-3">
                                                    <div class="font-medium">{{ item.nama_barang }}</div>
                                                    <div v-if="item.spesifikasi" class="text-xs text-muted-foreground">
                                                        {{ item.spesifikasi }}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-right font-mono">{{ item.jumlah }}</td>
                                                <td class="px-4 py-3">{{ item.satuan }}</td>
                                                <td class="px-4 py-3 text-right font-mono text-muted-foreground">
                                                    {{ formatRupiah(item.harga_satuan_estimasi) }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="number" min="0"
                                                        :value="getHargaKontrak(item.id)"
                                                        @input="setHargaKontrak(item.id, Number(($event.target as HTMLInputElement).value))"
                                                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-right font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                                                    <!-- Selisih per item -->
                                                    <div v-if="getHargaKontrak(item.id) !== Number(item.harga_satuan_estimasi)"
                                                        class="mt-0.5 text-right text-[11px]"
                                                        :class="getHargaKontrak(item.id) > Number(item.harga_satuan_estimasi) ? 'text-amber-600' : 'text-green-600'">
                                                        {{ getHargaKontrak(item.id) > Number(item.harga_satuan_estimasi) ? '▲' : '▼' }}
                                                        {{ formatRupiah(Math.abs(getHargaKontrak(item.id) - Number(item.harga_satuan_estimasi))) }}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-right font-mono font-semibold">
                                                    {{ formatRupiah(getHargaKontrak(item.id) * Number(item.jumlah)) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="border-t-2 border-border bg-muted/40">
                                            <tr>
                                                <td colspan="5" class="px-4 py-3 text-right text-sm font-semibold">
                                                    Total Nilai Kontrak
                                                </td>
                                                <td class="px-4 py-3 text-right font-mono font-bold text-base">
                                                    {{ formatRupiah(nilaiKontrakAuto) }}
                                                </td>
                                            </tr>
                                            <tr v-if="Math.abs(selisih) > 0">
                                                <td colspan="6" class="px-4 py-2 text-right text-xs"
                                                    :class="selisih > 0 ? 'text-amber-600' : 'text-green-600'">
                                                    {{ selisih > 0
                                                        ? `Melebihi estimasi paket ${formatRupiah(Math.abs(selisih))}`
                                                        : `Hemat ${formatRupiah(Math.abs(selisih))} dari estimasi paket` }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Fallback: nilai kontrak manual jika tidak ada item assignment -->
                        <div v-else>
                            <label class="mb-1.5 block text-sm font-semibold">
                                Nilai Kontrak <span class="text-destructive">*</span>
                            </label>
                            <input v-model="kontrakForm.nilai_kontrak" type="number" min="0"
                                class="h-11 w-full rounded-md border bg-background px-3 font-mono text-sm focus:outline-none focus:ring-1"
                                :class="inputClass('nilai_kontrak')" />
                            <p v-if="kontrakForm.errors.nilai_kontrak" class="mt-1 text-xs text-destructive">
                                {{ kontrakForm.errors.nilai_kontrak }}
                            </p>
                            <p v-else-if="Number(kontrakForm.nilai_kontrak) > 0" class="mt-1 text-xs"
                                :class="selisih > 0 ? 'text-amber-600' : 'text-muted-foreground'">
                                {{ selisih > 0
                                    ? `Melebihi estimasi paket ${formatRupiah(Math.abs(selisih))}`
                                    : `Di bawah estimasi paket ${formatRupiah(Math.abs(selisih))}` }}
                            </p>
                        </div>
                    </div>

                    <!-- View mode (not editable) -->
                    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <div class="text-eyebrow">No. Kontrak</div>
                            <div class="mt-0.5 font-mono text-sm">{{ pengadaan.no_kontrak ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-eyebrow">Tanggal Kontrak</div>
                            <div class="mt-0.5 text-sm">{{ formatDate(pengadaan.tanggal_kontrak) }}</div>
                        </div>
                        <div>
                            <div class="text-eyebrow">Tanggal Selesai</div>
                            <div class="mt-0.5 text-sm">{{ formatDate(pengadaan.tanggal_selesai) }}</div>
                        </div>
                        <div>
                            <div class="text-eyebrow">Nilai Kontrak</div>
                            <div class="mt-0.5 font-mono font-semibold">{{ formatRupiah(pengadaan.nilai_kontrak) }}</div>
                        </div>
                        <div>
                            <div class="text-eyebrow">Penyedia</div>
                            <div class="mt-0.5 text-sm font-medium">{{ pengadaan.penyedia?.nama ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-eyebrow">Metode</div>
                            <div class="mt-0.5 text-sm">{{ formatMetode(pengadaan.metode) }}</div>
                        </div>
                        <div v-if="pengadaan.pejabat_penandatangan">
                            <div class="text-eyebrow">Pejabat Penandatangan</div>
                            <div class="mt-0.5 text-sm font-medium">{{ pengadaan.pejabat_penandatangan.name }}</div>
                            <div class="text-xs text-muted-foreground">{{ pengadaan.pejabat_penandatangan.jabatan }}</div>
                        </div>
                        <div v-if="pengadaan.kpa_penandatangan">
                            <div class="text-eyebrow">KPA Penandatangan</div>
                            <div class="mt-0.5 text-sm font-medium">{{ pengadaan.kpa_penandatangan.name }}</div>
                            <div class="text-xs text-muted-foreground">{{ pengadaan.kpa_penandatangan.jabatan }}</div>
                        </div>
                        <div v-if="pengadaan.catatan" class="sm:col-span-2">
                            <div class="text-eyebrow">Catatan</div>
                            <p class="mt-0.5 whitespace-pre-line text-sm">{{ pengadaan.catatan }}</p>
                        </div>

                        <!-- Rincian harga kontrak per item (view mode) -->
                        <div v-if="itemsForKontrak.length > 0" class="sm:col-span-2">
                            <div class="text-eyebrow mb-2">Rincian Harga Kontrak</div>
                            <div class="overflow-hidden rounded-lg border border-border">
                                <table class="w-full text-sm">
                                    <thead class="border-b border-border bg-muted text-xs uppercase text-muted-foreground">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Nama Barang</th>
                                            <th class="w-16 px-3 py-2 text-right">Jml</th>
                                            <th class="w-20 px-3 py-2 text-left">Satuan</th>
                                            <th class="w-40 px-3 py-2 text-right">Harga Kontrak</th>
                                            <th class="w-40 px-3 py-2 text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in itemsForKontrak" :key="item.id"
                                            class="border-b border-border last:border-b-0">
                                            <td class="px-3 py-2 font-medium">{{ item.nama_barang }}</td>
                                            <td class="px-3 py-2 text-right font-mono">{{ item.jumlah }}</td>
                                            <td class="px-3 py-2">{{ item.satuan }}</td>
                                            <td class="px-3 py-2 text-right font-mono">
                                                {{ formatRupiah(item.pivot?.harga_satuan_kontrak ?? item.harga_satuan_estimasi) }}
                                            </td>
                                            <td class="px-3 py-2 text-right font-mono font-semibold">
                                                {{ formatRupiah(
                                                    Number(item.pivot?.harga_satuan_kontrak ?? item.harga_satuan_estimasi)
                                                    * Number(item.jumlah)
                                                ) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </Section>

                <!-- Action buttons -->
                <div v-if="isEditable" class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <PrimaryButton type="button" variant="danger" class="sm:mr-auto" @click="cancelPengadaan">
                        <X class="h-4 w-4" />
                        Batalkan Pengadaan
                    </PrimaryButton>
                    <PrimaryButton type="submit" variant="primary" size="lg" :disabled="kontrakForm.processing">
                        {{ kontrakForm.processing ? 'Menyimpan...' : 'Simpan Kontrak & Lanjut ke UPBJ' }}
                    </PrimaryButton>
                </div>

                <!-- Rincian barang (single paket, view only) -->
                <Section v-if="!isMultiPaket && !pakaiItemKontrak && pengadaan.usulan?.items?.length"
                    title="Rincian Barang" eyebrow="Dari Usulan"
                    :description="`${pengadaan.usulan.items.length} item`">
                    <div class="-mx-6 -my-6 divide-y divide-border">
                        <div v-for="item in pengadaan.usulan.items" :key="item.id" class="px-6 py-3">
                            <div class="text-sm font-medium">{{ item.nama_barang }}</div>
                            <div v-if="item.spesifikasi" class="mt-1 text-xs text-muted-foreground">
                                {{ item.spesifikasi }}
                            </div>
                            <div class="mt-2 flex items-center justify-between text-xs">
                                <span class="text-muted-foreground">{{ item.jumlah }} {{ item.satuan }}</span>
                                <span class="font-mono font-medium">{{ formatRupiah(item.subtotal) }}</span>
                            </div>
                        </div>
                    </div>
                </Section>

                <!-- Paket lain -->
                <Section v-if="siblingPakets.length > 0"
                    title="Paket Lain dari Usulan Ini" eyebrow="Multi-Paket"
                    :description="`${siblingPakets.length} paket lainnya`">
                    <div class="-mx-6 -my-6 divide-y divide-border">
                        <Link v-for="sibling in siblingPakets" :key="sibling.id"
                            :href="`/pengadaan/${sibling.id}`"
                            class="flex items-center gap-4 px-6 py-3 transition hover:bg-muted/30">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-xs text-muted-foreground">{{ sibling.no_pengadaan }}</span>
                                    <StatusBadge :status="sibling.status" />
                                </div>
                                <div class="mt-0.5 text-sm">{{ sibling.nama_paket || '(Tanpa nama paket)' }}</div>
                                <div class="mt-0.5 text-xs text-muted-foreground">
                                    {{ formatMetode(sibling.metode) }} ·
                                    <template v-if="Number(sibling.nilai_kontrak) > 0">
                                        Kontrak {{ formatRupiah(sibling.nilai_kontrak) }}
                                    </template>
                                    <template v-else>
                                        Estimasi {{ formatRupiah(sibling.estimasi_paket) }}
                                    </template>
                                </div>
                            </div>
                            <ArrowRight class="h-4 w-4 shrink-0 text-muted-foreground" />
                        </Link>
                    </div>
                </Section>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="space-y-6 xl:col-span-4">

                <!-- Dokumen upload -->
                <Section title="Dokumen" eyebrow="File"
                    description="Format PDF/DOC untuk kontrak dan PDF/Excel untuk HPS.">
                    <div class="space-y-4">
                        <div v-if="canUploadDokumenKontrak && !isEditable"
                            class="rounded-md border border-amber-300 bg-amber-50 p-3 text-xs text-amber-700 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-300">
                            Pastikan file yang diupload adalah dokumen final. Jika memilih file baru, file sebelumnya akan diganti.
                        </div>

                        <!-- File Kontrak -->
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold">File Kontrak</label>
                            <a v-if="pengadaan.file_kontrak" :href="`/storage/${pengadaan.file_kontrak}`" target="_blank"
                                class="mb-2 flex items-center gap-2 rounded-md border border-border bg-card p-3 text-sm font-medium transition hover:border-primary hover:bg-muted/30">
                                <FileText class="h-4 w-4 text-primary" />
                                <span class="flex-1">Lihat file kontrak</span>
                                <ExternalLink class="h-3 w-3 text-muted-foreground" />
                            </a>
                            <label v-if="canUploadDokumenKontrak"
                                class="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-border bg-background px-3 py-2.5 text-sm transition hover:border-primary hover:bg-muted/30">
                                <Upload class="h-4 w-4 text-muted-foreground" />
                                <span class="flex-1 truncate text-muted-foreground">
                                    {{ fileKontrakName || (pengadaan.file_kontrak ? 'Ganti file...' : 'Pilih file...') }}
                                </span>
                                <input type="file" accept=".pdf,.doc,.docx" @change="onFileKontrak" class="hidden" />
                            </label>
                            <p v-if="!isEditable && !pengadaan.file_kontrak" class="text-xs italic text-muted-foreground">
                                File belum diupload.
                            </p>
                        </div>

                        <!-- File HPS -->
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold">File HPS</label>
                            <a v-if="pengadaan.file_hps" :href="`/storage/${pengadaan.file_hps}`" target="_blank"
                                class="mb-2 flex items-center gap-2 rounded-md border border-border bg-card p-3 text-sm font-medium transition hover:border-primary hover:bg-muted/30">
                                <FileText class="h-4 w-4 text-primary" />
                                <span class="flex-1">Lihat file HPS</span>
                                <ExternalLink class="h-3 w-3 text-muted-foreground" />
                            </a>
                            <label v-if="canUploadDokumenKontrak"
                                class="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-border bg-background px-3 py-2.5 text-sm transition hover:border-primary hover:bg-muted/30">
                                <Upload class="h-4 w-4 text-muted-foreground" />
                                <span class="flex-1 truncate text-muted-foreground">
                                    {{ fileHpsName || (pengadaan.file_hps ? 'Ganti file...' : 'Pilih file...') }}
                                </span>
                                <input type="file" accept=".pdf,.xls,.xlsx" @change="onFileHps" class="hidden" />
                            </label>
                            <p v-if="!isEditable && !pengadaan.file_hps" class="text-xs italic text-muted-foreground">
                                File belum diupload.
                            </p>
                        </div>
                    </div>
                </Section>

                <div v-if="canUploadDokumenKontrak && !isEditable" class="flex justify-end">
                    <PrimaryButton type="button" variant="primary" :disabled="kontrakForm.processing"
                        @click="submitUploadDokumen">
                        {{ kontrakForm.processing ? 'Mengupload...' : 'Simpan Upload Dokumen' }}
                    </PrimaryButton>
                </div>

                <!-- Info dari usulan -->
                <Section title="Dari Usulan" eyebrow="Referensi">
                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-eyebrow">No. Usulan</div>
                            <Link :href="`/usulan/${pengadaan.usulan?.id}`"
                                class="mt-0.5 font-mono text-primary hover:underline">
                                {{ pengadaan.usulan?.no_usulan }}
                            </Link>
                        </div>
                            <!-- Tombol buat paket lain — tampil jika usulan masih dalam_pengadaan -->
                            <div v-if="pengadaan.usulan?.status === 'dalam_pengadaan'
                                    && (userRole === 'pejabat_pengadaan' || isAdmin)">
                                <Link
                                    :href="`/usulan/${pengadaan.usulan?.id}`"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-primary/40 px-3 py-1.5 text-xs font-medium text-primary transition hover:bg-primary/10"
                                >
                                    <Plus class="h-3.5 w-3.5" />
                                    Buat Paket Lain
                                </Link>
                            </div>
                        <div>
                            <div class="text-eyebrow">Pemohon</div>
                            <div class="mt-0.5">{{ pengadaan.usulan?.pemohon?.name ?? '—' }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ pengadaan.usulan?.pemohon?.unit_kerja?.nama }}
                            </div>
                        </div>
                        <div>
                            <div class="text-eyebrow">Total Estimasi Usulan</div>
                            <div class="mt-0.5 font-mono font-semibold">
                                {{ formatRupiah(pengadaan.usulan?.total_estimasi ?? 0) }}
                            </div>
                        </div>
                        <div v-if="isMultiPaket">
                            <div class="text-eyebrow">Estimasi Paket Ini</div>
                            <div class="mt-0.5 font-mono font-semibold">
                                {{ formatRupiah(pengadaan.estimasi_paket) }}
                            </div>
                        </div>
                        <div v-if="Number(pengadaan.nilai_kontrak) > 0">
                            <div class="text-eyebrow">Nilai Kontrak</div>
                            <div class="mt-0.5 font-mono font-semibold">
                                {{ formatRupiah(pengadaan.nilai_kontrak) }}
                            </div>
                        </div>
                    </div>
                </Section>

            </div>
        </div>
    </form>
</template>