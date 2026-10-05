<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    BookOpen,
    CalendarClock,
    Download,
    MapPin,
    Users,
    Vote,
} from '@lucide/vue';
import { computed, ref } from 'vue';
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
type DateOption = {
    id: number;
    starts_at: string;
    ends_at: string;
    location?: string;
    voters_count: number;
    voted: boolean;
};
type Course = {
    id: number;
    title: string;
    category: string;
    description?: string;
    instructor_name: string;
    capacity: number;
    allowed_absences: number;
    approved_count: number;
    sessions_count: number;
    schedule_status: string;
    starts_at: string;
    ends_at: string;
    location?: string;
    enrollments: Enrollment[];
    date_options: DateOption[];
};
type Placement = {
    enrollment_id: number;
    course: string;
    user: string;
    rank: number;
    proposal: string;
};
const props = defineProps<{
    semester: {
        id: number;
        name: string;
        course_selection_open: boolean;
    } | null;
    courses: Course[];
    canManage: boolean;
    pendingEnrollments: Enrollment[];
    placement: Placement[];
}>();
const date = (value: string) =>
    new Intl.DateTimeFormat('hu-HU', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));

const pollMode = ref(false);
const optionRows = ref(3);
const placementChanges = computed(() =>
    props.placement.filter((row) => row.proposal === 'approved'),
);
const leadingOption = (course: Course) =>
    [...course.date_options].sort(
        (a, b) =>
            b.voters_count - a.voters_count ||
            a.starts_at.localeCompare(b.starts_at),
    )[0];
const canVote = (course: Course) =>
    course.schedule_status === 'polling' &&
    ['pending', 'approved', 'waitlisted'].includes(
        course.enrollments[0]?.status ?? '',
    );
</script>

<template>
    <Head title="Kurzusok" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <FaktPageHeader
            eyebrow="Szakmai program"
            :title="`${semester?.name ?? ''} kurzuskínálat`"
            description="Állítsd be a preferenciáidat. Ha több időpont közül kell választani, szavazz azokra, amelyek neked jók. A beosztás után az alkalmak bekerülnek a naptáradba."
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
                        class="fakt-panel absolute top-12 right-0 z-20 max-h-[80vh] w-[min(92vw,34rem)] overflow-y-auto p-5"
                    >
                        <Form
                            action="/kurzusok"
                            method="post"
                            class="grid gap-3"
                            v-slot="{ errors, processing }"
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
                                    name="instructor_name"
                                    class="fakt-input"
                                    placeholder="Oktató neve"
                                    required
                                />
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="grid gap-1 text-xs"
                                    >Férőhely<input
                                        type="number"
                                        name="capacity"
                                        value="15"
                                        min="1"
                                        max="100"
                                        class="fakt-input"
                                        required
                                /></label>
                                <label class="grid gap-1 text-xs"
                                    >Megengedett hiányzás<input
                                        type="number"
                                        name="allowed_absences"
                                        value="2"
                                        min="0"
                                        max="20"
                                        class="fakt-input"
                                /></label>
                            </div>
                            <label class="grid gap-1 text-xs"
                                >Ismétlődés<select
                                    name="recurrence_rule"
                                    class="fakt-input"
                                >
                                    <option value="">Egyetlen alkalom</option>
                                    <option value="FREQ=WEEKLY">
                                        Hetente, a félév végéig
                                    </option>
                                    <option value="FREQ=WEEKLY;COUNT=6">
                                        Hetente, 6 alkalom
                                    </option>
                                    <option value="FREQ=WEEKLY;COUNT=10">
                                        Hetente, 10 alkalom
                                    </option>
                                    <option
                                        value="FREQ=WEEKLY;INTERVAL=2;COUNT=6"
                                    >
                                        Kéthetente, 6 alkalom
                                    </option>
                                    <option value="FREQ=MONTHLY;COUNT=4">
                                        Havonta, 4 alkalom
                                    </option>
                                </select></label
                            >
                            <div
                                class="flex gap-1 rounded-lg bg-muted p-1 text-sm"
                            >
                                <button
                                    type="button"
                                    class="flex-1 rounded-md px-3 py-1.5"
                                    :class="!pollMode && 'bg-background shadow'"
                                    @click="pollMode = false"
                                >
                                    Fix időpont
                                </button>
                                <button
                                    type="button"
                                    class="flex-1 rounded-md px-3 py-1.5"
                                    :class="pollMode && 'bg-background shadow'"
                                    @click="pollMode = true"
                                >
                                    Időpontszavazás
                                </button>
                            </div>
                            <div
                                v-if="!pollMode"
                                class="grid grid-cols-2 gap-2"
                            >
                                <label class="grid gap-1 text-xs"
                                    >Első alkalom kezdete<input
                                        type="datetime-local"
                                        name="starts_at"
                                        class="fakt-input"
                                        required
                                /></label>
                                <label class="grid gap-1 text-xs"
                                    >Vége<input
                                        type="datetime-local"
                                        name="ends_at"
                                        class="fakt-input"
                                        required
                                /></label>
                            </div>
                            <div v-else class="grid gap-2">
                                <p class="text-xs text-muted-foreground">
                                    A jelentkezők megjelölik a nekik megfelelő
                                    időpontokat, a döntést a KTSZT hozza meg.
                                </p>
                                <div
                                    v-for="index in optionRows"
                                    :key="index"
                                    class="grid grid-cols-[1fr_1fr] gap-2"
                                >
                                    <input
                                        type="datetime-local"
                                        :name="`date_options[${index - 1}][starts_at]`"
                                        class="fakt-input"
                                        :aria-label="`${index}. időpont kezdete`"
                                    /><input
                                        type="datetime-local"
                                        :name="`date_options[${index - 1}][ends_at]`"
                                        class="fakt-input"
                                        :aria-label="`${index}. időpont vége`"
                                    />
                                </div>
                                <Button
                                    v-if="optionRows < 8"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="optionRows++"
                                    >+ időpont</Button
                                >
                            </div>
                            <p
                                v-for="(message, field) in errors"
                                :key="field"
                                class="text-xs text-destructive"
                            >
                                {{ message }}
                            </p>
                            <input
                                name="location"
                                class="fakt-input"
                                placeholder="Helyszín"
                            /><textarea
                                name="description"
                                class="fakt-textarea"
                                placeholder="Leírás"
                            /><Button type="submit" :disabled="processing"
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
                            /><template
                                v-if="course.schedule_status === 'polling'"
                                ><StatusPill value="polling" /></template
                            ><template v-else
                                >{{ date(course.starts_at) }} ·
                                {{ course.sessions_count }} alkalom</template
                            >
                        </p>
                        <p class="flex items-center gap-2">
                            <MapPin class="size-4 text-muted-foreground" />{{
                                course.location || 'Helyszín egyeztetés alatt'
                            }}
                        </p>
                        <p class="flex items-center gap-2">
                            <Users class="size-4 text-muted-foreground" />{{
                                course.approved_count
                            }}/{{ course.capacity }} jóváhagyott hely ·
                            legfeljebb {{ course.allowed_absences }} hiányzás
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

                    <div
                        v-if="course.schedule_status === 'polling'"
                        class="mt-5 rounded-xl border p-3"
                    >
                        <p
                            class="flex items-center gap-2 text-sm font-semibold"
                        >
                            <Vote class="size-4 text-primary" />Melyik időpont
                            jó neked?
                        </p>
                        <Form
                            v-if="canVote(course)"
                            :action="`/kurzusok/${course.id}/idopont-szavazas`"
                            method="post"
                            class="mt-3 grid gap-2"
                            v-slot="{ processing }"
                        >
                            <label
                                v-for="option in course.date_options"
                                :key="option.id"
                                class="flex items-center gap-2 text-sm"
                                ><input
                                    type="checkbox"
                                    name="option_ids[]"
                                    :value="option.id"
                                    :checked="option.voted"
                                    class="size-4"
                                />{{ date(option.starts_at) }}
                                <span
                                    class="ml-auto text-xs text-muted-foreground"
                                    >{{ option.voters_count }} szavazat</span
                                ></label
                            >
                            <Button
                                type="submit"
                                size="sm"
                                variant="outline"
                                :disabled="processing"
                                >Szavazat mentése</Button
                            >
                        </Form>
                        <ul v-else class="mt-3 grid gap-1 text-sm">
                            <li
                                v-for="option in course.date_options"
                                :key="option.id"
                                class="flex justify-between"
                            >
                                <span>{{ date(option.starts_at) }}</span
                                ><span class="text-xs text-muted-foreground"
                                    >{{ option.voters_count }} szavazat</span
                                >
                            </li>
                            <li class="text-xs text-muted-foreground">
                                Szavazni a jelentkezés után tudsz.
                            </li>
                        </ul>
                        <Form
                            v-if="canManage"
                            :action="`/kurzusok/${course.id}/idopont`"
                            method="post"
                            class="mt-3 flex gap-2 border-t pt-3"
                            ><select
                                name="option_id"
                                class="fakt-input"
                                aria-label="Végleges időpont"
                            >
                                <option
                                    v-for="option in course.date_options"
                                    :key="option.id"
                                    :value="option.id"
                                    :selected="
                                        option.id === leadingOption(course)?.id
                                    "
                                >
                                    {{ date(option.starts_at) }} ({{
                                        option.voters_count
                                    }})
                                </option></select
                            ><Button type="submit" size="sm"
                                >Kijelölés</Button
                            ></Form
                        >
                    </div>

                    <div class="mt-5 border-t pt-4">
                        <p class="text-xs text-muted-foreground">Oktató</p>
                        <p class="font-semibold">
                            {{ course.instructor_name }}
                        </p>
                    </div>
                    <div class="mt-5 grid gap-2">
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
                        <a
                            v-if="canManage"
                            :href="`/kurzusok/${course.id}/nevsor.csv`"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary"
                            ><Download class="size-3.5" />Névsor és hiányzások
                            (CSV)</a
                        >
                    </div>
                </div>
            </article>
        </section>

        <section v-if="canManage" class="fakt-panel overflow-hidden">
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b p-5"
            >
                <div>
                    <p class="fakt-label text-primary">KTSZT beosztás</p>
                    <h2 class="mt-1 text-lg font-bold">
                        Automatikus beosztási javaslat
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Kurzusonként a legjobb preferenciájú jelentkezők kapják
                        a szabad helyeket, a többiek várólistára kerülnek. A
                        saját jelentkezésedről nem dönthetsz, azt kihagyja.
                    </p>
                </div>
                <Form
                    v-if="placement.length"
                    action="/kurzusok/beosztas"
                    method="post"
                    ><input
                        type="hidden"
                        name="seed"
                        :value="semester?.id"
                    /><Button type="submit"
                        >Javaslat alkalmazása ({{
                            placementChanges.length
                        }}
                        jóváhagyás)</Button
                    ></Form
                >
            </div>
            <div class="divide-y">
                <div
                    v-for="row in placement"
                    :key="row.enrollment_id"
                    class="flex items-center gap-3 px-5 py-3 text-sm"
                >
                    <span class="min-w-0 flex-1"
                        ><strong>{{ row.user }}</strong> · {{ row.course }} ·
                        {{ row.rank }}. preferencia</span
                    ><StatusPill :value="row.proposal" />
                </div>
                <p
                    v-if="!placement.length"
                    class="p-8 text-center text-sm text-muted-foreground"
                >
                    Nincs beosztásra váró jelentkezés.
                </p>
            </div>
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
