<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Building2, Search, X } from 'lucide-vue-next';

type PenyediaOption = {
    id: number;
    nama: string;
    jenis_badan?: string | null;
    npwp?: string | null;
    nama_pic?: string | null;
    alamat?: string | null;
    telepon?: string | null;
};

const props = withDefaults(
    defineProps<{
        modelValue: number | null;
        items: PenyediaOption[];
        label?: string;
        placeholder?: string;
        error?: string;
        disabled?: boolean;
    }>(),
    {
        label: 'Penyedia Terpilih',
        placeholder: 'Cari penyedia berdasarkan nama, NPWP, PIC, alamat, atau telepon...',
        error: '',
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
}>();

const search = ref('');
const open = ref(false);

const selected = computed(() => {
    return props.items.find((item) => item.id === props.modelValue) ?? null;
});

watch(
    selected,
    (value) => {
        search.value = value?.nama ?? '';
    },
    { immediate: true },
);

const filteredItems = computed(() => {
    const keyword = search.value.toLowerCase().trim();

    if (!keyword) {
        return props.items.slice(0, 10);
    }

    return props.items
        .filter((item) => {
            return [
                item.nama,
                item.jenis_badan,
                item.npwp,
                item.nama_pic,
                item.alamat,
                item.telepon,
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase()
                .includes(keyword);
        })
        .slice(0, 10);
});

const selectItem = (item: PenyediaOption) => {
    emit('update:modelValue', item.id);
    search.value = item.nama;
    open.value = false;
};

const clearItem = () => {
    emit('update:modelValue', null);
    search.value = '';
    open.value = true;
};

const handleInput = () => {
    open.value = true;

    if (selected.value && search.value !== selected.value.nama) {
        emit('update:modelValue', null);
    }
};

const closeLater = () => {
    window.setTimeout(() => {
        open.value = false;

        if (selected.value) {
            search.value = selected.value.nama;
        }
    }, 150);
};
</script>

<template>
    <div class="space-y-2">
        <label class="text-sm font-medium text-foreground">
            {{ label }}
        </label>

        <div class="relative">
            <Search
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
            />

            <input
                v-model="search"
                type="text"
                :disabled="disabled"
                :placeholder="placeholder"
                class="w-full rounded-md border border-border bg-background py-2 pl-9 pr-10 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:opacity-60"
                @focus="open = true"
                @input="handleInput"
                @blur="closeLater"
            />

            <button
                v-if="modelValue && !disabled"
                type="button"
                class="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground"
                @mousedown.prevent
                @click="clearItem"
            >
                <X class="h-4 w-4" />
            </button>

            <div
                v-if="open && !disabled"
                class="absolute z-50 mt-1 max-h-72 w-full overflow-auto rounded-md border border-border bg-card shadow-lg"
            >
                <button
                    v-for="item in filteredItems"
                    :key="item.id"
                    type="button"
                    class="w-full border-b border-border px-3 py-2 text-left text-sm transition last:border-b-0 hover:bg-muted"
                    @mousedown.prevent
                    @click="selectItem(item)"
                >
                    <div class="flex items-start gap-2">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                            <Building2 class="h-4 w-4" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-foreground">
                                {{ item.nama }}
                            </div>

                            <div class="mt-0.5 flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                <span v-if="item.jenis_badan">
                                    {{ item.jenis_badan }}
                                </span>

                                <span v-if="item.npwp">
                                    NPWP: {{ item.npwp }}
                                </span>

                                <span v-if="item.nama_pic">
                                    PIC: {{ item.nama_pic }}
                                </span>

                                <span v-if="item.telepon">
                                    Telp: {{ item.telepon }}
                                </span>
                            </div>

                            <div
                                v-if="item.alamat"
                                class="mt-0.5 truncate text-xs text-muted-foreground"
                            >
                                {{ item.alamat }}
                            </div>
                        </div>
                    </div>
                </button>

                <div
                    v-if="filteredItems.length === 0"
                    class="px-3 py-4 text-center text-sm text-muted-foreground"
                >
                    Penyedia tidak ditemukan.
                </div>
            </div>
        </div>

        <div
            v-if="selected"
            class="rounded-md border border-primary/20 bg-primary/5 p-3 text-sm"
        >
            <div class="font-medium text-foreground">
                {{ selected.nama }}
            </div>

            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                <span v-if="selected.jenis_badan">
                    {{ selected.jenis_badan }}
                </span>

                <span v-if="selected.npwp">
                    NPWP: {{ selected.npwp }}
                </span>

                <span v-if="selected.nama_pic">
                    PIC: {{ selected.nama_pic }}
                </span>

                <span v-if="selected.telepon">
                    Telp: {{ selected.telepon }}
                </span>
            </div>

            <div
                v-if="selected.alamat"
                class="mt-1 text-xs text-muted-foreground"
            >
                {{ selected.alamat }}
            </div>
        </div>

        <p v-if="error" class="text-sm text-destructive">
            {{ error }}
        </p>
    </div>
</template>