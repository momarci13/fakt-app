<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    Bell,
    BookOpenCheck,
    History,
    Megaphone,
    ShieldCheck,
    Upload,
    UserPlus,
    UsersRound,
} from '@lucide/vue';
import FaktPageHeader from '@/components/FaktPageHeader.vue';
import StatusPill from '@/components/StatusPill.vue';
import { Button } from '@/components/ui/button';

type Rule = {
    id: number;
    code: string;
    name: string;
    kind: string;
    threshold: number;
    version: number;
    published_at?: string;
};
type Audit = {
    id: number;
    event: string;
    auditable_type?: string;
    created_at: string;
    actor?: { name: string };
};
type MemberRequest = {
    id: number;
    type: string;
    reason: string;
    created_at: string;
    user: { name: string; email: string };
};
type ImportBatch = {
    id: number;
    original_name: string;
    status: string;
    total_rows: number;
    valid_rows: number;
    invalid_rows: number;
    created_at: string;
    rows: Array<{
        id: number;
        row_number: number;
        status: string;
        payload: Record<string, string>;
        errors?: string[];
    }>;
};
defineProps<{
    semester: { name: string; rules_published_at?: string } | null;
    semesters: unknown[];
    rules: Rule[];
    audits: Audit[];
    pendingRequests: MemberRequest[];
    importBatches: ImportBatch[];
    stats: { users: number; active: number; alumni: number };
}>();
const date = (value: string) =>
    new Intl.DateTimeFormat('hu-HU', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
</script>

<template>
    <Head title="Adminisztráció" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <FaktPageHeader
            eyebrow="Elnöki rendszeradminisztráció"
            title="Működés és szabályozás"
            description="Meghívók, féléves szabályok, kérelmek és a változásnapló egy védett felületen."
        />

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="fakt-panel p-5">
                <UsersRound class="mb-4 size-5 text-primary" />
                <p class="text-3xl font-bold">{{ stats.users }}</p>
                <p class="text-sm text-muted-foreground">Összes fiók</p>
            </div>
            <div class="fakt-panel p-5">
                <ShieldCheck class="mb-4 size-5 text-emerald-600" />
                <p class="text-3xl font-bold">{{ stats.active }}</p>
                <p class="text-sm text-muted-foreground">
                    Aktív és szenior tag
                </p>
            </div>
            <div class="fakt-panel p-5">
                <BookOpenCheck class="mb-4 size-5 text-blue-600" />
                <p class="text-3xl font-bold">{{ stats.alumni }}</p>
                <p class="text-sm text-muted-foreground">Alumni</p>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="fakt-panel p-5">
                <div class="mb-5 flex items-center gap-3">
                    <UserPlus class="size-5 text-primary" />
                    <div>
                        <h2 class="font-bold">Tag meghívása</h2>
                        <p class="text-sm text-muted-foreground">
                            Meghívásos, publikus regisztráció nélkül
                        </p>
                    </div>
                </div>
                <Form
                    action="/admin/meghivok"
                    method="post"
                    class="grid gap-3"
                    v-slot="{ errors, processing }"
                    ><input
                        name="name"
                        class="fakt-input"
                        placeholder="Teljes név"
                        required
                    /><input
                        type="email"
                        name="email"
                        class="fakt-input"
                        placeholder="Email cím"
                        required
                    />
                    <div class="grid grid-cols-2 gap-2">
                        <select name="member_status" class="fakt-input">
                            <option value="active">Aktív tag</option>
                            <option value="senior">Szenior</option>
                            <option value="alumni">Alumni</option></select
                        ><input
                            type="number"
                            name="cohort_year"
                            class="fakt-input"
                            placeholder="Évfolyam"
                        />
                    </div>
                    <p v-if="errors.email" class="text-xs text-destructive">
                        {{ errors.email }}
                    </p>
                    <Button type="submit" :disabled="processing"
                        >Meghívó küldése</Button
                    ></Form
                >
            </div>

            <div class="fakt-panel p-5">
                <div class="mb-5 flex items-center gap-3">
                    <Megaphone class="size-5 text-primary" />
                    <div>
                        <h2 class="font-bold">Új közlemény</h2>
                        <p class="text-sm text-muted-foreground">
                            Célzott üzenet a kezdőképernyőre
                        </p>
                    </div>
                </div>
                <Form
                    action="/admin/kozlemenyek"
                    method="post"
                    class="grid gap-3"
                    v-slot="{ processing }"
                    ><input
                        name="title"
                        class="fakt-input"
                        placeholder="Cím"
                        required
                    /><textarea
                        name="body"
                        class="fakt-textarea"
                        placeholder="Közlemény szövege"
                        required
                    />
                    <div class="flex gap-2">
                        <select name="audience" class="fakt-input">
                            <option value="members">Tagok</option>
                            <option value="alumni">Alumni</option>
                            <option value="all">Mindenki</option></select
                        ><label
                            class="flex shrink-0 items-center gap-2 rounded-lg border px-3 text-sm"
                            ><input
                                type="checkbox"
                                name="is_pinned"
                                value="1"
                            />Kiemelt</label
                        >
                    </div>
                    <Button type="submit" :disabled="processing"
                        >Publikálás</Button
                    ></Form
                >
            </div>
        </section>

        <section class="fakt-panel overflow-hidden">
            <div
                class="grid gap-6 border-b p-5 lg:grid-cols-[1fr_24rem] lg:items-center"
            >
                <div class="flex items-start gap-3">
                    <Upload class="mt-1 size-5 text-primary" />
                    <div>
                        <h2 class="font-bold">Előnézetes tagimport</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            CSV vagy XLSX első munkalap. Kötelező oszlopok:
                            <code>name</code>, <code>email</code>,
                            <code>member_status</code>; opcionális:
                            <code>cohort_year</code>. Az alkalmazás csak
                            hibamentes előnézet után engedélyezett.
                        </p>
                    </div>
                </div>
                <Form
                    action="/admin/importok"
                    method="post"
                    enctype="multipart/form-data"
                    class="flex gap-2"
                    v-slot="{ processing, errors }"
                    ><div class="min-w-0 flex-1">
                        <input
                            type="file"
                            name="file"
                            accept=".csv,.txt,.xlsx"
                            class="fakt-input w-full"
                            required
                        />
                        <p
                            v-if="errors.file"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ errors.file }}
                        </p>
                    </div>
                    <Button type="submit" :disabled="processing"
                        >Ellenőrzés</Button
                    ></Form
                >
            </div>
            <div class="divide-y">
                <article
                    v-for="batch in importBatches"
                    :key="batch.id"
                    class="p-5"
                >
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold">
                                {{ batch.original_name }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ date(batch.created_at) }} ·
                                {{ batch.total_rows }} sor ·
                                {{ batch.valid_rows }} érvényes ·
                                {{ batch.invalid_rows }} hibás
                            </p>
                        </div>
                        <StatusPill :value="batch.status" /><Form
                            v-if="
                                batch.status === 'staged' &&
                                batch.invalid_rows === 0
                            "
                            :action="`/admin/importok/${batch.id}/alkalmazas`"
                            method="post"
                            ><Button type="submit" size="sm"
                                >Alkalmazás</Button
                            ></Form
                        ><Form
                            v-if="batch.status === 'applied'"
                            :action="`/admin/importok/${batch.id}/visszavonas`"
                            method="post"
                            ><Button type="submit" size="sm" variant="outline"
                                >Visszavonás</Button
                            ></Form
                        >
                    </div>
                    <div
                        v-if="
                            batch.rows.some((row) => row.status === 'invalid')
                        "
                        class="mt-3 space-y-2 rounded-xl bg-destructive/5 p-3"
                    >
                        <div
                            v-for="row in batch.rows.filter(
                                (item) => item.status === 'invalid',
                            )"
                            :key="row.id"
                            class="text-xs"
                        >
                            <strong
                                >{{ row.row_number }}. sor –
                                {{
                                    row.payload.email || 'email nélkül'
                                }}</strong
                            ><span class="text-destructive"
                                >: {{ row.errors?.join(' ') }}</span
                            >
                        </div>
                    </div>
                </article>
                <p
                    v-if="!importBatches.length"
                    class="p-8 text-center text-sm text-muted-foreground"
                >
                    Még nem készült importelőnézet.
                </p>
            </div>
        </section>

        <section class="fakt-panel overflow-hidden">
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b p-5"
            >
                <div>
                    <p class="fakt-label text-primary">{{ semester?.name }}</p>
                    <h2 class="mt-1 font-bold">
                        Verziózott kötelezettségi szabályok
                    </h2>
                </div>
                <Form action="/admin/szabalyok/publikalas" method="post"
                    ><Button type="submit" variant="outline"
                        >Szabályok publikálása</Button
                    ></Form
                >
            </div>
            <div class="grid gap-5 p-5 xl:grid-cols-[1fr_22rem]">
                <div class="divide-y rounded-xl border">
                    <div
                        v-for="rule in rules"
                        :key="rule.id"
                        class="flex items-center gap-4 p-4"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ rule.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ rule.code }} · v{{ rule.version }}
                            </p>
                        </div>
                        <span class="text-sm font-bold"
                            >{{ rule.kind === 'minimum' ? '≥' : '<' }}
                            {{ rule.threshold }}</span
                        ><StatusPill
                            :value="rule.published_at ? 'approved' : 'pending'"
                        />
                    </div>
                </div>
                <Form action="/admin/szabalyok" method="post" class="grid gap-3"
                    ><p class="font-semibold">Új szabályverzió</p>
                    <input
                        name="code"
                        class="fakt-input"
                        placeholder="azonosito_kod"
                        required
                    /><input
                        name="name"
                        class="fakt-input"
                        placeholder="Megjelenített név"
                        required
                    />
                    <div class="grid grid-cols-2 gap-2">
                        <select name="kind" class="fakt-input">
                            <option value="minimum">Minimum</option>
                            <option value="maximum">Maximum</option></select
                        ><input
                            type="number"
                            step="0.1"
                            name="threshold"
                            class="fakt-input"
                            placeholder="Küszöb"
                            required
                        />
                    </div>
                    <textarea
                        name="description"
                        class="fakt-textarea"
                        placeholder="Leírás"
                    /><Button type="submit">Piszkozat mentése</Button></Form
                >
            </div>
        </section>

        <section class="fakt-panel overflow-hidden">
            <div class="border-b p-5">
                <div class="flex items-center gap-3">
                    <Bell class="size-5 text-primary" />
                    <div>
                        <h2 class="font-bold">Elbírálandó tagi kérelmek</h2>
                        <p class="text-sm text-muted-foreground">
                            A döntés indoklása kötelező és naplózott
                        </p>
                    </div>
                </div>
            </div>
            <div class="divide-y">
                <article
                    v-for="request in pendingRequests"
                    :key="request.id"
                    class="grid gap-3 p-5 lg:grid-cols-[1fr_24rem]"
                >
                    <div>
                        <p class="font-bold">
                            {{ request.user.name }} · {{ request.type }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ request.user.email }} ·
                            {{ date(request.created_at) }}
                        </p>
                        <p class="mt-3 text-sm leading-6 text-muted-foreground">
                            {{ request.reason }}
                        </p>
                    </div>
                    <Form
                        :action="`/admin/kerelmek/${request.id}`"
                        method="patch"
                        class="grid gap-2"
                    >
                        <textarea
                            name="decision_note"
                            class="fakt-textarea"
                            placeholder="Döntés indoklása"
                            required
                        />
                        <div class="flex gap-2">
                            <Button
                                name="status"
                                value="approved"
                                type="submit"
                                class="flex-1"
                                >Jóváhagyás</Button
                            ><Button
                                name="status"
                                value="rejected"
                                type="submit"
                                variant="outline"
                                class="flex-1"
                                >Elutasítás</Button
                            >
                        </div></Form
                    >
                </article>
                <p
                    v-if="!pendingRequests.length"
                    class="p-8 text-center text-sm text-muted-foreground"
                >
                    Nincs elbírálandó kérelem.
                </p>
            </div>
        </section>

        <section class="fakt-panel overflow-hidden">
            <div class="border-b p-5">
                <div class="flex items-center gap-3">
                    <History class="size-5 text-primary" />
                    <div>
                        <h2 class="font-bold">Változásnapló</h2>
                        <p class="text-sm text-muted-foreground">
                            Az utolsó 25 érzékeny művelet
                        </p>
                    </div>
                </div>
            </div>
            <div class="divide-y">
                <div
                    v-for="audit in audits"
                    :key="audit.id"
                    class="flex items-center gap-4 p-4"
                >
                    <span class="size-2 rounded-full bg-primary" />
                    <div class="min-w-0 flex-1">
                        <p class="font-medium">{{ audit.event }}</p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ audit.actor?.name || 'Rendszer' }} ·
                            {{ audit.auditable_type }}
                        </p>
                    </div>
                    <time class="text-xs text-muted-foreground">{{
                        date(audit.created_at)
                    }}</time>
                </div>
            </div>
        </section>
    </div>
</template>
