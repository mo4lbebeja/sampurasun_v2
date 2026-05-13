<script setup lang="ts">
withDefaults(
    defineProps<{
        title?: string;
        eyebrow?: string;
        description?: string;
    }>(),
    {},
);
</script>

<template>
    <section class="rounded-lg border border-border bg-card">
        <header
            v-if="title || eyebrow"
            class="border-b border-border px-6 py-5"
        >
            <div v-if="eyebrow" class="text-eyebrow mb-1">{{ eyebrow }}</div>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 v-if="title" class="font-display text-xl font-semibold">
                        {{ title }}
                    </h2>
                    <p v-if="description" class="mt-1 text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
                <div v-if="$slots.actions" class="shrink-0">
                    <slot name="actions" />
                </div>
            </div>
        </header>

        <div class="p-6">
            <slot />
        </div>
    </section>
</template>