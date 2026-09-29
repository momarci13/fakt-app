<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Crown, UserRoundPlus, X } from '@lucide/vue';
import { computed } from 'vue';
import FaktPageHeader from '@/components/FaktPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Person = { id: number; name: string; email: string };
type Role = { id: number; role: string; user: Person };
type Membership = { id: number; user: Person };
type Unit = {
    id: number;
    parent_id?: number;
    type: string;
    name: string;
    slug: string;
    color: string;
    roles: Role[];
    memberships: Membership[];
};
type Project = {
    id: number;
    name: string;
    description?: string;
    status: string;
    ends_at?: string | null;
    lead: Person;
    members: Person[];
    org_unit?: { name: string };
};

const props = defineProps<{
    semester: { name: string } | null;
    units: Unit[];
    projects: Project[];
    members: Person[];
    canAdmin: boolean;
    managedUnitIds: number[];
    managedProjectIds: number[];
}>();
const portfolios = computed(() =>
    props.units.filter((unit) => unit.type === 'portfolio'),
);
const teamsFor = (portfolioId: number) =>
    props.units.filter((unit) => unit.parent_id === portfolioId);
const canManage = (unitId: number) =>
    props.canAdmin || props.managedUnitIds.includes(unitId);
// Server-side AccessScope::managesProject is the real guard; this only hides controls.
const canManageProject = (projectId: number) =>
    props.managedProjectIds.includes(projectId);
// The leader is shown separately and cannot be removed as a member.
const membersOf = (project: Project) =>
    project.members.filter((member) => member.id !== project.lead.id);
const candidatesFor = (project: Project) =>
    props.members.filter(
        (member) => !project.members.some((m) => m.id === member.id),
    );
const projectStatus = (status: string) =>
    ({ active: 'Aktív', closed: 'Lezárt', archived: 'Archivált' })[status] ??
    status;
// A Y-m-d calendar date, read as a local date so no viewer's timezone shifts it.
const day = (value?: string | null) => {
    if (!value) {
        return '';
    }

    const [year, month, date] = value.slice(0, 10).split('-').map(Number);

    return new Intl.DateTimeFormat('hu-HU', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(year, month - 1, date));
};
const roleLabel = (role: string) =>
    ({
        president: 'Elnök',
        vice_president: 'Alelnök',
        team_leader: 'Teamvezető',
    })[role] ?? role;
</script>

<template>
    <Head title="Szervezet" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <FaktPageHeader
            eyebrow="Szervezeti térkép"
            title="Portfóliók, Teamek és projektek"
            :description="`${semester?.name ?? ''} hatályos, közvetlen kinevezései és Team-tagságai.`"
        />

        <section
            v-for="portfolio in portfolios"
            :key="portfolio.id"
            class="fakt-panel overflow-hidden"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-4 border-b p-5"
                :style="{ borderLeft: `5px solid ${portfolio.color}` }"
            >
                <div>
                    <p class="fakt-label">Alelnöki portfólió</p>
                    <h2 class="mt-1 text-lg font-bold">{{ portfolio.name }}</h2>
                </div>
                <div
                    v-for="role in portfolio.roles"
                    :key="role.id"
                    class="flex items-center gap-3 rounded-xl bg-muted px-4 py-3"
                >
                    <Crown class="size-5 text-primary" />
                    <div>
                        <p class="text-xs text-muted-foreground">
                            {{ roleLabel(role.role) }}
                        </p>
                        <p class="font-semibold">{{ role.user.name }}</p>
                    </div>
                </div>
            </div>
            <div class="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="team in teamsFor(portfolio.id)"
                    :key="team.id"
                    class="rounded-xl border bg-background p-4"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <span
                            class="size-3 rounded-full"
                            :style="{ background: team.color }"
                        />
                        <h3 class="font-bold">{{ team.name }}</h3>
                    </div>
                    <div
                        v-if="team.roles.length"
                        class="mb-4 rounded-lg bg-muted/70 p-3"
                    >
                        <p class="text-xs text-muted-foreground">Teamvezető</p>
                        <p class="mt-1 font-semibold">
                            {{ team.roles[0].user.name }}
                        </p>
                    </div>
                    <p
                        class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Teamtagok · {{ team.memberships.length }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="membership in team.memberships"
                            :key="membership.id"
                            class="rounded-full border px-2.5 py-1 text-xs"
                            >{{ membership.user.name }}</span
                        ><span
                            v-if="!team.memberships.length"
                            class="text-sm text-muted-foreground"
                            >Még nincs kijelölt tag.</span
                        >
                    </div>

                    <Form
                        v-if="canManage(team.id)"
                        action="/szervezet/team-tagsag"
                        method="post"
                        class="mt-4 flex gap-2"
                        v-slot="{ processing }"
                    >
                        <input
                            type="hidden"
                            name="org_unit_id"
                            :value="team.id"
                        />
                        <select
                            name="user_id"
                            class="fakt-input min-w-0"
                            required
                        >
                            <option value="">Tag kijelölése…</option>
                            <option
                                v-for="member in members"
                                :key="member.id"
                                :value="member.id"
                            >
                                {{ member.name }}
                            </option>
                        </select>
                        <Button
                            type="submit"
                            size="icon"
                            :disabled="processing"
                            aria-label="Tag kijelölése"
                            ><UserRoundPlus class="size-4"
                        /></Button>
                    </Form>
                </article>
            </div>
        </section>

        <section class="fakt-panel overflow-hidden">
            <header class="border-b p-5">
                <p class="fakt-label">SZMSZ 8.4 és 12.5</p>
                <h2 class="mt-1 text-h2">Projektek</h2>
                <p class="mt-1 max-w-2xl text-small text-muted-foreground">
                    A hat Team mellett működnek, nem azok között. A projektet az
                    Elnök hozza létre és nevezi ki a vezetőjét; a projekttagokat
                    a Projektvezető választja.
                </p>
            </header>

            <Form
                v-if="canAdmin"
                action="/szervezet/projektek"
                method="post"
                reset-on-success
                class="grid gap-4 border-b p-5 md:grid-cols-2"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-1">
                    <label for="project-name" class="text-small font-medium"
                        >Projekt neve</label
                    >
                    <input
                        id="project-name"
                        name="name"
                        class="fakt-input"
                        maxlength="150"
                        required
                    />
                    <p v-if="errors.name" class="text-small text-destructive">
                        {{ errors.name }}
                    </p>
                </div>
                <div class="grid gap-1">
                    <label for="project-lead" class="text-small font-medium"
                        >Projektvezető</label
                    >
                    <select
                        id="project-lead"
                        name="lead_user_id"
                        class="fakt-input"
                        required
                    >
                        <option value="">Vezető kiválasztása…</option>
                        <option
                            v-for="member in members"
                            :key="member.id"
                            :value="member.id"
                        >
                            {{ member.name }}
                        </option>
                    </select>
                    <p
                        v-if="errors.lead_user_id"
                        class="text-small text-destructive"
                    >
                        {{ errors.lead_user_id }}
                    </p>
                </div>
                <div class="grid gap-1 md:col-span-2">
                    <label
                        for="project-description"
                        class="text-small font-medium"
                        >Cél és feladat</label
                    >
                    <textarea
                        id="project-description"
                        name="description"
                        class="fakt-textarea"
                        maxlength="3000"
                    />
                </div>
                <div class="grid gap-1">
                    <label for="project-ends" class="text-small font-medium"
                        >Záródátum, legkésőbb a félév vége</label
                    >
                    <input
                        id="project-ends"
                        type="date"
                        name="ends_at"
                        class="fakt-input"
                    />
                    <p
                        v-if="errors.ends_at"
                        class="text-small text-destructive"
                    >
                        {{ errors.ends_at }}
                    </p>
                </div>
                <div class="flex items-end">
                    <Button type="submit" :disabled="processing"
                        >Projekt létrehozása</Button
                    >
                </div>
            </Form>

            <div
                v-if="projects.length"
                class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3"
            >
                <article
                    v-for="project in projects"
                    :key="project.id"
                    class="grid content-start gap-4 rounded-[var(--radius-surface)] border bg-background p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="text-h3">{{ project.name }}</h3>
                        <Badge variant="secondary">{{
                            projectStatus(project.status)
                        }}</Badge>
                    </div>
                    <p
                        v-if="project.description"
                        class="line-clamp-3 text-small text-muted-foreground"
                    >
                        {{ project.description }}
                    </p>
                    <dl class="grid gap-1 text-small">
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">Projektvezető</dt>
                            <dd class="font-medium">{{ project.lead.name }}</dd>
                        </div>
                        <div
                            v-if="project.ends_at"
                            class="flex justify-between gap-3"
                        >
                            <dt class="text-muted-foreground">Záródátum</dt>
                            <dd data-numeric>{{ day(project.ends_at) }}</dd>
                        </div>
                    </dl>

                    <div>
                        <p class="fakt-label mb-2" data-numeric>
                            Projekttagok · {{ membersOf(project).length }}
                        </p>
                        <ul
                            class="divide-y rounded-[var(--radius-control)] border"
                        >
                            <li
                                v-for="member in membersOf(project)"
                                :key="member.id"
                                class="flex items-center justify-between gap-2 px-3 py-1.5"
                            >
                                <span class="truncate text-small">{{
                                    member.name
                                }}</span>
                                <Form
                                    v-if="canManageProject(project.id)"
                                    :action="`/szervezet/projektek/${project.id}/tagok/${member.id}`"
                                    method="delete"
                                    v-slot="{ processing }"
                                >
                                    <Button
                                        type="submit"
                                        variant="ghost"
                                        size="icon-sm"
                                        :aria-label="`${member.name} eltávolítása a projektből`"
                                        :disabled="processing"
                                        ><X class="size-4"
                                    /></Button>
                                </Form>
                            </li>
                            <li
                                v-if="!membersOf(project).length"
                                class="px-3 py-2 text-small text-muted-foreground"
                            >
                                Még nincs projekttag.
                            </li>
                        </ul>
                    </div>

                    <Form
                        v-if="
                            canManageProject(project.id) &&
                            project.status === 'active'
                        "
                        :action="`/szervezet/projektek/${project.id}/tagok`"
                        method="post"
                        reset-on-success
                        class="grid gap-1"
                        v-slot="{ errors, processing }"
                    >
                        <label
                            :for="`project-add-${project.id}`"
                            class="sr-only"
                            >Projekttag hozzáadása</label
                        >
                        <div class="flex gap-2">
                            <select
                                :id="`project-add-${project.id}`"
                                name="user_id"
                                class="fakt-input min-w-0"
                                required
                            >
                                <option value="">Projekttag hozzáadása…</option>
                                <option
                                    v-for="member in candidatesFor(project)"
                                    :key="member.id"
                                    :value="member.id"
                                >
                                    {{ member.name }}
                                </option>
                            </select>
                            <Button
                                type="submit"
                                size="icon"
                                aria-label="Projekttag hozzáadása"
                                :disabled="processing"
                                ><UserRoundPlus class="size-4"
                            /></Button>
                        </div>
                        <p
                            v-if="errors.user_id"
                            class="text-small text-destructive"
                        >
                            {{ errors.user_id }}
                        </p>
                    </Form>
                </article>
            </div>
            <p v-else class="p-8 text-center text-small text-muted-foreground">
                Ebben a félévben még nincs projekt.
                <template v-if="canAdmin"
                    >Az elsőt a fenti űrlappal hozhatod létre.</template
                >
            </p>
        </section>
    </div>
</template>
