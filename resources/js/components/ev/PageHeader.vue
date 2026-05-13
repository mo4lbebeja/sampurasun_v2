<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        subtitle?: string;
        eyebrow?: string;
    }>(),
    {},
);
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <div v-if="eyebrow" class="text-eyebrow mb-2">
                {{ eyebrow }}
            </div>
            <h1 class="font-display text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                {{ title }}
            </h1>
            <p v-if="subtitle" class="mt-1 text-sm text-muted-foreground">
                {{ subtitle }}
            </p>
        </div>

        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>