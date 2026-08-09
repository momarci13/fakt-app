<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({ inheritAttrs: false });
defineProps<{ className?: HTMLAttributes['class'] }>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 44 44"
        :class="className"
        v-bind="$attrs"
    >
        <rect width="44" height="44" rx="12" fill="currentColor" />
        <path d="M13 11h19v6H20v4h10v6H20v7h-7V11Z" fill="white" />
        <circle cx="33" cy="33" r="4" fill="#b8e0b8" />
    </svg>
</template>
