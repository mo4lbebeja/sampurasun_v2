<script setup lang="ts">
import { computed, reactive, ref } from 'vue';

import {
    AlertTriangle,
    ArrowLeft,
    Copy,
    Pencil,
    Plus,
    Trash2,
    X,
} from 'lucide-vue-next';

import { Head, Link, useForm } from '@inertiajs/vue3';
import PageHeader from '@/components/ev/PageHeader.vue';
import Section from '@/components/ev/Section.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';
import SearchableSelect from '@/components/ev/SearchableSelect.vue';

type Kategori = { id: number; kode: string; nama: string };
type Anggaran = {
    id: number;
    kode_rekening: string;
    nama_rekening: string;
    pagu: string;
    sisa: string;
};

type Item = {
    kategori_id: number | null;
    nama_barang: string;
    spesifikasi: string;
    satuan: string;
    jumlah: number;
    harga_satuan_estimasi: number;
};

const props = withDefaults(
    defineProps<{
        kategoriList?: Kategori[];
        anggaranList?: Anggaran[];
    }>(),
    {
        kategoriList: () => [],
        anggaranList: () => [],
    },
);

const anggaranList = computed(() => props.anggaranList ?? []);
const kategoriList = computed(() => props.kategoriList ?? []);

const emptyItem = (): Item => ({
    kategori_id: null,
    nama_barang: '',
    spesifikasi: '',
    satuan: '',
    jumlah: 1,
    harga_satuan_estimasi: 0,
});

const form = useForm({
    judul: '',
    latar_belakang: '',
    keterangan: '',
    anggaran_id: null as number | null,
    items: [] as Item[],
});

const itemModalOpen = ref(false);
const editingIndex = ref<number | null>(null);

const itemDraft = reactive<Item>(emptyItem());

const resetItemDraft = () => {
    Object.assign(itemDraft, emptyItem());
    editingIndex.value = null;
};

const openCreateItem = () => {
    resetItemDraft();
    customSatuan.value = '';
    itemModalOpen.value = true;
};

const openEditItem = (idx: number) => {
    editingIndex.value = idx;

    const item = form.items[idx];
    const satuanTersedia = satuanOptions.includes(item.satuan);

    Object.assign(itemDraft, {
        kategori_id: item.kategori_id,
        nama_barang: item.nama_barang,
        spesifikasi: item.spesifikasi,
        satuan: satuanTersedia ? item.satuan : 'lainnya',
        jumlah: item.jumlah,
        harga_satuan_estimasi: item.harga_satuan_estimasi,
    });

    customSatuan.value = satuanTersedia ? '' : item.satuan;

    itemModalOpen.value = true;
};

const closeItemModal = () => {
    itemModalOpen.value = false;
    customSatuan.value = '';
    resetItemDraft();
};

const saveItem = () => {
    const finalSatuan =
        itemDraft.satuan === 'lainnya'
            ? customSatuan.value.trim()
            : itemDraft.satuan;

    if (itemDraft.satuan === 'lainnya' && !finalSatuan) {
        return;
    }

    const payload: Item = {
        kategori_id: itemDraft.kategori_id ? Number(itemDraft.kategori_id) : null,
        nama_barang: itemDraft.nama_barang,
        spesifikasi: itemDraft.spesifikasi,
        satuan: finalSatuan || 'unit',
        jumlah: Number(itemDraft.jumlah) || 1,
        harga_satuan_estimasi: Number(itemDraft.harga_satuan_estimasi) || 0,
    };

    if (editingIndex.value === null) {
        form.items.push(payload);
    } else {
        form.items.splice(editingIndex.value, 1, payload);
    }

    closeItemModal();
};

const duplicateItem = (idx: number) => {
    const item = form.items[idx];

    form.items.push({
        ...item,
        nama_barang: `${item.nama_barang} - Copy`,
    });
};

const removeItem = (idx: number) => {
    form.items.splice(idx, 1);
};

const kategoriName = (kategoriId: number | null) => {
    if (!kategoriId) return '-';

    return kategoriList.value.find((kategori) => kategori.id === kategoriId)?.nama ?? '-';
};

const hasItemError = (idx: number) =>
    Boolean(
        form.errors[`items.${idx}.kategori_id`] ||
        form.errors[`items.${idx}.nama_barang`] ||
        form.errors[`items.${idx}.jumlah`] ||
        form.errors[`items.${idx}.harga_satuan_estimasi`],
    );

const subtotal = (item: Item) =>
    (Number(item.jumlah) || 0) * (Number(item.harga_satuan_estimasi) || 0);

const itemDraftSubtotal = computed(() => subtotal(itemDraft));

const totalEstimasi = computed(() =>
    form.items.reduce((sum, item) => sum + subtotal(item), 0),
);

const isOverBudget = computed(() => {
    if (!selectedAnggaran.value) return false;
    return totalEstimasi.value > Number(selectedAnggaran.value.sisa);
});

const formatRupiah = (val: number | string) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(val) || 0);

const submit = () => {
    form.clearErrors('items', 'anggaran_id');

    if (form.items.length === 0) {
        form.setError('items', 'Minimal tambahkan 1 item pengadaan.');
        return;
    }

    if (!form.anggaran_id) {
        form.setError('anggaran_id', 'Pilih pos anggaran terlebih dahulu.');
        return;
    }

    if (isEstimasiMelebihiSisa.value) {
        form.setError(
            'items',
            `Total estimasi ${formatRupiah(totalEstimasi.value)} melebihi sisa anggaran ${formatRupiah(sisaAnggaran.value)}.`,
        );
        return;
    }

    form.post('/usulan', { preserveScroll: true });
};
const selectedAnggaran = computed(() =>
    anggaranList.value.find(a => a.id === form.anggaran_id)
);

const sisaAnggaran = computed(() => Number(selectedAnggaran.value?.sisa ?? 0));

const isEstimasiMelebihiSisa = computed(() =>
    form.anggaran_id !== null && totalEstimasi.value > sisaAnggaran.value,
);

const selisihEstimasi = computed(() =>
    Math.max(totalEstimasi.value - sisaAnggaran.value, 0),
);

const satuanOptions = [
    'unit',
    'pcs',
    'buah',
    'paket',
    'set',
    'box',
    'rim',
    'lusin',
    'meter',
    'roll',
    'liter',
    'kg',
    'gram',
    'bulan',
    'tahun',
    'layanan',
    'lainnya',
];

const customSatuan = ref('');

</script>

<template>
    <Head title="Buat Usulan" />
    
    <form @submit.prevent="submit" class="space-y-6 p-4 sm:p-6 lg:p-8">
        <!-- Header dengan tombol back -->
        <div class="flex items-start gap-3">
            <Link
                href="/usulan"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-card transition hover:bg-muted"
            >
                <ArrowLeft class="h-4 w-4" />
            </Link>
            <div class="flex-1">
                <PageHeader
                    title="Buat Usulan Pengadaan"
                    subtitle="Lengkapi informasi dan rincian barang yang diusulkan"
                    eyebrow="Pengadaan Baru"
                />
            </div>
        </div>

        <!-- Section: Anggaran -->
        <Section title="Sumber Anggaran" eyebrow="Langkah 1">
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Anggaran <span class="text-destructive">*</span>
                    </label>
                    <SearchableSelect
                        v-model="form.anggaran_id"
                        :items="anggaranList"
                        value-key="id"
                        label-key="nama_rekening"
                        :search-keys="['kode_rekening', 'nama_rekening', 'uraian']"
                        placeholder="Ketik kode atau nama rekening..."
                        empty-text="Tidak ada anggaran yang cocok"
                        :render-label="(a) => `${a.kode_rekening} — ${a.nama_rekening}`"
                        :render-subtext="(a) => `Pagu: ${formatRupiah(a.pagu)} · Sisa: ${formatRupiah(a.sisa)}`"
                    />
                    <p v-if="form.errors.anggaran_id" class="mt-1 text-xs text-destructive">
                        {{ form.errors.anggaran_id }}
                    </p>
                </div>

                <!-- Info anggaran terpilih -->
                <div
                    v-if="selectedAnggaran"
                    class="grid grid-cols-1 gap-3 rounded-md bg-secondary/40 p-4 sm:grid-cols-3"
                >
                    <div>
                        <div class="text-eyebrow">Total Pagu</div>
                        <div class="mt-1 font-mono text-sm font-semibold">
                            {{ formatRupiah(selectedAnggaran.pagu) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-eyebrow">Sisa Anggaran</div>
                        <div
                            class="mt-1 font-mono text-sm font-semibold"
                            style="color: var(--color-brand-success);"
                        >
                            {{ formatRupiah(selectedAnggaran.sisa) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-eyebrow">Estimasi Usulan</div>
                        <div
                            class="mt-1 font-mono text-sm font-semibold"
                            :class="isOverBudget ? 'text-destructive' : 'text-foreground'"
                        >
                            {{ formatRupiah(totalEstimasi) }}
                        </div>
                        <div
                            v-if="form.anggaran_id"
                            class="mt-3 rounded-md border p-3 text-sm"
                            :class="
                                isEstimasiMelebihiSisa
                                    ? 'border-destructive/40 bg-destructive/10 text-destructive'
                                    : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700'
                            "
                        >
                            <div class="font-semibold">
                                {{ isEstimasiMelebihiSisa ? 'Estimasi melebihi sisa anggaran' : 'Estimasi masih dalam batas anggaran' }}
                            </div>

                            <div class="mt-1 space-y-1 text-xs">
                                <div>
                                    Sisa Anggaran:
                                    <span class="font-mono font-semibold">
                                        {{ formatRupiah(sisaAnggaran) }}
                                    </span>
                                </div>

                                <div>
                                    Total Estimasi:
                                    <span class="font-mono font-semibold">
                                        {{ formatRupiah(totalEstimasi) }}
                                    </span>
                                </div>

                                <div v-if="isEstimasiMelebihiSisa">
                                    Kelebihan:
                                    <span class="font-mono font-semibold">
                                        {{ formatRupiah(selisihEstimasi) }}
                                    </span>
                                </div>
                            </div>

                            <p v-if="isEstimasiMelebihiSisa" class="mt-2 text-xs">
                                Proses tidak dapat dilanjutkan. Kurangi item, ubah jumlah/harga, atau pilih pos anggaran dengan sisa yang mencukupi.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="isOverBudget"
                    class="flex items-start gap-3 rounded-md p-3 text-sm"
                    style="background-color: var(--color-brand-danger-bg); color: var(--color-brand-danger);"
                >
                    <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                    <div>
                        <div class="font-semibold">Estimasi melebihi sisa anggaran</div>
                        <div class="text-xs opacity-90">
                            Kurangi jumlah atau harga satuan, atau pilih anggaran dengan sisa yang lebih besar.
                        </div>
                    </div>
                </div>
            </div>
        </Section>

        <!-- Section: Info Usulan -->
        <Section title="Informasi Usulan" eyebrow="Langkah 2">
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Judul Usulan <span class="text-destructive">*</span>
                    </label>
                    <input
                        v-model="form.judul"
                        type="text"
                        placeholder="Contoh: Pengadaan Laptop Bidang IT"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.judul }"
                    />
                    <p v-if="form.errors.judul" class="mt-1 text-xs text-destructive">
                        {{ form.errors.judul }}
                    </p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Latar Belakang</label>
                    <textarea
                        v-model="form.latar_belakang"
                        rows="3"
                        placeholder="Jelaskan kebutuhan atau alasan usulan ini..."
                        class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                        :class="{ 'border-destructive': form.errors.latar_belakang }"
                    />
                    <p v-if="form.errors.latar_belakang" class="mt-1 text-xs text-destructive">
                        {{ form.errors.latar_belakang }}
                    </p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Keterangan Tambahan</label>
                    <textarea
                        v-model="form.keterangan"
                        rows="2"
                        placeholder="Catatan, persyaratan khusus, dll (opsional)"
                        class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>
            </div>
        </Section>

        <!-- Section: Rincian Barang -->
        <Section title="Rincian Barang" eyebrow="Langkah 3" :description="`${form.items.length} item`">
            <template #actions>
                <PrimaryButton
                    type="button"
                    variant="secondary"
                    size="sm"
                    @click="openCreateItem"
                >
                    <Plus class="h-3.5 w-3.5" />
                    Tambah Item
                </PrimaryButton>
            </template>

            <p v-if="form.errors.items" class="mb-3 text-xs text-destructive">
                {{ form.errors.items }}
            </p>

            <div
                v-if="form.items.length === 0"
                class="rounded-lg border border-dashed border-border bg-secondary/20 p-8 text-center"
            >
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <Plus class="h-5 w-5" />
                </div>

                <div class="font-semibold">Belum ada item pengadaan</div>

                <p class="mt-1 text-sm text-muted-foreground">
                    Tambahkan barang atau jasa yang akan diusulkan.
                </p>

                <PrimaryButton
                    type="button"
                    variant="primary"
                    class="mt-4"
                    @click="openCreateItem"
                >
                    <Plus class="h-4 w-4" />
                    Tambah Item Pertama
                </PrimaryButton>
            </div>

            <div v-else class="overflow-hidden rounded-lg border border-border">
                <div class="max-h-[420px] overflow-auto">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead class="sticky top-0 z-10 bg-muted">
                            <tr class="border-b border-border text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <th class="w-12 px-3 py-3">No</th>
                                <th class="px-3 py-3">Nama Barang</th>
                                <th class="px-3 py-3">Kategori</th>
                                <th class="w-24 px-3 py-3 text-right">Jumlah</th>
                                <th class="w-24 px-3 py-3">Satuan</th>
                                <th class="w-40 px-3 py-3 text-right">Harga Satuan</th>
                                <th class="w-40 px-3 py-3 text-right">Subtotal</th>
                                <th class="w-36 px-3 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(item, idx) in form.items"
                                :key="idx"
                                class="border-b border-border last:border-b-0"
                                :class="hasItemError(idx) ? 'bg-destructive/5' : 'bg-card'"
                            >
                                <td class="px-3 py-3 align-top font-mono text-xs text-muted-foreground">
                                    {{ idx + 1 }}
                                </td>

                                <td class="px-3 py-3 align-top">
                                    <div class="font-medium text-foreground">
                                        {{ item.nama_barang || '-' }}
                                    </div>

                                    <div
                                        v-if="item.spesifikasi"
                                        class="mt-0.5 line-clamp-2 text-xs text-muted-foreground"
                                    >
                                        {{ item.spesifikasi }}
                                    </div>

                                    <div
                                        v-if="hasItemError(idx)"
                                        class="mt-1 text-xs font-medium text-destructive"
                                    >
                                        Ada data item yang perlu diperbaiki.
                                    </div>
                                </td>

                                <td class="px-3 py-3 align-top">
                                    {{ kategoriName(item.kategori_id) }}
                                </td>

                                <td class="px-3 py-3 text-right align-top font-mono">
                                    {{ Number(item.jumlah).toLocaleString('id-ID') }}
                                </td>

                                <td class="px-3 py-3 align-top">
                                    {{ item.satuan }}
                                </td>

                                <td class="px-3 py-3 text-right align-top font-mono">
                                    {{ formatRupiah(item.harga_satuan_estimasi) }}
                                </td>

                                <td class="px-3 py-3 text-right align-top font-mono font-semibold">
                                    {{ formatRupiah(subtotal(item)) }}
                                </td>

                                <td class="px-3 py-3 align-top">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-border bg-background transition hover:bg-muted"
                                            title="Edit item"
                                            @click="openEditItem(idx)"
                                        >
                                            <Pencil class="h-3.5 w-3.5" />
                                        </button>

                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-border bg-background transition hover:bg-muted"
                                            title="Duplikat item"
                                            @click="duplicateItem(idx)"
                                        >
                                            <Copy class="h-3.5 w-3.5" />
                                        </button>

                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-border bg-background transition hover:bg-destructive/10"
                                            title="Hapus item"
                                            style="color: var(--color-brand-danger);"
                                            @click="removeItem(idx)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Total -->
            <div class="mt-4 flex flex-col gap-3 rounded-md border border-primary/30 bg-primary/5 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-eyebrow">Ringkasan Estimasi</div>

                    <div class="mt-0.5 text-xs text-muted-foreground">
                        {{ form.items.length }} item pengadaan
                    </div>
                </div>

                <div class="text-left sm:text-right">
                    <div class="text-xs text-muted-foreground">Total Estimasi</div>

                    <span class="font-display text-2xl font-bold text-primary">
                        {{ formatRupiah(totalEstimasi) }}
                    </span>
                </div>
            </div>
        </Section>

        <!-- Submit row -->
        <!-- Modal Tambah/Edit Item -->
        <div
            v-if="itemModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        >
            <div class="w-full max-w-3xl overflow-hidden rounded-xl border border-border bg-card shadow-xl">
                <div class="flex items-center justify-between border-b border-border px-5 py-4">
                    <div>
                        <div class="text-eyebrow">
                            {{ editingIndex === null ? 'Tambah Item' : 'Edit Item' }}
                        </div>

                        <h3 class="font-display text-lg font-semibold">
                            Rincian Barang/Jasa
                        </h3>
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-border bg-background transition hover:bg-muted"
                        @click="closeItemModal"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="max-h-[70vh] overflow-y-auto p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Kategori -->
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold">
                                Kategori <span class="text-destructive">*</span>
                            </label>

                            <select
                                v-model="itemDraft.kategori_id"
                                class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            >
                                <option :value="null">— Pilih kategori —</option>

                                <option
                                    v-for="k in kategoriList"
                                    :key="k.id"
                                    :value="k.id"
                                >
                                    {{ k.nama }}
                                </option>
                            </select>
                        </div>

                        <!-- Nama Barang -->
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold">
                                Nama Barang <span class="text-destructive">*</span>
                            </label>

                            <input
                                v-model="itemDraft.nama_barang"
                                type="text"
                                placeholder="Nama barang/jasa"
                                class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            />
                        </div>

                        <!-- Spesifikasi -->
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-semibold">
                                Spesifikasi
                            </label>

                            <textarea
                                v-model="itemDraft.spesifikasi"
                                rows="3"
                                placeholder="Spesifikasi teknis, merek, tipe, ukuran, atau catatan detail lainnya"
                                class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            />
                        </div>

                        <!-- Jumlah -->
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold">
                                Jumlah <span class="text-destructive">*</span>
                            </label>

                            <input
                                v-model.number="itemDraft.jumlah"
                                type="number"
                                min="1"
                                class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            />
                        </div>

                        <!-- Satuan -->
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold">
                                Satuan <span class="text-destructive">*</span>
                            </label>

                            <select
                                v-model="itemDraft.satuan"
                                class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            >
                                <option
                                    v-for="satuan in satuanOptions"
                                    :key="satuan"
                                    :value="satuan"
                                >
                                    {{ satuan === 'lainnya' ? 'Lainnya...' : satuan }}
                                </option>
                            </select>

                            <input
                                v-if="itemDraft.satuan === 'lainnya'"
                                v-model="customSatuan"
                                type="text"
                                placeholder="Tulis satuan lainnya, contoh: hari, orang, lot"
                                class="mt-2 h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            />

                            <p
                                v-if="itemDraft.satuan === 'lainnya'"
                                class="mt-1 text-xs text-muted-foreground"
                            >
                                Satuan yang ditulis di sini akan disimpan ke item pengadaan.
                            </p>
                        </div>

                        <!-- Harga Satuan -->
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold">
                                Harga Satuan Estimasi <span class="text-destructive">*</span>
                            </label>

                            <input
                                v-model.number="itemDraft.harga_satuan_estimasi"
                                type="number"
                                min="0"
                                class="h-11 w-full rounded-md border border-input bg-background px-3 text-right font-mono text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            />
                        </div>

                        <!-- Subtotal -->
                        <div class="rounded-md border border-dashed border-border bg-secondary/30 p-4">
                            <div class="text-eyebrow">Subtotal</div>

                            <div class="mt-1 font-display text-xl font-bold text-primary">
                                {{ formatRupiah(itemDraftSubtotal) }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-border px-5 py-4 sm:flex-row sm:justify-end">
                    <PrimaryButton
                        type="button"
                        variant="secondary"
                        @click="closeItemModal"
                    >
                        Batal
                    </PrimaryButton>

                    <PrimaryButton
                        type="button"
                        variant="primary"
                        :disabled="itemDraft.satuan === 'lainnya' && !customSatuan.trim()"
                        @click="saveItem"
                    >
                        {{ editingIndex === null ? 'Simpan Item' : 'Update Item' }}
                    </PrimaryButton>    
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <Link href="/usulan">
                <PrimaryButton variant="secondary" type="button">
                    Batal
                </PrimaryButton>
            </Link>
            <PrimaryButton
                type="submit"
                variant="primary"
                size="lg"
                :disabled="form.processing || isEstimasiMelebihiSisa || form.items.length === 0"
            >
                {{ form.processing ? 'Menyimpan...' : 'Ajukan Usulan' }}
            </PrimaryButton>
        </div>
    </form>
</template>