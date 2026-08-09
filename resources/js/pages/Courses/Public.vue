<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, GraduationCap, MapPin } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

type Course = {
    id: number;
    title: string;
    category: string;
    description?: string;
    instructor_name: string;
    capacity: number;
    starts_at: string;
    ends_at: string;
    location?: string;
};
defineProps<{ semester: { name: string } | null; courses: Course[] }>();
const date = (value: string) =>
    new Intl.DateTimeFormat('hu-HU', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Europe/Budapest',
    }).format(new Date(value));
</script>

<template>
    <Head title="Nyilvános kurzuskínálat" />
    <main
        class="min-h-screen bg-[radial-gradient(circle_at_top_left,rgba(48,131,48,.14),transparent_38%)] px-4 py-8 sm:px-6 lg:px-8"
    >
        <div class="mx-auto max-w-6xl">
            <header
                class="mb-10 flex flex-wrap items-center justify-between gap-4"
            >
                <Link href="/" class="flex items-center gap-3"
                    ><span
                        class="grid size-11 place-items-center rounded-2xl bg-primary text-primary-foreground"
                        ><AppLogoIcon class="size-7 fill-current"
                    /></span>
                    <div>
                        <p class="font-bold tracking-tight">FAKT</p>
                        <p class="text-xs text-muted-foreground">
                            Belső alkalmazás
                        </p>
                    </div></Link
                ><Link
                    href="/login"
                    class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground"
                    >Tagi belépés</Link
                >
            </header>
            <section class="mb-8 max-w-3xl">
                <p class="fakt-label text-primary">
                    {{ semester?.name || 'Aktuális félév' }}
                </p>
                <h1
                    class="mt-2 text-3xl font-extrabold tracking-tight sm:text-5xl"
                >
                    Nyilvános kurzuskínálat
                </h1>
                <p class="mt-4 text-base leading-7 text-muted-foreground">
                    A meghirdetett kurzusok időpontjai, oktatói és
                    férőhelyadatai bejelentkezés nélkül. Jelentkezéshez
                    FAKT-tagság és belépés szükséges.
                </p>
            </section>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="course in courses"
                    :key="course.id"
                    class="fakt-panel p-5"
                >
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <span
                            class="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary"
                            >{{ course.category }}</span
                        ><span class="text-xs font-medium text-muted-foreground"
                            >{{ course.capacity }} férőhely</span
                        >
                    </div>
                    <h2 class="text-xl font-bold">{{ course.title }}</h2>
                    <p class="mt-1 text-sm font-medium">
                        {{ course.instructor_name }}
                    </p>
                    <p
                        v-if="course.description"
                        class="mt-3 line-clamp-3 text-sm leading-6 text-muted-foreground"
                    >
                        {{ course.description }}
                    </p>
                    <div
                        class="mt-5 space-y-2 border-t pt-4 text-sm text-muted-foreground"
                    >
                        <p class="flex gap-2">
                            <CalendarDays
                                class="mt-0.5 size-4 shrink-0 text-primary"
                            />{{ date(course.starts_at) }} –
                            {{ date(course.ends_at) }}
                        </p>
                        <p v-if="course.location" class="flex gap-2">
                            <MapPin
                                class="mt-0.5 size-4 shrink-0 text-primary"
                            />{{ course.location }}
                        </p>
                    </div>
                </article>
                <div
                    v-if="!courses.length"
                    class="fakt-panel col-span-full grid min-h-56 place-items-center p-8 text-center"
                >
                    <div>
                        <GraduationCap
                            class="mx-auto mb-3 size-9 text-primary"
                        />
                        <p class="font-semibold">Még nincs publikált kurzus.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>
