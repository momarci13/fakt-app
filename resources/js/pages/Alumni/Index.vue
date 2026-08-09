<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { GraduationCap, Handshake, Mail, Sparkles } from '@lucide/vue';
import FaktPageHeader from '@/components/FaktPageHeader.vue';
import StatusPill from '@/components/StatusPill.vue';
import { Button } from '@/components/ui/button';

type Alumni = {
    id: number;
    name: string;
    email: string;
    profile: {
        expertise?: string;
        bio?: string;
        cohort_year?: number;
        mentor_available: boolean;
    };
};
type Mentorship = {
    id: number;
    status: string;
    focus?: string;
    mentor: { name: string };
    mentee: { name: string };
};
defineProps<{ alumni: Alumni[]; mentorships: Mentorship[] }>();
</script>

<template>
    <Head title="Alumni és mentorok" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <FaktPageHeader
            eyebrow="Hosszú távú közösség"
            title="Alumni címtár és mentorprogram"
            description="Kapcsolódj azokhoz, akik már végigjárták a FAKT-os utat, és tapasztalatukkal segítenek."
        />

        <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="person in alumni"
                :key="person.id"
                class="fakt-panel p-5"
            >
                <div class="flex items-start justify-between">
                    <div
                        class="grid size-12 place-items-center rounded-2xl bg-primary text-lg font-black text-primary-foreground"
                    >
                        {{
                            person.name
                                .split(' ')
                                .map((word) => word[0])
                                .join('')
                                .slice(0, 2)
                        }}
                    </div>
                    <span
                        v-if="person.profile.mentor_available"
                        class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800"
                        >Mentorál</span
                    >
                </div>
                <h2 class="mt-4 text-lg font-bold">{{ person.name }}</h2>
                <p class="text-sm text-muted-foreground">
                    {{ person.profile.cohort_year }}-es évfolyam
                </p>
                <p
                    class="mt-4 flex items-center gap-2 text-sm font-semibold text-primary"
                >
                    <Sparkles class="size-4" />{{
                        person.profile.expertise || 'Általános mentorálás'
                    }}
                </p>
                <p
                    class="mt-3 line-clamp-3 text-sm leading-6 text-muted-foreground"
                >
                    {{ person.profile.bio }}
                </p>
                <a
                    :href="`mailto:${person.email}`"
                    class="mt-4 inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-primary"
                    ><Mail class="size-4" />{{ person.email }}</a
                ><Form
                    v-if="person.profile.mentor_available"
                    action="/alumni/mentor"
                    method="post"
                    class="mt-5 grid gap-2"
                    ><input
                        type="hidden"
                        name="mentor_id"
                        :value="person.id"
                    /><input
                        name="focus"
                        class="fakt-input"
                        placeholder="Miben kérsz segítséget?"
                        required
                    /><Button type="submit" variant="outline" class="w-full"
                        ><Handshake class="size-4" />Mentorálási kérés</Button
                    ></Form
                >
            </article>
        </section>

        <section class="fakt-panel overflow-hidden">
            <div class="border-b p-5">
                <div class="flex items-center gap-3">
                    <GraduationCap class="size-5 text-primary" />
                    <div>
                        <h2 class="font-bold">
                            Saját mentorálási kapcsolataim
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Aktív és függőben lévő párosítások
                        </p>
                    </div>
                </div>
            </div>
            <div class="divide-y">
                <div
                    v-for="item in mentorships"
                    :key="item.id"
                    class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center"
                >
                    <div class="flex-1">
                        <p class="font-semibold">
                            {{ item.mentor.name }} ↔ {{ item.mentee.name }}
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ item.focus }}
                        </p>
                    </div>
                    <StatusPill :value="item.status" />
                </div>
                <p
                    v-if="!mentorships.length"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    Még nincs mentorálási kapcsolatod.
                </p>
            </div>
        </section>
    </div>
</template>
