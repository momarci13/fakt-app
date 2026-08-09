<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Bell,
    CalendarDays,
    CheckCircle2,
    Clock3,
    GraduationCap,
    Sparkles,
} from '@lucide/vue';
import FaktPageHeader from '@/components/FaktPageHeader.vue';
import StatusPill from '@/components/StatusPill.vue';

type EventItem = {
    id: number;
    title: string;
    starts_at: string;
    location?: string;
    obligation: string;
    type: string;
};
type TaskItem = {
    id: number;
    title: string;
    due_at?: string;
    status: string;
    priority: string;
    assignees: { id: number; name: string }[];
};
type Progress = {
    code: string;
    name: string;
    current: number;
    threshold: number;
    percent: number;
    complete: boolean;
    kind: string;
};
type Announcement = {
    id: number;
    title: string;
    body: string;
    is_pinned: boolean;
    published_at: string;
    author: { name: string };
};

defineProps<{
    semester: { name: string } | null;
    roles: string[];
    events: EventItem[];
    tasks: TaskItem[];
    progress: Progress[];
    announcements: Announcement[];
}>();
const page = usePage();
const firstName = String((page.props.auth as any).user?.name ?? 'Tag').split(
    ' ',
)[0];
const notifications = (page.props.auth as any).notifications as Array<{
    id: string;
    title: string;
    message: string;
    url: string;
}>;
const openNotification = (notification: { id: string; url: string }) =>
    router.post(
        `/ertesitesek/${notification.id}/olvasott`,
        {},
        { onSuccess: () => router.visit(notification.url) },
    );
const date = (value?: string) =>
    value
        ? new Intl.DateTimeFormat('hu-HU', {
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          }).format(new Date(value))
        : 'Nincs határidő';
</script>

<template>
    <Head title="Áttekintés" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <FaktPageHeader
            eyebrow="Személyes irányítópult"
            :title="`Szia, ${firstName}!`"
            description="Itt találod a FAKT-os kötelezettségeidet, feladataidat és a következő eseményeket."
        >
            <template #actions
                ><span
                    class="rounded-full border bg-card px-3 py-2 text-sm font-medium"
                    >{{ semester?.name ?? 'Nincs aktív félév' }}</span
                ></template
            >
        </FaktPageHeader>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="fakt-panel relative overflow-hidden p-5 sm:col-span-2">
                <div
                    class="absolute -top-16 -right-10 size-44 rounded-full bg-primary/10"
                />
                <div
                    class="relative flex min-h-32 flex-col justify-between gap-5"
                >
                    <div class="flex items-center gap-2 text-primary">
                        <Sparkles class="size-5" /><span
                            class="text-sm font-semibold"
                            >Mai fókusz</span
                        >
                    </div>
                    <div>
                        <p class="text-2xl font-bold">
                            {{
                                tasks.filter((task) => task.status !== 'done')
                                    .length
                            }}
                            nyitott feladat
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{
                                events.length
                                    ? `${events.length} közelgő esemény a naptáradban`
                                    : 'A következő napokra nincs eseményed'
                            }}
                        </p>
                    </div>
                    <Link
                        href="/feladatok"
                        class="inline-flex items-center gap-1 text-sm font-semibold text-primary"
                        >Feladatok megnyitása <ArrowRight class="size-4"
                    /></Link>
                </div>
            </div>
            <div class="fakt-panel p-5">
                <div
                    class="mb-5 grid size-10 place-items-center rounded-xl bg-emerald-100 text-emerald-700"
                >
                    <CheckCircle2 class="size-5" />
                </div>
                <p class="text-3xl font-bold">
                    {{ tasks.filter((task) => task.status === 'done').length }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">Lezárt feladat</p>
            </div>
            <div class="fakt-panel p-5">
                <div
                    class="mb-5 grid size-10 place-items-center rounded-xl bg-blue-100 text-blue-700"
                >
                    <GraduationCap class="size-5" />
                </div>
                <p class="text-3xl font-bold">
                    {{ progress.filter((item) => item.complete).length }}/{{
                        progress.length
                    }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Teljesített életút-cél
                </p>
            </div>
        </section>

        <section v-if="notifications.length" class="fakt-panel overflow-hidden">
            <div class="flex items-center gap-3 border-b px-5 py-4">
                <Bell class="size-5 text-primary" />
                <div>
                    <p class="font-semibold">Új értesítések</p>
                    <p class="text-sm text-muted-foreground">
                        Feladat-, kurzus- és kérelemváltozások
                    </p>
                </div>
            </div>
            <button
                v-for="notification in notifications"
                :key="notification.id"
                type="button"
                class="flex w-full items-center gap-4 border-b px-5 py-4 text-left last:border-0 hover:bg-muted/40"
                @click="openNotification(notification)"
            >
                <span class="size-2 shrink-0 rounded-full bg-primary" /><span
                    class="min-w-0 flex-1"
                    ><strong class="block text-sm">{{
                        notification.title
                    }}</strong
                    ><span
                        class="mt-1 block truncate text-xs text-muted-foreground"
                        >{{ notification.message }}</span
                    ></span
                ><ArrowRight class="size-4 text-muted-foreground" />
            </button>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.15fr_.85fr]">
            <div class="fakt-panel overflow-hidden">
                <div
                    class="flex items-center justify-between border-b px-5 py-4"
                >
                    <div>
                        <p class="font-semibold">Következő események</p>
                        <p class="text-sm text-muted-foreground">
                            A személyes FAKT-naptáradból
                        </p>
                    </div>
                    <Link
                        href="/naptar"
                        class="text-sm font-semibold text-primary"
                        >Teljes naptár</Link
                    >
                </div>
                <div v-if="events.length" class="divide-y">
                    <article
                        v-for="event in events"
                        :key="event.id"
                        class="flex gap-4 px-5 py-4"
                    >
                        <div
                            class="grid size-11 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary"
                        >
                            <CalendarDays class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold">{{ event.title }}</p>
                                <StatusPill :value="event.obligation" />
                            </div>
                            <p
                                class="mt-1 flex flex-wrap gap-x-3 text-sm text-muted-foreground"
                            >
                                <span>{{ date(event.starts_at) }}</span
                                ><span v-if="event.location">{{
                                    event.location
                                }}</span>
                            </p>
                        </div>
                    </article>
                </div>
                <p
                    v-else
                    class="px-5 py-10 text-center text-sm text-muted-foreground"
                >
                    Nincs közelgő esemény.
                </p>
            </div>

            <div class="fakt-panel overflow-hidden">
                <div
                    class="flex items-center justify-between border-b px-5 py-4"
                >
                    <div>
                        <p class="font-semibold">Saját feladatok</p>
                        <p class="text-sm text-muted-foreground">
                            Határidő szerint
                        </p>
                    </div>
                    <Link
                        href="/feladatok"
                        class="text-sm font-semibold text-primary"
                        >Tábla</Link
                    >
                </div>
                <div class="divide-y">
                    <article
                        v-for="task in tasks"
                        :key="task.id"
                        class="px-5 py-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <p class="leading-5 font-medium">
                                {{ task.title }}
                            </p>
                            <StatusPill :value="task.status" />
                        </div>
                        <p
                            class="mt-2 flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <Clock3 class="size-3.5" />{{ date(task.due_at) }}
                        </p>
                    </article>
                    <p
                        v-if="!tasks.length"
                        class="px-5 py-10 text-center text-sm text-muted-foreground"
                    >
                        Minden feladatod kész.
                    </p>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.15fr_.85fr]">
            <div class="fakt-panel p-5">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <p class="font-semibold">FAKT életút</p>
                        <p class="text-sm text-muted-foreground">
                            Jóváhagyott eredmények alapján
                        </p>
                    </div>
                    <Link
                        href="/eletut"
                        class="text-sm font-semibold text-primary"
                        >Részletek</Link
                    >
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div v-for="item in progress.slice(0, 4)" :key="item.code">
                        <div class="mb-2 flex justify-between gap-3 text-sm">
                            <span class="font-medium">{{ item.name }}</span
                            ><span class="text-muted-foreground"
                                >{{ item.current }}/{{ item.threshold }}</span
                            >
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary transition-all"
                                :style="{ width: `${item.percent}%` }"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div class="fakt-panel overflow-hidden">
                <div class="border-b px-5 py-4">
                    <p class="font-semibold">Közlemények</p>
                    <p class="text-sm text-muted-foreground">A vezetőségtől</p>
                </div>
                <article
                    v-for="announcement in announcements"
                    :key="announcement.id"
                    class="border-b px-5 py-4 last:border-0"
                >
                    <div class="flex items-center gap-2">
                        <span
                            v-if="announcement.is_pinned"
                            class="size-2 rounded-full bg-primary"
                        />
                        <p class="font-semibold">{{ announcement.title }}</p>
                    </div>
                    <p
                        class="mt-2 line-clamp-2 text-sm leading-5 text-muted-foreground"
                    >
                        {{ announcement.body }}
                    </p>
                    <p class="mt-2 text-xs text-muted-foreground">
                        {{ announcement.author.name }}
                    </p>
                </article>
            </div>
        </section>
    </div>
</template>
