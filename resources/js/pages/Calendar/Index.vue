<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import {
    CalendarPlus,
    Copy,
    ExternalLink,
    MapPin,
    RotateCw,
} from '@lucide/vue';
import FaktPageHeader from '@/components/FaktPageHeader.vue';
import StatusPill from '@/components/StatusPill.vue';
import { Button } from '@/components/ui/button';

type EventItem = {
    id: number;
    title: string;
    description?: string;
    type: string;
    starts_at: string;
    ends_at: string;
    location?: string;
    obligation: string;
    visibility: string;
    organizer_id: number;
    agenda?: string;
    minutes?: string;
    decision_summary?: string;
    quorum_required?: number;
    participant_count?: number;
    attendances: { rsvp_status: string; final_status?: string }[];
    org_unit?: { name: string; color: string };
};
defineProps<{
    events: EventItem[];
    canCreate: boolean;
    calendarUrl?: string;
    members: Array<{ id: number; name: string }>;
}>();
const currentUserId = Number((usePage().props.auth as any).user?.id);
const day = (value: string) =>
    new Intl.DateTimeFormat('hu-HU', { month: 'short', day: 'numeric' }).format(
        new Date(value),
    );
const time = (value: string) =>
    new Intl.DateTimeFormat('hu-HU', {
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
const copy = async (value?: string) => {
    if (value) {
        await navigator.clipboard.writeText(value);
    }
};
</script>

<template>
    <Head title="Naptár" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <FaktPageHeader
            eyebrow="Egységes időrend"
            title="Személyes FAKT-naptár"
            description="A teljes szervezeti program, a kurzusaid, Teamed és projektjeid eseményei egy helyen."
        >
            <template #actions
                ><details v-if="canCreate" class="relative">
                    <summary
                        class="inline-flex h-10 cursor-pointer list-none items-center gap-2 rounded-lg bg-primary px-4 text-sm font-semibold text-primary-foreground"
                    >
                        <CalendarPlus class="size-4" />Új esemény
                    </summary>
                    <div
                        class="fakt-panel absolute top-12 right-0 z-20 w-[min(92vw,30rem)] p-5"
                    >
                        <Form
                            action="/naptar/esemenyek"
                            method="post"
                            class="grid gap-3"
                            v-slot="{ processing }"
                            ><input
                                name="title"
                                class="fakt-input"
                                placeholder="Esemény neve"
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
                            />
                            <div class="grid grid-cols-3 gap-2">
                                <select name="type" class="fakt-input">
                                    <option value="assembly">Gyűlés</option>
                                    <option value="community">Közösségi</option>
                                    <option value="professional">
                                        Szakmai
                                    </option>
                                    <option value="team">Team</option></select
                                ><select name="visibility" class="fakt-input">
                                    <option value="company">
                                        Teljes szervezet
                                    </option>
                                    <option value="members">Tagok</option>
                                    <option value="alumni">
                                        Alumni
                                    </option></select
                                ><select name="obligation" class="fakt-input">
                                    <option value="optional">Opcionális</option>
                                    <option value="required">Kötelező</option>
                                </select>
                            </div>
                            <textarea
                                name="description"
                                class="fakt-textarea"
                                placeholder="Leírás"
                            /><Button type="submit" :disabled="processing"
                                >Esemény létrehozása</Button
                            ></Form
                        >
                    </div>
                </details></template
            >
        </FaktPageHeader>

        <section class="grid gap-6 xl:grid-cols-[1fr_20rem]">
            <div class="fakt-panel overflow-hidden">
                <div class="border-b px-5 py-4">
                    <p class="font-semibold">Következő események</p>
                    <p class="text-sm text-muted-foreground">
                        Europe/Budapest időzóna
                    </p>
                </div>
                <div class="divide-y">
                    <article
                        v-for="event in events"
                        :key="event.id"
                        class="grid grid-cols-[4rem_1fr] gap-4 p-4 sm:grid-cols-[5rem_1fr_auto] sm:p-5"
                    >
                        <div
                            class="rounded-xl bg-primary/10 px-2 py-3 text-center text-primary"
                        >
                            <p class="text-xs font-bold uppercase">
                                {{ day(event.starts_at).split(' ')[0] }}
                            </p>
                            <p class="text-xl font-black">
                                {{ new Date(event.starts_at).getDate() }}
                            </p>
                            <p class="text-xs">{{ time(event.starts_at) }}</p>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-bold">{{ event.title }}</h2>
                                <StatusPill :value="event.obligation" />
                            </div>
                            <p
                                v-if="event.description"
                                class="mt-1 line-clamp-2 text-sm leading-5 text-muted-foreground"
                            >
                                {{ event.description }}
                            </p>
                            <p
                                v-if="event.location"
                                class="mt-2 flex items-center gap-1.5 text-xs text-muted-foreground"
                            >
                                <MapPin class="size-3.5" />{{
                                    event.location
                                }}
                                · {{ time(event.starts_at) }}–{{
                                    time(event.ends_at)
                                }}
                            </p>
                            <p
                                v-if="event.attendances[0]?.final_status"
                                class="mt-2"
                            >
                                <StatusPill
                                    :value="event.attendances[0].final_status!"
                                />
                            </p>
                            <details
                                v-if="event.type === 'assembly'"
                                class="mt-3 rounded-xl border p-3 text-sm"
                            >
                                <summary class="cursor-pointer font-semibold">
                                    Napirend, jegyzőkönyv és határozatok
                                </summary>
                                <div
                                    class="mt-3 space-y-3 text-muted-foreground"
                                >
                                    <p v-if="event.agenda">
                                        <strong class="text-foreground"
                                            >Napirend:</strong
                                        ><br />{{ event.agenda }}
                                    </p>
                                    <p v-if="event.minutes">
                                        <strong class="text-foreground"
                                            >Jegyzőkönyv:</strong
                                        ><br />{{ event.minutes }}
                                    </p>
                                    <p v-if="event.decision_summary">
                                        <strong class="text-foreground"
                                            >Határozatok és eredmény:</strong
                                        ><br />{{ event.decision_summary }}
                                    </p>
                                    <p v-if="event.quorum_required">
                                        Jelenlévők:
                                        {{ event.participant_count || 0 }} /
                                        {{ event.quorum_required }} ·
                                        {{
                                            (event.participant_count || 0) >=
                                            event.quorum_required
                                                ? 'határozatképes'
                                                : 'nem határozatképes'
                                        }}
                                    </p>
                                </div>
                                <Form
                                    v-if="
                                        canCreate &&
                                        event.organizer_id === currentUserId
                                    "
                                    :action="`/naptar/esemenyek/${event.id}/jegyzokonyv`"
                                    method="patch"
                                    class="mt-4 grid gap-2"
                                >
                                    <textarea
                                        name="agenda"
                                        class="fakt-textarea"
                                        placeholder="Napirend"
                                        :value="event.agenda"
                                    /><textarea
                                        name="minutes"
                                        class="fakt-textarea"
                                        placeholder="Jegyzőkönyv"
                                        :value="event.minutes"
                                    /><textarea
                                        name="decision_summary"
                                        class="fakt-textarea"
                                        placeholder="Határozatok és eredmény"
                                        :value="event.decision_summary"
                                    />
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            type="number"
                                            name="quorum_required"
                                            class="fakt-input"
                                            placeholder="Határozatképességi minimum"
                                            :value="event.quorum_required"
                                        /><input
                                            type="number"
                                            name="participant_count"
                                            class="fakt-input"
                                            placeholder="Jelenlévők"
                                            :value="event.participant_count"
                                        />
                                    </div>
                                    <Button type="submit" size="sm"
                                        >Jegyzőkönyv mentése</Button
                                    ></Form
                                >
                            </details>
                        </div>
                        <div
                            class="col-span-2 grid gap-2 sm:col-span-1 sm:self-center"
                        >
                            <Form
                                :action="`/naptar/esemenyek/${event.id}/visszajelzes`"
                                method="put"
                                class="flex gap-2"
                                ><Button
                                    name="rsvp_status"
                                    value="attending"
                                    size="sm"
                                    :variant="
                                        event.attendances[0]?.rsvp_status ===
                                        'attending'
                                            ? 'default'
                                            : 'outline'
                                    "
                                    >Ott leszek</Button
                                ><Button
                                    name="rsvp_status"
                                    value="not_attending"
                                    size="sm"
                                    variant="ghost"
                                    >Nem jó</Button
                                ></Form
                            >
                            <details>
                                <summary
                                    class="cursor-pointer text-center text-xs font-semibold text-primary"
                                >
                                    Indokolt távolmaradás
                                </summary>
                                <Form
                                    :action="`/naptar/esemenyek/${event.id}/visszajelzes`"
                                    method="put"
                                    class="mt-2 grid gap-2"
                                    ><input
                                        type="hidden"
                                        name="rsvp_status"
                                        value="excused_requested"
                                    /><textarea
                                        name="excuse_reason"
                                        class="fakt-textarea"
                                        placeholder="Indoklás"
                                        required
                                    /><Button
                                        type="submit"
                                        size="sm"
                                        variant="outline"
                                        >Kérelem küldése</Button
                                    ></Form
                                >
                            </details>
                            <details
                                v-if="
                                    canCreate &&
                                    event.organizer_id === currentUserId
                                "
                            >
                                <summary
                                    class="cursor-pointer text-center text-xs font-semibold text-primary"
                                >
                                    Végleges jelenlét
                                </summary>
                                <Form
                                    :action="`/naptar/esemenyek/${event.id}/jelenlet`"
                                    method="patch"
                                    class="mt-2 grid gap-2"
                                    ><select
                                        name="user_id"
                                        class="fakt-input"
                                        required
                                    >
                                        <option value="">
                                            Tag kiválasztása
                                        </option>
                                        <option
                                            v-for="member in members"
                                            :key="member.id"
                                            :value="member.id"
                                        >
                                            {{ member.name }}
                                        </option></select
                                    ><select
                                        name="final_status"
                                        class="fakt-input"
                                    >
                                        <option value="present">Jelen</option>
                                        <option value="absent">
                                            Hiányzott
                                        </option>
                                        <option value="excused">
                                            Igazolt
                                        </option></select
                                    ><Button type="submit" size="sm"
                                        >Rögzítés</Button
                                    ></Form
                                >
                            </details>
                        </div>
                    </article>
                    <p
                        v-if="!events.length"
                        class="p-10 text-center text-sm text-muted-foreground"
                    >
                        A naptárad még üres.
                    </p>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="fakt-panel p-5">
                    <div
                        class="mb-3 grid size-10 place-items-center rounded-xl bg-primary/10 text-primary"
                    >
                        <ExternalLink class="size-5" />
                    </div>
                    <h2 class="font-bold">Külső naptár</h2>
                    <p class="mt-2 text-sm leading-5 text-muted-foreground">
                        Iratkozz fel a privát hivatkozással Google, Apple vagy
                        Outlook naptárból.
                    </p>
                    <div v-if="calendarUrl" class="mt-4 flex gap-2">
                        <Button
                            variant="outline"
                            class="min-w-0 flex-1"
                            @click="copy(calendarUrl)"
                            ><Copy class="size-4" />Másolás</Button
                        ><Form action="/naptar/token" method="post"
                            ><Button
                                type="submit"
                                variant="ghost"
                                size="icon"
                                aria-label="Token cseréje"
                                ><RotateCw class="size-4" /></Button
                        ></Form>
                    </div>
                    <Form
                        v-else
                        action="/naptar/token"
                        method="post"
                        class="mt-4"
                        ><Button type="submit" class="w-full"
                            >Privát hivatkozás készítése</Button
                        ></Form
                    >
                    <p class="mt-3 text-xs leading-4 text-muted-foreground">
                        A token cseréje az előző előfizetést azonnal
                        érvényteleníti.
                    </p>
                </div>
                <div class="rounded-2xl bg-primary p-5 text-primary-foreground">
                    <p class="text-sm font-semibold">Jelmagyarázat</p>
                    <div class="mt-4 grid gap-3 text-sm">
                        <p>
                            <span
                                class="mr-2 inline-block size-2 rounded-full bg-white"
                            />Teljes szervezeti esemény
                        </p>
                        <p>
                            <span
                                class="mr-2 inline-block size-2 rounded-full bg-amber-300"
                            />Kötelező részvétel
                        </p>
                        <p>
                            <span
                                class="mr-2 inline-block size-2 rounded-full bg-blue-300"
                            />Team / projekt esemény
                        </p>
                    </div>
                </div>
            </aside>
        </section>
    </div>
</template>
