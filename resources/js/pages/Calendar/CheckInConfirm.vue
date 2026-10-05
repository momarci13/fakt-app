<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { CalendarCheck } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{
    event: { id: number; title: string; starts_at: string; location?: string };
    code: string;
    valid: boolean;
}>();
const date = (value: string) =>
    new Intl.DateTimeFormat('hu-HU', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
</script>

<template>
    <Head title="Bejelentkezés eseményre" />
    <div class="flex flex-1 items-center justify-center p-4">
        <div class="fakt-panel w-full max-w-sm p-6 text-center">
            <div
                class="mx-auto mb-4 grid size-12 place-items-center rounded-xl bg-primary/10 text-primary"
            >
                <CalendarCheck class="size-6" />
            </div>
            <h1 class="text-lg font-bold">{{ event.title }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ date(event.starts_at) }}
                <template v-if="event.location">
                    · {{ event.location }}</template
                >
            </p>
            <Form
                v-if="valid"
                :action="`/naptar/bejelentkezes/${event.id}/${code}`"
                method="post"
                class="mt-6"
                v-slot="{ processing }"
                ><Button type="submit" class="w-full" :disabled="processing"
                    >Itt vagyok – bejelentkezem</Button
                ></Form
            >
            <div v-else class="mt-6 grid gap-3">
                <p class="text-sm text-destructive">
                    Ez a kód lejárt, vagy az esemény most nem fogad
                    bejelentkezést. Olvasd be újra a kivetített kódot.
                </p>
                <Button variant="outline" as-child
                    ><Link href="/naptar">Vissza a naptárhoz</Link></Button
                >
            </div>
        </div>
    </div>
</template>
