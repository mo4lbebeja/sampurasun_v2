<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue';
import { Search, X, Check } from 'lucide-vue-next';

type Item = Record<string, any>;

const props = withDefaults(
    defineProps<{
        modelValue: number | string | null;
        items: Item[];
        valueKey?: string;
        labelKey?: string;
        searchKeys?: string[];
        placeholder?: string;
        emptyText?: string;
        disabled?: boolean;
        renderLabel?: (item: Item) => string;
        renderSubtext?: (item: Item) => string;
    }>(),
    {
        valueKey: 'id',
        labelKey: 'nama',
        searchKeys: () => ['nama'],
        placeholder: 'Cari atau pilih...',
        emptyText: 'Tidak ada hasil yang cocok',
        disabled: false,
        renderLabel: undefined,
        renderSubtext: undefined,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: number | string | null];
}>();

const open = ref(false);
const searchTerm = ref('');
const containerRef = ref<HTMLElement | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);

// Selected item
const selected = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined) return null;
    return props.items.find((it) => it[props.valueKey] === props.modelValue) ?? null;
});

// Filtered items — case-insensitive search across multiple keys
const filteredItems = computed(() => {
    const q = searchTerm.value.trim().toLowerCase();
    if (!q) return props.items;

    return props.items.filter((item) => {
        // Loop semua searchKeys — kalau ada yang match return true
        for (const key of props.searchKeys) {
            const val = item[key];
            if (val !== null && val !== undefined) {
                if (String(val).toLowerCase().includes(q)) {
                    return true;
                }
            }
        }
        return false;
    });
});

const selectItem = (item: Item) => {
    emit('update:modelValue', item[props.valueKey]);
    searchTerm.value = '';
    open.value = false;
};

const clearSelection = (e?: Event) => {
    e?.stopPropagation();
    emit('update:modelValue', null);
    searchTerm.value = '';
    open.value = true;
    nextTick(() => inputRef.value?.focus());
};

// Buka dropdown saat user klik display selected
const openSearch = async () => {
    if (props.disabled) return;
    open.value = true;
    searchTerm.value = '';
    await nextTick();
    inputRef.value?.focus();
};

// Close on outside click
const handleClickOutside = (e: MouseEvent) => {
    if (containerRef.value && !containerRef.value.contains(e.target as Node)) {
        open.value = false;
        searchTerm.value = '';
    }
};

watch(open, (isOpen) => {
    if (isOpen) {
        // Use setTimeout to avoid catching the same click that opened it
        setTimeout(() => {
            document.addEventListener('click', handleClickOutside);
        }, 0);
    } else {
        document.removeEventListener('click', handleClickOutside);
    }
});

const onFocus = () => {
    open.value = true;
};
</script>

<template>
    <div ref="containerRef" class="relative">
        <!-- Mode 1: Selected display (kalau ada selection & dropdown tutup) -->
        <div
            v-if="selected && !open"
            class="flex h-11 items-center gap-2 rounded-md border border-input bg-background px-3 text-sm transition"
            :class="{ 'cursor-pointer hover:border-primary': !disabled, 'opacity-60': disabled }"
            @click="openSearch"
        >
            <div class="min-w-0 flex-1">
                <div class="truncate font-medium">
                    {{ renderLabel ? renderLabel(selected) : selected[labelKey] }}
                </div>
                <div
                    v-if="renderSubtext"
                    class="truncate text-xs text-muted-foreground"
                >
                    {{ renderSubtext(selected) }}
                </div>
            </div>
            <button
                v-if="!disabled"
                type="button"
                class="rounded-full p-0.5 text-muted-foreground transition hover:bg-muted hover:text-foreground"
                @click="clearSelection"
            >
                <X class="h-3.5 w-3.5" />
            </button>
        </div>

        <!-- Mode 2: Search input (kalau dropdown buka atau belum ada selection) -->
        <div v-else class="relative">
            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <input
                ref="inputRef"
                v-model="searchTerm"
                type="text"
                :placeholder="selected ? (renderLabel ? renderLabel(selected) : String(selected[labelKey])) : placeholder"
                :disabled="disabled"
                class="h-11 w-full rounded-md border border-input bg-background py-2 pl-10 pr-3 text-sm placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
                autocomplete="off"
                @focus="onFocus"
            />
        </div>

        <!-- Dropdown suggestion -->
        <div
            v-if="open && !disabled"
            class="absolute z-50 mt-1 max-h-72 w-full overflow-y-auto rounded-md border border-border bg-card shadow-lg"
        >
            <div
                v-if="filteredItems.length === 0"
                class="px-4 py-6 text-center text-sm text-muted-foreground"
            >
                {{ emptyText }}
            </div>

            <ul v-else class="py-1">
                <li
                    v-for="item in filteredItems"
                    :key="item[valueKey]"
                    class="cursor-pointer px-3 py-2.5 text-sm transition hover:bg-muted/50"
                    :class="{ 'bg-primary/10': selected && item[valueKey] === modelValue }"
                    @click="selectItem(item)"
                >
                    <div class="flex items-start gap-2">
                        <Check
                            v-if="selected && item[valueKey] === modelValue"
                            class="mt-0.5 h-4 w-4 shrink-0 text-primary"
                        />
                        <div v-else class="h-4 w-4 shrink-0" />

                        <div class="min-w-0 flex-1">
                            <div class="font-medium">
                                {{ renderLabel ? renderLabel(item) : item[labelKey] }}
                            </div>
                            <div
                                v-if="renderSubtext"
                                class="truncate text-xs text-muted-foreground"
                            >
                                {{ renderSubtext(item) }}
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>