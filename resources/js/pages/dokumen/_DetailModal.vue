<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import {
    Dialog,
    DialogContent,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import {
    FileText, CheckCircle2, AlertCircle, X,
    Building2, Hash, Calendar, Wallet, Upload, Loader2,
} from 'lucide-vue-next';

import StatusBadge from '@/components/ev/StatusBadge.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

const props = defineProps<{
    pengadaanId: number | null;
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'saved': []; // emit saat save berhasil supaya parent bisa refresh data
}>();

// State
const loading = ref(false);
const data = ref<any>(null);
const error = ref<string | null>(null);

// Form (di-init setelah data load)
const form = useForm({
    no_bast: '',
    tanggal_bast: '',
    keterangan: '',
    file_bast: null as File | null,
    file_invoice: null as File | null,
    file_faktur_pajak: null as File | null,
    file_kuitansi: null as File | null,
    file_spp: null as File | null,
});

const selectedFileNames = ref<Record<string, string>>({});

// Fetch data saat modal dibuka
const fetchDokumen = async (id: number) => {
    loading.value = true;
    error.value = null;
    try {
        const response = await fetch(`/dokumen/${id}/json`, {
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) throw new Error('Gagal memuat data');
        data.value = await response.json();

        // Init form dengan data dokumen yang ada
        if (data.value.dokumen) {
            form.no_bast = data.value.dokumen.no_bast ?? '';
            //form.tanggal_bast = data.value.dokumen.tanggal_bast ?? '';
            form.tanggal_bast = formatDateInput(data.value.dokumen.tanggal_bast);
            form.keterangan = data.value.dokumen.keterangan ?? '';
        }
    } catch (e: any) {
        error.value = e.message || 'Terjadi kesalahan';
    } finally {
        loading.value = false;
    }
};

// Watcher: load data saat pengadaanId berubah & modal terbuka
watch(
    () => [props.pengadaanId, props.open],
    ([id, isOpen]) => {
        if (id && isOpen) {
            fetchDokumen(id as number);
        } else {
            // Reset saat ditutup
            data.value = null;
            error.value = null;
            form.reset();
            selectedFileNames.value = {};
        }
    },
);

// File upload handlers
const onFileChange = (field: string, e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    (form as any)[field] = file;
    selectedFileNames.value[field] = file?.name ?? '';
};

// Submit save
const submitSave = () => {
    if (!props.pengadaanId) return;

    form.post(`/dokumen/${props.pengadaanId}`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            // Refresh data di modal
            fetchDokumen(props.pengadaanId!);
            // Reset file inputs
            form.file_bast = null;
            form.file_invoice = null;
            form.file_faktur_pajak = null;
            form.file_kuitansi = null;
            form.file_spp = null;
            selectedFileNames.value = {};
            // Trigger refresh di parent table
            emit('saved');
        },
    });
};

// Tutup modal
const closeModal = () => emit('update:open', false);

// Helpers
const formatRupiah = (val: any) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0,
    }).format(Number(val) || 0);

const formatDate = (val: string | null) => {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('id-ID', {
        day: '2-digit', month: 'long', year: 'numeric',
    });
};

const formatDateInput = (val: string | null | undefined) => {
    if (!val) return '';

    return String(val).slice(0, 10);
};

const isReadOnly = computed(() => !data.value?.can_edit);

const dokumenPengadaanList = computed(() => {
    return data.value?.pengadaan?.dokumen_pengadaan ?? [];
});

const dokumenPengadaanMap = computed(() => {
    const map: Record<string, any> = {};

    dokumenPengadaanList.value.forEach((item: any) => {
        map[item.jenis] = item;
    });

    return map;
});

const beritaAcaraRows = computed(() => [
    {
        jenis: 'bast',
        kode: 'BAST',
        nama: 'Berita Acara Serah Terima',
        placeholder: '027/____/BAST/__/____',
    },
    {
        jenis: 'bapmhp',
        kode: 'BAPMHP',
        nama: 'Berita Acara Pemeriksaan Mutu Hasil Pekerjaan',
        placeholder: '027/____/BAPMHP/__/____',
    },
    {
        jenis: 'baprhp',
        kode: 'BAPRHP',
        nama: 'Berita Acara Penerimaan Hasil Pekerjaan',
        placeholder: '027/____/BAPRHP/__/____',
    },
    {
        jenis: 'bapp',
        kode: 'BAPP',
        nama: 'Berita Acara Persetujuan Pembayaran',
        placeholder: '027/____/BAPP/__/____',
    },
]);

// Daftar dokumen wajib
const dokumenList = computed(() => {
    if (!data.value?.dokumen) return [];
    const d = data.value.dokumen;
    return [
        { field: 'file_bast', label: 'BAST', existing: d.file_bast, accept: '.pdf,.doc,.docx,.jpg,.png' },
        { field: 'file_invoice', label: 'Invoice', existing: d.file_invoice, accept: '.pdf,.jpg,.png' },
        { field: 'file_faktur_pajak', label: 'Faktur Pajak', existing: d.file_faktur_pajak, accept: '.pdf,.jpg,.png' },
        { field: 'file_kuitansi', label: 'Kuitansi', existing: d.file_kuitansi, accept: '.pdf,.jpg,.png' },
        { field: 'file_spp', label: 'SPP', existing: d.file_spp, accept: '.pdf,.doc,.docx' },
    ];
});

const completedCount = computed(() => dokumenList.value.filter((d) => d.existing).length);
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent
            class="!max-w-3xl max-h-[90vh] overflow-y-auto"
        >
            <!-- Loading state -->
            <div v-if="loading" class="flex items-center justify-center py-12">
                <Loader2 class="h-8 w-8 animate-spin text-primary" />
            </div>

            <!-- Error state -->
            <div
                v-else-if="error"
                class="flex items-start gap-3 rounded-md p-4"
                style="background-color: var(--color-brand-danger-bg); color: var(--color-brand-danger);"
            >
                <AlertCircle class="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    <div class="font-semibold">Gagal memuat data</div>
                    <div class="text-sm">{{ error }}</div>
                </div>
            </div>

            <!-- Content -->
            <div v-else-if="data" class="space-y-5">
                <!-- Header -->
                <div>
                    <DialogTitle class="text-eyebrow mb-1">
                        {{ data.pengadaan?.no_pengadaan }}
                    </DialogTitle>
                    <DialogDescription class="font-display text-2xl font-semibold text-foreground">
                        {{ data.pengadaan?.usulan?.judul ?? '—' }}
                    </DialogDescription>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <StatusBadge
                            :status="data.dokumen?.is_complete ? 'selesai' : (data.dokumen?.no_bast ? 'proses' : 'belum')"
                            :label="data.dokumen?.is_complete ? 'Dokumen Selesai' : (data.dokumen?.no_bast ? 'Dalam Proses' : 'Belum Dimulai')"
                        />
                        <span
                            v-if="data.pengadaan?.usulan?.status && data.pengadaan.usulan.status !== 'dokumen'"
                            class="badge-status badge-muted"
                        >
                            Usulan: {{ data.pengadaan.usulan.status }}
                        </span>
                    </div>
                </div>

                <!-- Info ringkas -->
                <div class="grid grid-cols-1 gap-3 rounded-md border border-border bg-secondary/30 p-4 sm:grid-cols-2">
                    <div class="flex items-start gap-2">
                        <Building2 class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                        <div>
                            <div class="text-eyebrow">Penyedia</div>
                            <div class="text-sm font-semibold">{{ data.pengadaan?.penyedia?.nama ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <Hash class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                        <div>
                            <div class="text-eyebrow">No. Kontrak</div>
                            <div class="font-mono text-sm font-semibold">{{ data.pengadaan?.no_kontrak ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <Wallet class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                        <div>
                            <div class="text-eyebrow">Nilai Kontrak</div>
                            <div class="font-mono text-sm font-semibold text-primary">
                                {{ formatRupiah(data.pengadaan?.nilai_kontrak) }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <FileText class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                        <div>
                            <div class="text-eyebrow">Petugas</div>
                            <div class="text-sm font-semibold">{{ data.dokumen?.petugas?.name ?? '—' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Read-only notice kalau tidak bisa edit -->
                <div
                    v-if="isReadOnly"
                    class="flex items-start gap-3 rounded-md p-3 text-sm"
                    style="background-color: var(--color-brand-info-bg); color: var(--color-brand-info);"
                >
                    <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                    <div>
                        <span class="font-semibold">Mode Lihat (Read-only).</span>
                        {{
                            data.dokumen?.is_complete
                                ? 'Dokumen sudah selesai diproses.'
                                : 'Pengadaan ini sudah lewat tahap dokumen.'
                        }}
                    </div>
                </div>

                <!-- Form input -->
                <form @submit.prevent="submitSave" class="space-y-4">
                    <!-- BAST Info -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold">Nomor BAST</label>
                            <input
                                v-model="form.no_bast"
                                type="text"
                                placeholder="027/BAST/V/2026"
                                :disabled="isReadOnly"
                                class="h-10 w-full rounded-md border border-input bg-background px-3 font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                            />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold">Tanggal BAST</label>
                            <input
                                v-model="form.tanggal_bast"
                                type="date"
                                :disabled="isReadOnly"
                                class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                            />
                        </div>
                    </div>

                    <!-- Dokumen Wajib + Cetak Berita Acara -->
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <!-- KIRI: Dokumen Wajib -->
                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <label class="text-sm font-semibold">Dokumen Wajib</label>
                                <span class="text-xs text-muted-foreground">
                                    {{ completedCount }} dari {{ dokumenList.length }} terupload
                                </span>
                            </div>

                            <div class="space-y-2">
                                <div
                                    v-for="dok in dokumenList"
                                    :key="dok.field"
                                    class="flex items-center gap-3 rounded-md border p-3 transition"
                                    :class="dok.existing ? 'border-[var(--color-brand-success)] bg-[var(--color-brand-success-bg)]' : 'border-border bg-secondary/20'"
                                >
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md"
                                        :class="dok.existing ? 'bg-[var(--color-brand-success)] text-white' : 'bg-card text-muted-foreground ring-1 ring-border'"
                                    >
                                        <CheckCircle2 v-if="dok.existing" class="h-4 w-4" />
                                        <FileText v-else class="h-4 w-4" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-semibold">{{ dok.label }}</div>

                                        <a
                                            v-if="dok.existing"
                                            :href="`/storage/${dok.existing}`"
                                            target="_blank"
                                            class="text-xs font-semibold text-primary hover:underline"
                                        >
                                            Lihat file ↗
                                        </a>

                                        <div
                                            v-if="selectedFileNames[dok.field]"
                                            class="text-xs"
                                            style="color: var(--color-brand-info);"
                                        >
                                            Akan diunggah: {{ selectedFileNames[dok.field] }}
                                        </div>
                                    </div>

                                    <label
                                        v-if="!isReadOnly"
                                        class="flex shrink-0 cursor-pointer items-center gap-1.5 rounded-md border border-dashed border-border bg-card px-3 py-1.5 text-xs font-medium transition hover:border-primary hover:bg-muted/30"
                                    >
                                        <Upload class="h-3 w-3" />
                                        {{ dok.existing ? 'Ganti' : 'Upload' }}
                                        <input
                                            type="file"
                                            :accept="dok.accept"
                                            class="hidden"
                                            @change="onFileChange(dok.field, $event)"
                                        />
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- KANAN: Nomor & Cetak Berita Acara -->
                        <div>
                            <div class="mb-2">
                                <label class="text-sm font-semibold">Nomor & Cetak Berita Acara</label>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Dokumen resmi yang siap dicetak dari data pengadaan.
                                </p>
                            </div>

                            <div class="space-y-2">
                                <div
                                    v-for="row in beritaAcaraRows"
                                    :key="row.jenis"
                                    class="flex items-start justify-between gap-3 rounded-md border border-border bg-background p-3"
                                >
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                                            {{ row.kode }}
                                        </div>

                                        <div class="mt-0.5 text-xs font-medium">
                                            {{ row.nama }}
                                        </div>

                                        <div
                                            class="mt-1 truncate font-mono text-xs"
                                            :class="dokumenPengadaanMap[row.jenis] ? 'text-primary' : 'text-muted-foreground/60'"
                                        >

                                        </div>
                                    </div>

                                    <a
                                        v-if="dokumenPengadaanMap[row.jenis]"
                                        :href="`/dokumen/${data.pengadaan.id}/cetak/${row.jenis}`"
                                        target="_blank"
                                        class="inline-flex h-8 shrink-0 items-center justify-center rounded-md border border-border px-3 text-xs font-semibold transition hover:bg-muted"
                                    >
                                        Cetak
                                    </a>
                                    <span
                                        v-else
                                        class="inline-flex h-8 shrink-0 items-center justify-center rounded-md border border-border px-3 text-xs text-muted-foreground"
                                    >
                                        Belum
                                    </span>
                                </div>

                                <div class="flex items-start justify-between gap-3 rounded-md border border-border bg-background p-3">
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                                            Ringkasan Kontrak
                                        </div>

                                        <div class="mt-0.5 text-xs font-medium">
                                            Ringkasan Kontrak
                                        </div>
                                    </div>

                                    <a
                                        :href="`/dokumen/${data.pengadaan.id}/cetak/ringkasan-kontrak`"
                                        target="_blank"
                                        class="inline-flex h-8 shrink-0 items-center justify-center rounded-md border border-border px-3 text-xs font-semibold transition hover:bg-muted"
                                    >
                                        Cetak
                                    </a>
                                </div>

                                <!-- Surat Pesanan -->
                                <div class="flex items-start justify-between gap-3 rounded-md border border-border bg-background p-3">
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                                            Surat Pesanan
                                        </div>

                                        <div class="mt-0.5 text-xs font-medium">
                                            Surat Pesanan (SP)
                                        </div>
                                    </div>

                                    <a
                                        :href="`/dokumen/${data.pengadaan.id}/cetak/surat-pesanan`"
                                        target="_blank"
                                        class="inline-flex h-8 shrink-0 items-center justify-center rounded-md border border-border px-3 text-xs font-semibold transition hover:bg-muted"
                                    >
                                        Cetak
                                    </a>
                                </div>

                                <!-- SPP/SPTJ -->
                                <div class="flex items-start justify-between gap-3 rounded-md border border-border bg-background p-3">
                                    <div class="min-w-0">
                                        <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                                            SPP/SPTJ
                                        </div>

                                        <div class="mt-0.5 text-xs font-medium">
                                            SPP-GU & Tanggung Jawab Belanja
                                        </div>
                                    </div>
                                    <a
                                        :href="`/dokumen/${data.pengadaan.id}/cetak/spp-sptj`"
                                        target="_blank"
                                        class="inline-flex h-8 shrink-0 items-center justify-center rounded-md border border-border px-3 text-xs font-semibold transition hover:bg-muted"
                                    >
                                        Cetak
                                    </a>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">Keterangan</label>
                        <textarea
                            v-model="form.keterangan"
                            rows="2"
                            placeholder="Catatan tambahan..."
                            :disabled="isReadOnly"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                        />
                    </div>

                    <!-- Action buttons -->
                    <div class="flex flex-col-reverse gap-3 border-t border-border pt-4 sm:flex-row sm:justify-end">
                        <PrimaryButton type="button" variant="secondary" @click="closeModal">
                            Tutup
                        </PrimaryButton>
                        <PrimaryButton
                            v-if="!isReadOnly"
                            type="submit"
                            variant="primary"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </PrimaryButton>
                        <a
                            v-if="!isReadOnly && data.pengadaan?.id"
                            :href="`/dokumen/${data.pengadaan.id}`"
                            class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-border bg-card px-5 text-sm font-medium transition hover:bg-muted"
                        >
                            Buka Halaman Penuh
                        </a>
                    </div>
                </form>
            </div>
        </DialogContent>
    </Dialog>
</template>