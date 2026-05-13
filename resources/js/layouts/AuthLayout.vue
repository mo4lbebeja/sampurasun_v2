<script setup lang="ts">
import EvSplitLayout from '@/layouts/auth/EvSplitLayout.vue';

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
    }>(),
    {
        title: '',
        description: '',
    },
);
</script>

<template>
    <EvSplitLayout :title="props.title" :description="props.description">
        <slot />
    </EvSplitLayout>
</template>