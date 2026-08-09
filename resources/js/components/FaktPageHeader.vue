<script setup lang="ts">
defineProps<{ eyebrow?: string; title: string; description?: string }>();
</script>

<template>
    <header
        class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
    >
        <div class="max-w-3xl">
            <p v-if="eyebrow" class="fakt-label mb-2 text-primary">
                {{ eyebrow }}
            </p>
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                {{ title }}
            </h1>
            <p
                v-if="description"
                class="mt-2 text-sm leading-6 text-muted-foreground sm:text-base"
            >
                {{ description }}
            </p>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 flex-wrap gap-2">
            <slot name="actions" />
        </div>
    </header>
</template>
