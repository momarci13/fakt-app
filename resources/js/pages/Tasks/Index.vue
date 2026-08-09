<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    CalendarClock,
    Check,
    ChevronRight,
    ListTodo,
    MessageSquare,
    Plus,
    Users,
} from '@lucide/vue';
import FaktPageHeader from '@/components/FaktPageHeader.vue';
import { Button } from '@/components/ui/button';

type TaskItem = {
    id: number;
    title: string;
    description?: string;
    status: string;
    priority: string;
    due_at?: string;
    assignees: { id: number; name: string }[];
    org_unit?: { name: string; color: string };
    project?: { name: string };
    comments: unknown[];
};
type Option = { id: number; name: string };
const props = defineProps<{
    tasks: TaskItem[];
    canAssign: boolean;
    assignees: Option[];
    units: Option[];
    projects: Option[];
}>();
const columns = [
    { key: 'todo', title: 'Teendő' },
    { key: 'in_progress', title: 'Folyamatban' },
    { key: 'review', title: 'Ellenőrzés' },
    { key: 'done', title: 'Kész' },
];
const tasksFor = (status: string) =>
    props.tasks.filter((task) => task.status === status);
const date = (value?: string) =>
    value
        ? new Intl.DateTimeFormat('hu-HU', {
              month: 'short',
              day: 'numeric',
          }).format(new Date(value))
        : 'Nincs határidő';
const priorityTone = (value: string) =>
    ({
        urgent: 'bg-red-100 text-red-800',
        high: 'bg-orange-100 text-orange-800',
        normal: 'bg-blue-100 text-blue-800',
        low: 'bg-muted text-muted-foreground',
    })[value];
</script>

<template>
    <Head title="Feladatok" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <FaktPageHeader
            eyebrow="Operatív munka"
            title="Saját és csapatfeladatok"
            description="A jogosultsági körödbe tartozó személyes, Team- és projektfeladatok."
        >
            <template #actions
                ><details class="relative">
                    <summary
                        class="inline-flex h-10 cursor-pointer list-none items-center gap-2 rounded-lg bg-primary px-4 text-sm font-semibold text-primary-foreground"
                    >
                        <Plus class="size-4" />Új feladat
                    </summary>
                    <div
                        class="fakt-panel absolute top-12 right-0 z-20 w-[min(92vw,32rem)] p-5"
                    >
                        <Form
                            action="/feladatok"
                            method="post"
                            class="grid gap-3"
                            v-slot="{ processing }"
                            ><input
                                name="title"
                                class="fakt-input"
                                placeholder="Feladat címe"
                                required
                            /><textarea
                                name="description"
                                class="fakt-textarea"
                                placeholder="Leírás és elfogadási feltételek"
                            />
                            <div class="grid grid-cols-2 gap-2">
                                <select name="priority" class="fakt-input">
                                    <option value="normal">
                                        Normál prioritás
                                    </option>
                                    <option value="high">Magas</option>
                                    <option value="urgent">Sürgős</option>
                                    <option value="low">
                                        Alacsony
                                    </option></select
                                ><input
                                    type="datetime-local"
                                    name="due_at"
                                    class="fakt-input"
                                />
                            </div>
                            <div
                                v-if="canAssign"
                                class="grid grid-cols-2 gap-2"
                            >
                                <select name="org_unit_id" class="fakt-input">
                                    <option value="">Személyes</option>
                                    <option
                                        v-for="unit in units"
                                        :key="unit.id"
                                        :value="unit.id"
                                    >
                                        {{ unit.name }}
                                    </option></select
                                ><select name="project_id" class="fakt-input">
                                    <option value="">Nincs projekt</option>
                                    <option
                                        v-for="project in projects"
                                        :key="project.id"
                                        :value="project.id"
                                    >
                                        {{ project.name }}
                                    </option>
                                </select>
                            </div>
                            <label class="fakt-label">Felelősök</label
                            ><select
                                name="assignee_ids[]"
                                class="fakt-input"
                                multiple
                                required
                            >
                                <option
                                    v-for="person in assignees"
                                    :key="person.id"
                                    :value="person.id"
                                >
                                    {{ person.name }}
                                </option></select
                            ><Button type="submit" :disabled="processing"
                                >Feladat létrehozása</Button
                            ></Form
                        >
                    </div>
                </details></template
            >
        </FaktPageHeader>

        <div class="grid gap-4 lg:grid-cols-2 2xl:grid-cols-4">
            <section
                v-for="(column, columnIndex) in columns"
                :key="column.key"
                class="rounded-2xl bg-muted/60 p-3"
            >
                <header class="mb-3 flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <ListTodo class="size-4 text-muted-foreground" />
                        <h2 class="text-sm font-bold">{{ column.title }}</h2>
                    </div>
                    <span
                        class="grid size-6 place-items-center rounded-full bg-background text-xs font-semibold"
                        >{{ tasksFor(column.key).length }}</span
                    >
                </header>
                <div class="grid gap-3">
                    <article
                        v-for="task in tasksFor(column.key)"
                        :key="task.id"
                        class="fakt-panel p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <span
                                class="rounded-full px-2 py-1 text-[10px] font-bold uppercase"
                                :class="priorityTone(task.priority)"
                                >{{ task.priority }}</span
                            ><span
                                v-if="task.org_unit"
                                class="size-2.5 rounded-full"
                                :style="{ background: task.org_unit.color }"
                                :title="task.org_unit.name"
                            />
                        </div>
                        <h3 class="mt-3 leading-5 font-bold">
                            {{ task.title }}
                        </h3>
                        <p
                            v-if="task.description"
                            class="mt-2 line-clamp-2 text-sm leading-5 text-muted-foreground"
                        >
                            {{ task.description }}
                        </p>
                        <div
                            class="mt-4 grid gap-2 text-xs text-muted-foreground"
                        >
                            <p class="flex items-center gap-1.5">
                                <CalendarClock class="size-3.5" />{{
                                    date(task.due_at)
                                }}
                            </p>
                            <p class="flex items-center gap-1.5">
                                <Users class="size-3.5" />{{
                                    task.assignees
                                        .map((person) => person.name)
                                        .join(', ')
                                }}
                            </p>
                            <p
                                v-if="task.comments.length"
                                class="flex items-center gap-1.5"
                            >
                                <MessageSquare class="size-3.5" />{{
                                    task.comments.length
                                }}
                                hozzászólás
                            </p>
                        </div>
                        <Form
                            v-if="columnIndex < columns.length - 1"
                            :action="`/feladatok/${task.id}`"
                            method="patch"
                            class="mt-4"
                            ><input
                                type="hidden"
                                name="status"
                                :value="columns[columnIndex + 1].key" /><Button
                                type="submit"
                                variant="outline"
                                size="sm"
                                class="w-full"
                                >{{ columnIndex === 2 ? 'Lezárás' : 'Tovább'
                                }}<component
                                    :is="
                                        columnIndex === 2 ? Check : ChevronRight
                                    "
                                    class="size-4" /></Button
                        ></Form>
                    </article>
                    <p
                        v-if="!tasksFor(column.key).length"
                        class="rounded-xl border border-dashed p-6 text-center text-xs text-muted-foreground"
                    >
                        Nincs feladat ebben az oszlopban.
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>
