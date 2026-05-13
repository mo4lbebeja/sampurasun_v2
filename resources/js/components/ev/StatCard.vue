<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    label: string;
    value: string | number;
    delta?: string;
    icon?: Component;
    tone?: 'primary' | 'accent' | 'success' | 'warning' | 'info';
}>();

const toneClass: Record<string, string> = {
    primary: 'bg-primary text-primary-foreground',
    accent:  'bg-accent text-accent-foreground',
    success: 'bg-[var(--color-brand-success)] text-white',
    warning: 'bg-[var(--color-brand-warning)] text-white',
    info:    'bg-[var(--color-brand-info)] text-white',
};
</script>

<template>
    <div class="relative overflow-hidden rounded-lg border border-border bg-card p-6 transition hover:border-primary/30">
        <!-- Top accent line -->
        <div
            class="absolute inset-x-0 top-0 h-1"
            :class="toneClass[tone ?? 'primary']"
        />

        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="text-eyebrow">{{ label }}</div>
                <div class="mt-3 font-display text-4xl font-semibold leading-none tracking-tight">
                    {{ value }}
                </div>
                <div v-if="delta" class="mt-2 text-xs text-muted-foreground">
                    {{ delta }}
                </div>
            </div>

            <div
                v-if="icon"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md"
                :class="toneClass[tone ?? 'primary']"
            >
                <component :is="icon" class="h-5 w-5" />
            </div>
        </div>
    </div>
</template>