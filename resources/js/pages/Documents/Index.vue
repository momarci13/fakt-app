<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Download, FileText, Upload } from '@lucide/vue';
import FaktPageHeader from '@/components/FaktPageHeader.vue';
import StatusPill from '@/components/StatusPill.vue';
import { Button } from '@/components/ui/button';

type Document = {
    id: number;
    category: string;
    original_name: string;
    mime_type: string;
    size: number;
    visibility: string;
    created_at: string;
    uploader: { name: string };
};
type Event = { id: number; title: string; starts_at: string };
defineProps<{ documents: Document[]; events: Event[]; canUpload: boolean }>();
const date = (value: string) =>
    new Intl.DateTimeFormat('hu-HU', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Europe/Budapest',
    }).format(new Date(value));
const size = (bytes: number) =>
    bytes < 1048576
        ? `${Math.ceil(bytes / 1024)} KB`
        : `${(bytes / 1048576).toFixed(1)} MB`;
const labels: Record<string, string> = {
    guidebook: 'Guidebook',
    minutes: 'Jegyzőkönyv',
    evidence: 'Bizonyíték',
    other: 'Egyéb',
};
</script>

<template>
    <Head title="Dokumentumok" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <FaktPageHeader
            eyebrow="Védett fájltár"
            title="Dokumentumok és Guidebookok"
            description="Jogosultság-ellenőrzött letöltések; a fájlok közvetlenül nem publikusak."
        />

        <section v-if="canUpload" class="fakt-panel p-5">
            <div class="mb-5 flex items-center gap-3">
                <Upload class="size-5 text-primary" />
                <div>
                    <h2 class="font-bold">Dokumentum feltöltése</h2>
                    <p class="text-sm text-muted-foreground">
                        PDF, Office-dokumentum vagy kép, legfeljebb 10 MB
                    </p>
                </div>
            </div>
            <Form
                action="/dokumentumok"
                method="post"
                enctype="multipart/form-data"
                class="grid gap-3 lg:grid-cols-[1fr_12rem_12rem_1fr_auto]"
                v-slot="{ processing, errors }"
                ><input
                    type="file"
                    name="file"
                    class="fakt-input"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                    required
                /><select name="category" class="fakt-input">
                    <option value="guidebook">Guidebook</option>
                    <option value="minutes">Jegyzőkönyv</option>
                    <option value="evidence">Bizonyíték</option>
                    <option value="other">Egyéb</option></select
                ><select name="visibility" class="fakt-input">
                    <option value="members">Tagok</option>
                    <option value="alumni">Alumni</option>
                    <option value="all">
                        Minden belépett felhasználó
                    </option></select
                ><select name="event_id" class="fakt-input">
                    <option value="">Nem eseményhez kötött</option>
                    <option
                        v-for="event in events"
                        :key="event.id"
                        :value="event.id"
                    >
                        {{ event.title }}
                    </option></select
                ><Button type="submit" :disabled="processing">Feltöltés</Button>
                <p
                    v-if="errors.file"
                    class="text-xs text-destructive lg:col-span-full"
                >
                    {{ errors.file }}
                </p></Form
            >
        </section>

        <section class="fakt-panel overflow-hidden">
            <div class="border-b p-5">
                <h2 class="font-bold">Elérhető dokumentumok</h2>
            </div>
            <div class="divide-y">
                <article
                    v-for="document in documents"
                    :key="document.id"
                    class="flex flex-wrap items-center gap-4 p-5"
                >
                    <div
                        class="grid size-11 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary"
                    >
                        <FileText class="size-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">
                            {{ document.original_name }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ labels[document.category] || document.category }}
                            · {{ size(document.size) }} ·
                            {{ document.uploader.name }} ·
                            {{ date(document.created_at) }}
                        </p>
                    </div>
                    <StatusPill :value="document.visibility" /><a
                        :href="`/dokumentumok/${document.id}/letoltes`"
                        class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold hover:bg-muted"
                        ><Download class="size-4" />Letöltés</a
                    >
                </article>
                <p
                    v-if="!documents.length"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    Nincs elérhető dokumentum.
                </p>
            </div>
        </section>
    </div>
</template>
