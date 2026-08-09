<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    FileUp,
    GraduationCap,
    ShieldCheck,
} from '@lucide/vue';
import FaktPageHeader from '@/components/FaktPageHeader.vue';
import StatusPill from '@/components/StatusPill.vue';
import { Button } from '@/components/ui/button';

type Progress = {
    code: string;
    name: string;
    description?: string;
    current: number;
    threshold: number;
    percent: number;
    complete: boolean;
    kind: string;
};
type RequestItem = {
    id: number;
    type: string;
    reason: string;
    status: string;
    created_at: string;
    decision_note?: string;
    evidence_path?: string;
};
type RecordItem = {
    id: number;
    type: string;
    value: number;
    status: string;
    note?: string;
    created_at: string;
};
defineProps<{
    progress: Progress[];
    records: RecordItem[];
    requests: RequestItem[];
    memberStatus?: string;
}>();
const label = (type: string) =>
    ({
        passivation: 'Passziválás',
        senior: 'Szenior státusz',
        diploma: 'FAKT Diploma',
        exception: 'Méltányosság',
    })[type] ?? type;
const date = (value: string) =>
    new Intl.DateTimeFormat('hu-HU', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(value));
</script>

<template>
    <Head title="Tagi életút" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <FaktPageHeader
            eyebrow="Személyes teljesítés"
            title="FAKT életút és kérelmek"
            description="A rendszer számol és jelez; minden státuszváltozást arra jogosult vezető hagy jóvá."
        >
            <template #actions
                ><StatusPill :value="memberStatus ?? 'active'"
            /></template>
        </FaktPageHeader>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="item in progress"
                :key="item.code"
                class="fakt-panel p-5"
            >
                <div class="flex items-start justify-between gap-3">
                    <div
                        class="grid size-10 place-items-center rounded-xl"
                        :class="
                            item.complete
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-amber-100 text-amber-700'
                        "
                    >
                        <component
                            :is="item.complete ? CheckCircle2 : AlertTriangle"
                            class="size-5"
                        />
                    </div>
                    <span class="text-sm font-bold"
                        >{{ item.current }} / {{ item.threshold }}</span
                    >
                </div>
                <h2 class="mt-4 font-bold">{{ item.name }}</h2>
                <p
                    class="mt-1 line-clamp-2 text-sm leading-5 text-muted-foreground"
                >
                    {{ item.description }}
                </p>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full"
                        :class="item.complete ? 'bg-emerald-500' : 'bg-primary'"
                        :style="{ width: `${item.percent}%` }"
                    />
                </div>
                <p
                    class="mt-2 text-xs font-medium"
                    :class="
                        item.complete
                            ? 'text-emerald-700'
                            : 'text-muted-foreground'
                    "
                >
                    {{ item.complete ? 'Teljesítve' : 'Folyamatban' }}
                </p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[.8fr_1.2fr]">
            <div class="fakt-panel p-5">
                <div class="mb-5 flex items-center gap-3">
                    <div
                        class="grid size-10 place-items-center rounded-xl bg-primary/10 text-primary"
                    >
                        <FileUp class="size-5" />
                    </div>
                    <div>
                        <h2 class="font-bold">Új kérelem</h2>
                        <p class="text-sm text-muted-foreground">
                            Indoklással és opcionális bizonyítékkal
                        </p>
                    </div>
                </div>
                <Form
                    action="/eletut/kerelmek"
                    method="post"
                    enctype="multipart/form-data"
                    class="grid gap-4"
                    v-slot="{ errors, processing }"
                    ><div>
                        <label class="fakt-label">Kérelem típusa</label
                        ><select name="type" class="fakt-input mt-2" required>
                            <option value="passivation">Passziválás</option>
                            <option value="senior">Szenior státusz</option>
                            <option value="diploma">FAKT Diploma</option>
                            <option value="exception">Méltányosság</option>
                        </select>
                    </div>
                    <div>
                        <label class="fakt-label">Indoklás</label
                        ><textarea
                            name="reason"
                            class="fakt-textarea mt-2"
                            required
                            placeholder="Írd le a kérelem szakmai vagy személyes indokát…"
                        />
                        <p
                            v-if="errors.reason"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ errors.reason }}
                        </p>
                    </div>
                    <div>
                        <label class="fakt-label">Bizonyíték · max. 10 MB</label
                        ><input
                            type="file"
                            name="evidence"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                            class="mt-2 block w-full text-sm"
                        />
                    </div>
                    <Button type="submit" :disabled="processing"
                        >Kérelem benyújtása</Button
                    ></Form
                >
            </div>

            <div class="fakt-panel overflow-hidden">
                <div class="border-b p-5">
                    <div class="flex items-center gap-3">
                        <ShieldCheck class="size-5 text-primary" />
                        <div>
                            <h2 class="font-bold">Kérelmeim és döntések</h2>
                            <p class="text-sm text-muted-foreground">
                                Teljes, visszakövethető előzmény
                            </p>
                        </div>
                    </div>
                </div>
                <div class="divide-y">
                    <article
                        v-for="request in requests"
                        :key="request.id"
                        class="p-5"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-bold">
                                    {{ label(request.type) }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ date(request.created_at) }}
                                </p>
                            </div>
                            <StatusPill :value="request.status" />
                        </div>
                        <p class="mt-3 text-sm leading-5 text-muted-foreground">
                            {{ request.reason }}
                        </p>
                        <a
                            v-if="request.evidence_path"
                            :href="`/eletut/kerelmek/${request.id}/bizonyitek`"
                            class="mt-3 inline-block text-sm font-semibold text-primary"
                            >Bizonyíték letöltése</a
                        >
                        <p
                            v-if="request.decision_note"
                            class="mt-3 rounded-lg bg-muted p-3 text-sm"
                        >
                            <strong>Döntés indoka:</strong>
                            {{ request.decision_note }}
                        </p>
                    </article>
                    <p
                        v-if="!requests.length"
                        class="p-10 text-center text-sm text-muted-foreground"
                    >
                        Még nincs benyújtott kérelmed.
                    </p>
                </div>
            </div>
        </section>

        <section class="fakt-panel overflow-hidden">
            <div class="border-b p-5">
                <div class="flex items-center gap-3">
                    <GraduationCap class="size-5 text-primary" />
                    <h2 class="font-bold">Jóváhagyott teljesítési rekordok</h2>
                </div>
            </div>
            <div class="divide-y">
                <div
                    v-for="record in records"
                    :key="record.id"
                    class="flex items-center gap-4 p-4"
                >
                    <CheckCircle2 class="size-5 text-emerald-600" />
                    <div class="min-w-0 flex-1">
                        <p class="font-medium">{{ record.type }}</p>
                        <p class="truncate text-sm text-muted-foreground">
                            {{ record.note || date(record.created_at) }}
                        </p>
                    </div>
                    <span class="font-bold">+{{ record.value }}</span>
                </div>
            </div>
        </section>
    </div>
</template>
