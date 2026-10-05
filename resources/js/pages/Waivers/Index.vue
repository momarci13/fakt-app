<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { HandHeart } from '@lucide/vue';
import FaktPageHeader from '@/components/FaktPageHeader.vue';
import StatusPill from '@/components/StatusPill.vue';
import { Button } from '@/components/ui/button';

type Voter = { id: number; name: string; decision?: string | null };
type Waiver = {
    id: number;
    status: string;
    reason: string;
    created_at: string;
    obligation_rule_code?: string | null;
    user?: { name: string };
    requester?: { name: string };
    course?: { title: string } | null;
    can_vote: boolean;
    my_vote?: string | null;
    voters: Voter[];
};
defineProps<{
    waivers: Waiver[];
    isElnokseg: boolean;
    courses: Array<{ id: number; title: string }>;
    rules: Array<{ code: string; name: string }>;
    members: Array<{ id: number; name: string }>;
}>();
const date = (value: string) =>
    new Intl.DateTimeFormat('hu-HU', {
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
</script>

<template>
    <Head title="Felmentések" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <FaktPageHeader
            eyebrow="Elnökségi döntés"
            title="Felmentések"
            description="Egy kötelezettség (például egy kurzus megengedett hiányzásai) alól csak a teljes Elnökség egyhangú döntése ad felmentést. Az érintett nem szavaz a saját ügyében."
        />

        <section class="grid gap-6 xl:grid-cols-[1fr_24rem]">
            <div class="fakt-panel overflow-hidden">
                <div class="border-b p-5">
                    <h2 class="font-bold">
                        {{ isElnokseg ? 'Kérelmek' : 'Saját kérelmeim' }}
                    </h2>
                </div>
                <div class="divide-y">
                    <article
                        v-for="waiver in waivers"
                        :key="waiver.id"
                        class="grid gap-3 p-5"
                    >
                        <div class="flex flex-wrap items-start gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold">
                                    {{ waiver.user?.name }} ·
                                    {{
                                        waiver.course?.title ??
                                        rules.find(
                                            (rule) =>
                                                rule.code ===
                                                waiver.obligation_rule_code,
                                        )?.name ??
                                        waiver.obligation_rule_code
                                    }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Kérte: {{ waiver.requester?.name }},
                                    {{ date(waiver.created_at) }}
                                </p>
                            </div>
                            <StatusPill :value="waiver.status" />
                        </div>
                        <p class="text-sm leading-6 whitespace-pre-line">
                            {{ waiver.reason }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="voter in waiver.voters"
                                :key="voter.id"
                                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs"
                                >{{ voter.name
                                }}<StatusPill
                                    :value="voter.decision ?? 'pending'"
                            /></span>
                        </div>
                        <Form
                            v-if="waiver.can_vote"
                            :action="`/felmentesek/${waiver.id}/szavazat`"
                            method="post"
                            class="flex flex-wrap gap-2"
                            ><input
                                name="note"
                                class="fakt-input min-w-0 flex-1"
                                placeholder="Megjegyzés (nem kötelező)"
                            /><Button
                                name="decision"
                                value="approve"
                                size="sm"
                                :variant="
                                    waiver.my_vote === 'approve'
                                        ? 'default'
                                        : 'outline'
                                "
                                >Megadom</Button
                            ><Button
                                name="decision"
                                value="reject"
                                size="sm"
                                variant="ghost"
                                >Nem adom meg</Button
                            ></Form
                        >
                    </article>
                    <p
                        v-if="!waivers.length"
                        class="p-10 text-center text-sm text-muted-foreground"
                    >
                        Nincs felmentési kérelem.
                    </p>
                </div>
            </div>

            <aside class="fakt-panel h-fit p-5">
                <div
                    class="mb-3 grid size-10 place-items-center rounded-xl bg-primary/10 text-primary"
                >
                    <HandHeart class="size-5" />
                </div>
                <h2 class="font-bold">Felmentés kérése</h2>
                <Form
                    action="/felmentesek"
                    method="post"
                    class="mt-4 grid gap-3"
                    v-slot="{ errors, processing }"
                >
                    <select
                        v-if="isElnokseg"
                        name="user_id"
                        class="fakt-input"
                        aria-label="Érintett tag"
                    >
                        <option value="">Saját magam</option>
                        <option
                            v-for="member in members"
                            :key="member.id"
                            :value="member.id"
                        >
                            {{ member.name }}
                        </option>
                    </select>
                    <select
                        name="course_offering_id"
                        class="fakt-input"
                        aria-label="Kurzus"
                    >
                        <option value="">Kurzus (hiányzási korlát)…</option>
                        <option
                            v-for="course in courses"
                            :key="course.id"
                            :value="course.id"
                        >
                            {{ course.title }}
                        </option>
                    </select>
                    <select
                        v-if="rules.length"
                        name="obligation_rule_code"
                        class="fakt-input"
                        aria-label="Kötelezettség"
                    >
                        <option value="">…vagy életút-kötelezettség</option>
                        <option
                            v-for="rule in rules"
                            :key="rule.code"
                            :value="rule.code"
                        >
                            {{ rule.name }}
                        </option>
                    </select>
                    <textarea
                        name="reason"
                        class="fakt-textarea"
                        placeholder="Indoklás (legalább 10 karakter)"
                        required
                    />
                    <p
                        v-for="(message, field) in errors"
                        :key="field"
                        class="text-xs text-destructive"
                    >
                        {{ message }}
                    </p>
                    <Button type="submit" :disabled="processing"
                        >Elnökség elé terjesztés</Button
                    >
                </Form>
            </aside>
        </section>
    </div>
</template>
