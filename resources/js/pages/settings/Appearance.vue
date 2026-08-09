<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Megjelenés',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Megjelenés" />

    <h1 class="sr-only">Megjelenési beállítások</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Megjelenési beállítások"
            description="Válassz világos, sötét vagy rendszer szerinti témát"
        />
        <AppearanceTabs />
    </div>
</template>
