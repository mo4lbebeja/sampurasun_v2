<script setup lang="ts">
withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
        size?: 'sm' | 'md' | 'lg';
        type?: 'button' | 'submit' | 'reset';
        disabled?: boolean;
    }>(),
    {
        variant: 'primary',
        size: 'md',
        type: 'button',
        disabled: false,
    },
);

const variantClass: Record<string, string> = {
    primary:   'bg-primary text-primary-foreground hover:bg-[var(--color-brand-primary-dark)]',
    secondary: 'border border-border bg-card hover:bg-muted',
    ghost:     'hover:bg-muted',
    danger:    'border border-[var(--color-brand-danger-bg)] text-[var(--color-brand-danger)] hover:bg-[var(--color-brand-danger-bg)]',
};

const sizeClass: Record<string, string> = {
    sm: 'h-8 px-3 text-xs',
    md: 'h-10 px-5 text-sm',
    lg: 'h-12 px-6 text-base',
};
</script>

<template>
    <button
        :type="type"
        :disabled="disabled"
        class="inline-flex items-center justify-center gap-2 rounded-md font-medium transition disabled:cursor-not-allowed disabled:opacity-50"
        :class="[variantClass[variant], sizeClass[size]]"
    >
        <slot />
    </button>
</template>