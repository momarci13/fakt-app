<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { BookOpen, CalendarClock, MapPin, Users } from '@lucide/vue';
import FaktPageHeader from '@/components/FaktPageHeader.vue';
import StatusPill from '@/components/StatusPill.vue';
import { Button } from '@/components/ui/button';

type Enrollment = {
    id: number;
    preference_rank: number;
    status: string;
    user?: { name: string };
    course?: { title: string };
};
type Course = {
    id: number;
    title: string;
    category: string;
    description?: string;
    instructor_name: string;
    capacity: number;
    approved_count: number;
    starts_at: string;
    ends_at: string;
    location?: string;
    enrollments: Enrollment[];
};
defineProps<{
    semester: { name: string; course_selection_open: boolean } | null;
    courses: Course[];
    canManage: boolean;
    pendingEnrollments: Enrollment[];
}>();
const date = (value: string) =>
    new Intl.DateTimeFormat('hu-HU', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
</script>

<template>
    <Head title="Kurzusok" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <FaktPageHeader
            eyebrow="Szakmai program"
            title="Tavaszi kurzuskínálat"
            description="Állítsd be a preferenciáidat. A végleges beosztást a Szakmaiság vezetése hagyja jóvá."
        >
            <template #actions
                ><span
                    class="rounded-full px-3 py-2 text-sm font-semibold"
                    :class="
                        semester?.course_selection_open
                            ? 'bg-emerald-100 text-emerald-800'
                            : 'bg-muted text-muted-foreground'
                    "
                    >{{
                        semester?.course_selection_open
                            ? 'Jelentkezés nyitva'
                            : 'Jelentkezés lezárva'
                    }}</span
                >
                <details v-if="canManage" class="relative">
                    <summary
                        class="inline-flex h-10 cursor-pointer list-none items-center rounded-lg bg-primary px-4 text-sm font-semibold text-primary-foreground"
                    >
                        Új kurzus
                    </summary>
                    <div
                        class="fakt-panel absolute top-12 right-0 z-20 w-[min(92vw,32rem)] p-5"
                    >
                        <Form
                            action="/kurzusok"
                            method="post"
                            class="grid gap-3"
                            ><input
                                name="title"
                                class="fakt-input"
                                placeholder="Kurzus neve"
                                required
                            />
                            <div class="grid grid-cols-2 gap-2">
                                <input
                                    name="category"
                                    class="fakt-input"
                                    placeholder="Kategória"
                                    required
                                /><input
                                    type="number"
                                    name="capacity"
                                    value="15"
                                    min="1"
                                    max="100"
                                    class="fakt-input"
                                    required
                                />
                            </div>
                            <input
                                name="instructor_name"
                                class="fakt-input"
                                placeholder="Oktató neve"
                                required
                            />
                            <div class="grid grid-cols-2 gap-2">
                                <input
                                    type="datetime-local"
                                    name="starts_at"
                                    class="fakt-input"
                                    required
                                /><input
                                    type="datetime-local"
                                    name="ends_at"
                                    class="fakt-input"
                                    required
                                />
                            </div>
                            <input
                                name="location"
                                class="fakt-input"
                                placeholder="Helyszín"
                            /><textarea
                                name="description"
                                class="fakt-textarea"
                                placeholder="Leírás"
                            /><Button type="submit"
                                >Kurzus létrehozása</Button
                            ></Form
                        >
                    </div>
                </details></template
            >
        </FaktPageHeader>

        <section class="grid gap-5 md:grid-cols-2 2xl:grid-cols-3">
            <article
                v-for="course in courses"
                :key="course.id"
                class="fakt-panel flex flex-col overflow-hidden"
            >
                <div class="h-1.5 bg-primary" />
                <div class="flex flex-1 flex-col p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="fakt-label text-primary">
                                {{ course.category }}
                            </p>
                            <h2 class="mt-2 text-lg leading-6 font-bold">
                                {{ course.title }}
                            </h2>
                        </div>
                        <div
                            class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary"
                        >
                            <BookOpen class="size-5" />
                        </div>
                    </div>
                    <p
                        class="mt-3 line-clamp-3 text-sm leading-6 text-muted-foreground"
                    >
                        {{ course.description }}
                    </p>
                    <div class="mt-5 grid gap-2 text-sm">
                        <p class="flex items-center gap-2">
                            <CalendarClock
                                class="size-4 text-muted-foreground"
                            />{{ date(course.starts_at) }}
                        </p>
                        <p class="flex items-center gap-2">
                            <MapPin class="size-4 text-muted-foreground" />{{
                                course.location || 'Helyszín egyeztetés alatt'
                            }}
                        </p>
                        <p class="flex items-center gap-2">
                            <Users class="size-4 text-muted-foreground" />{{
                                course.approved_count
                            }}/{{ course.capacity }} jóváhagyott hely
                        </p>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full bg-primary"
                            :style="{
                                width: `${Math.min(100, (course.approved_count / course.capacity) * 100)}%`,
                            }"
                        />
                    </div>
                    <div class="mt-5 border-t pt-4">
                        <p class="text-xs text-muted-foreground">Oktató</p>
                        <p class="font-semibold">
                            {{ course.instructor_name }}
                        </p>
                    </div>
                    <div class="mt-5">
                        <div
                            v-if="course.enrollments[0]"
                            class="flex items-center justify-between rounded-xl bg-muted p-3"
                        >
                            <span class="text-sm"
                                >{{ course.enrollments[0].preference_rank }}.
                                preferencia</span
                            ><StatusPill
                                :value="course.enrollments[0].status"
                            />
                        </div>
                        <Form
                            v-else-if="semester?.course_selection_open"
                            :action="`/kurzusok/${course.id}/jelentkezes`"
                            method="post"
                            class="flex gap-2"
                            v-slot="{ processing }"
                            ><select
                                name="preference_rank"
                                class="fakt-input"
                                aria-label="Preferenciasorrend"
                            >
                                <option
                                    v-for="rank in 9"
                                    :key="rank"
                                    :value="rank"
                                >
                                    {{ rank }}. preferencia
                                </option></select
                            ><Button type="submit" :disabled="processing"
                                >Jelentkezem</Button
                            ></Form
                        >
                    </div>
                </div>
            </article>
        </section>

        <section v-if="canManage" class="fakt-panel overflow-hidden">
            <div class="border-b p-5">
                <p class="fakt-label text-primary">Szakmaiság vezetői nézet</p>
                <h2 class="mt-1 text-lg font-bold">
                    Elbírálandó jelentkezések
                </h2>
            </div>
            <div class="divide-y">
                <article
                    v-for="enrollment in pendingEnrollments"
                    :key="enrollment.id"
                    class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center"
                >
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold">{{ enrollment.user?.name }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ enrollment.course?.title }} ·
                            {{ enrollment.preference_rank }}. preferencia
                        </p>
                    </div>
                    <Form
                        :action="`/kurzusjelentkezesek/${enrollment.id}/elbiras`"
                        method="patch"
                        class="flex gap-2"
                        ><Button name="status" value="approved" size="sm"
                            >Jóváhagyás</Button
                        ><Button
                            name="status"
                            value="waitlisted"
                            variant="outline"
                            size="sm"
                            >Várólista</Button
                        ><Button
                            name="status"
                            value="rejected"
                            variant="ghost"
                            size="sm"
                            >Elutasítás</Button
                        ></Form
                    >
                </article>
                <p
                    v-if="!pendingEnrollments.length"
                    class="p-8 text-center text-sm text-muted-foreground"
                >
                    Nincs elbírálandó jelentkezés.
                </p>
            </div>
        </section>
    </div>
</template>
