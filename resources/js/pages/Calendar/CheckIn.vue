<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ScanLine } from '@lucide/vue';
import { onBeforeUnmount, onMounted } from 'vue';
import FaktPageHeader from '@/components/FaktPageHeader.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    event: {
        id: number;
        title: string;
        starts_at: string;
        ends_at: string;
        location?: string;
    };
    qrSvg: string;
    url: string;
    open: boolean;
    checkedIn: Array<{
        id: number;
        checked_in_at: string;
        user?: { name: string };
    }>;
}>();

const time = (value: string) =>
    new Intl.DateTimeFormat('hu-HU', {
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));

// The code rotates every 30 seconds; refresh it (and the list) every 20.
let timer: number | undefined;
onMounted(() => {
    timer = window.setInterval(
        () => router.reload({ only: ['qrSvg', 'url', 'open', 'checkedIn'] }),
        20000,
    );
});
onBeforeUnmount(() => window.clearInterval(timer));
</script>

<template>
    <Head title="QR bejelentkezés" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <FaktPageHeader
            eyebrow="Jelenlét"
            :title="event.title"
            description="Vetítsd ki ezt a képernyőt. A résztvevők a telefonjukkal beolvassák a kódot és megerősítik a jelenlétüket. A kód 30 másodpercenként változik."
        >
            <template #actions
                ><Button variant="outline" as-child
                    ><Link href="/naptar"
                        ><ChevronLeft class="size-4" />Naptár</Link
                    ></Button
                ></template
            >
        </FaktPageHeader>
        <section class="grid gap-6 lg:grid-cols-[auto_1fr]">
            <div class="fakt-panel grid place-items-center p-6">
                <div
                    v-if="open"
                    class="rounded-xl bg-white p-3"
                    v-html="qrSvg"
                />
                <p
                    v-else
                    class="max-w-xs p-8 text-center text-sm text-muted-foreground"
                >
                    A bejelentkezés a kezdés előtt 30 perccel nyílik és a
                    befejezés után 30 perccel zárul.
                </p>
                <p class="mt-3 text-xs text-muted-foreground">
                    {{ time(event.starts_at) }}–{{ time(event.ends_at) }}
                    <template v-if="event.location">
                        · {{ event.location }}</template
                    >
                </p>
            </div>
            <div class="fakt-panel overflow-hidden">
                <div class="flex items-center gap-2 border-b p-5">
                    <ScanLine class="size-5 text-primary" />
                    <h2 class="font-bold">
                        Bejelentkeztek ({{ checkedIn.length }})
                    </h2>
                </div>
                <ul class="divide-y">
                    <li
                        v-for="item in checkedIn"
                        :key="item.id"
                        class="flex justify-between px-5 py-3 text-sm"
                    >
                        <span>{{ item.user?.name }}</span
                        ><span class="text-muted-foreground">{{
                            time(item.checked_in_at)
                        }}</span>
                    </li>
                    <li
                        v-if="!checkedIn.length"
                        class="p-8 text-center text-sm text-muted-foreground"
                    >
                        Még senki nem jelentkezett be.
                    </li>
                </ul>
            </div>
        </section>
    </div>
</template>
