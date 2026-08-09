<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ value: string }>();
const labels: Record<string, string> = {
    todo: 'Teendő',
    in_progress: 'Folyamatban',
    review: 'Ellenőrzés',
    done: 'Kész',
    cancelled: 'Törölve',
    pending: 'Függőben',
    approved: 'Jóváhagyva',
    rejected: 'Elutasítva',
    waitlisted: 'Várólista',
    active: 'Aktív',
    senior: 'Szenior',
    alumni: 'Alumni',
    required: 'Kötelező',
    optional: 'Opcionális',
    present: 'Jelen',
    absent: 'Hiányzott',
    excused: 'Igazolt',
};
const tone = computed(
    () =>
        ({
            done: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
            approved:
                'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
            active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
            rejected:
                'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
            urgent: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
            pending:
                'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
            waitlisted:
                'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
            review: 'bg-violet-100 text-violet-800 dark:bg-violet-950 dark:text-violet-200',
        })[props.value] || 'bg-muted text-muted-foreground',
);
</script>

<template>
    <span
        class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
        :class="tone"
        >{{ labels[value] ?? value }}</span
    >
</template>
