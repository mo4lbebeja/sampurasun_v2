<script setup lang="ts">
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: Component;
    }>(),
    {},
);
</script>

<template>
    <div class="rounded-lg border-2 border-dashed border-border bg-card/50 p-12 text-center">
        <div
            v-if="icon"
            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-secondary text-muted-foreground"
        >
            <component :is="icon" class="h-6 w-6" />
        </div>
        <h3 class="mt-4 font-display text-lg font-semibold">{{ title }}</h3>
        <p v-if="description" class="mx-auto mt-1 max-w-sm text-sm text-muted-foreground">
            {{ description }}
        </p>
        <div v-if="$slots.action" class="mt-5">
            <slot name="action" />
        </div>
    </div>
</template>