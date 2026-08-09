<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const name = usePage().props.name;
</script>

<template>
    <div
        class="flex aspect-square size-9 items-center justify-center text-sidebar-primary"
    >
        <AppLogoIcon class="size-9" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-bold">{{ name }}</span>
        <span
            class="truncate text-[10px] tracking-wide text-muted-foreground uppercase"
            >Belső alkalmazás</span
        >
    </div>
</template>
