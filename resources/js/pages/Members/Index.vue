<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import FaktPageHeader from '@/components/FaktPageHeader.vue';
import StatusPill from '@/components/StatusPill.vue';

type Member = {
    id: number;
    name: string;
    email: string;
    status?: string | null;
    cohort_year?: number | null;
    expertise?: string | null;
    mentor: boolean;
    team?: { name: string; color: string } | null;
    roles: string[];
};
const props = defineProps<{ members: Member[] }>();
const query = ref('');
const normalise = (value: string) =>
    value
        .toLocaleLowerCase('hu')
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '');
const visible = computed(() => {
    const needle = normalise(query.value.trim());

    if (!needle) {
        return props.members;
    }

    return props.members.filter((member) =>
        normalise(
            [
                member.name,
                member.email,
                member.team?.name,
                member.expertise,
                ...member.roles,
            ]
                .filter(Boolean)
                .join(' '),
        ).includes(needle),
    );
});
</script>

<template>
    <Head title="Tagnévsor" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <FaktPageHeader
            eyebrow="Közösség"
            title="Tagnévsor"
            description="Ki melyik Teamben dolgozik, milyen tisztséget visel és miben jártas. Az alumni tagok csak hozzájárulásukkal jelennek meg."
        >
            <template #actions
                ><label class="relative block w-full sm:w-72"
                    ><Search
                        class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" /><input
                        v-model="query"
                        class="fakt-input pl-9"
                        placeholder="Keresés név, Team, szakterület…"
                        aria-label="Keresés" /></label
            ></template>
        </FaktPageHeader>
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="member in visible"
                :key="member.id"
                class="fakt-panel p-5"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="truncate font-bold">{{ member.name }}</h2>
                        <a
                            :href="`mailto:${member.email}`"
                            class="block truncate text-sm text-primary"
                            >{{ member.email }}</a
                        >
                    </div>
                    <StatusPill v-if="member.status" :value="member.status" />
                </div>
                <p
                    v-if="member.team"
                    class="mt-3 flex items-center gap-2 text-sm"
                >
                    <span
                        class="size-2.5 rounded-full"
                        :style="{ background: member.team.color }"
                    />{{ member.team.name }}
                </p>
                <div
                    v-if="member.roles.length"
                    class="mt-3 flex flex-wrap gap-1.5"
                >
                    <span
                        v-for="role in member.roles"
                        :key="role"
                        class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary"
                        >{{ role }}</span
                    >
                </div>
                <p
                    v-if="member.expertise || member.cohort_year"
                    class="mt-3 text-xs text-muted-foreground"
                >
                    <template v-if="member.cohort_year"
                        >{{ member.cohort_year }}-es évfolyam</template
                    ><template v-if="member.cohort_year && member.expertise">
                        · </template
                    >{{ member.expertise }}
                    <template v-if="member.mentor"> · mentor</template>
                </p>
            </article>
        </section>
        <p
            v-if="!visible.length"
            class="fakt-panel p-10 text-center text-sm text-muted-foreground"
        >
            Nincs találat.
        </p>
    </div>
</template>
