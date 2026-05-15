<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import PageHeader    from '@/components/ev/PageHeader.vue';
import Section       from '@/components/ev/Section.vue';
import PrimaryButton from '@/components/ev/PrimaryButton.vue';

type DpaAnggaran = {
    id:             number;
    tahun_anggaran: number;
    no_dpa:         string;
    tanggal_dpa:    string | null;
    nama_dpa:       string | null;
    keterangan:     string | null;
    is_active:      boolean;
};

const props = defineProps<{ dpa: DpaAnggaran }>();

const form = useForm({
    tahun_anggaran: props.dpa.tahun_anggaran,
    no_dpa:         props.dpa.no_dpa,
    tanggal_dpa:    props.dpa.tanggal_dpa ?? '',
    nama_dpa:       props.dpa.nama_dpa    ?? '',
    keterangan:     props.dpa.keterangan  ?? '',
    is_active:      props.dpa.is_active,
});

const submit = () => {
    form.put(`/dpa-anggaran/${props.dpa.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Edit DPA ${dpa.no_dpa}`" />

    <form @submit.prevent="submit" class="space-y-6 p-4 sm:p-6 lg:p-8">

        <div class="flex items-start gap-3">
            <Link href="/dpa-anggaran"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-border bg-card transition hover:bg-muted">
                <ArrowLeft class="h-4 w-4" />
            </Link>
            <div class="flex-1">
                <PageHeader :title="`Edit DPA ${dpa.no_dpa}`" eyebrow="Master Data"
                    :subtitle="`Tahun Anggaran ${dpa.tahun_anggaran}`" />
            </div>
        </div>

        <Section title="Data DPA Anggaran" eyebrow="Informasi">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                <!-- Tahun Anggaran -->
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Tahun Anggaran <span class="text-destructive">*</span>
                    </label>
                    <input v-model.number="form.tahun_anggaran" type="number" min="2020" max="2100"
                        class="h-11 w-full rounded-md border bg-background px-3 text-sm focus:outline-none focus:ring-1"
                        :class="form.errors.tahun_anggaran ? 'border-destructive focus:ring-destructive' : 'border-input focus:border-primary focus:ring-primary'" />
                    <p v-if="form.errors.tahun_anggaran" class="mt-1 text-xs text-destructive">{{ form.errors.tahun_anggaran }}</p>
                </div>

                <!-- No DPA -->
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">
                        Nomor DPA <span class="text-destructive">*</span>
                    </label>
                    <input v-model="form.no_dpa" type="text"
                        class="h-11 w-full rounded-md border bg-background px-3 font-mono text-sm focus:outline-none focus:ring-1"
                        :class="form.errors.no_dpa ? 'border-destructive focus:ring-destructive' : 'border-input focus:border-primary focus:ring-primary'" />
                    <p v-if="form.errors.no_dpa" class="mt-1 text-xs text-destructive">{{ form.errors.no_dpa }}</p>
                </div>

                <!-- Tanggal DPA -->
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Tanggal DPA</label>
                    <input v-model="form.tanggal_dpa" type="date"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                </div>

                <!-- Nama DPA -->
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">Nama DPA</label>
                    <input v-model="form.nama_dpa" type="text"
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                </div>

                <!-- Keterangan -->
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold">Keterangan</label>
                    <textarea v-model="form.keterangan" rows="3"
                        class="w-full rounded-md border border-input bg-background px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" />
                </div>

                <!-- Status -->
                <div class="sm:col-span-2">
                    <label class="flex cursor-pointer items-center gap-3">
                        <input v-model="form.is_active" type="checkbox"
                            class="h-4 w-4 rounded border-input accent-primary" />
                        <span class="text-sm font-semibold">DPA Aktif</span>
                        <span class="text-xs text-muted-foreground">— DPA aktif bisa dipilih saat membuat Sub Kegiatan dan Anggaran</span>
                    </label>
                </div>
            </div>
        </Section>

        <div class="flex justify-end gap-3">
            <Link href="/dpa-anggaran">
                <PrimaryButton type="button" variant="secondary">Batal</PrimaryButton>
            </Link>
            <PrimaryButton type="submit" variant="primary" size="lg" :disabled="form.processing">
                {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </PrimaryButton>
        </div>

    </form>
</template>