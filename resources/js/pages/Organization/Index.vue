<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    Crown,
    Network,
    UserRoundPlus,
    UsersRound,
} from '@lucide/vue';
import { computed } from 'vue';
import FaktPageHeader from '@/components/FaktPageHeader.vue';
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
}>();
const portfolios = computed(() =>
    props.units.filter((unit) => unit.type === 'portfolio'),
);
const teamsFor = (portfolioId: number) =>
    props.units.filter((unit) => unit.parent_id === portfolioId);
const canManage = (unitId: number) =>
    props.canAdmin || props.managedUnitIds.includes(unitId);
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

        <section>
            <div class="mb-4 flex items-center gap-3">
                <div
                    class="grid size-10 place-items-center rounded-xl bg-primary/10 text-primary"
                >
                    <BriefcaseBusiness class="size-5" />
                </div>
                <div>
                    <h2 class="text-lg font-bold">Kereszt-Team projektek</h2>
                    <p class="text-sm text-muted-foreground">
                        Több terület együttműködései
                    </p>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="project in projects"
                    :key="project.id"
                    class="fakt-panel p-5"
                >
                    <div class="flex items-start justify-between gap-3">
                        <Network class="size-5 text-primary" /><span
                            class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800"
                            >{{ project.status }}</span
                        >
                    </div>
                    <h3 class="mt-4 font-bold">{{ project.name }}</h3>
                    <p
                        class="mt-2 line-clamp-2 text-sm leading-5 text-muted-foreground"
                    >
                        {{ project.description }}
                    </p>
                    <p class="mt-4 text-xs text-muted-foreground">
                        Projektvezető
                    </p>
                    <p class="font-semibold">{{ project.lead.name }}</p>
                    <div
                        class="mt-3 flex items-center gap-1.5 text-xs text-muted-foreground"
                    >
                        <UsersRound class="size-4" />{{
                            project.members.length
                        }}
                        közreműködő
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>
