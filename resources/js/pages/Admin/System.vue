<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, Mail, ServerCog, XCircle } from '@lucide/vue';
import { computed } from 'vue';
import FaktPageHeader from '@/components/FaktPageHeader.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    checks: {
        php: string;
        laravel: string;
        environment: string;
        debug: boolean;
        timezone: string;
        config_cached: boolean;
        routes_cached: boolean;
        database: boolean;
        pending_migrations: string[];
        storage_writable: boolean;
        scheduler_last_tick: string | null;
        scheduler_ok: boolean;
        queue_jobs: number;
        queue_oldest_minutes: number | null;
        failed_jobs: number;
        mail: { mailer: string; host?: string; port?: number; from?: string };
    };
    failedJobs: Array<{
        id: number;
        queue: string;
        failed_at: string;
        error: string;
    }>;
    logErrors: string[];
}>();

const rows = computed(() => {
    const c = props.checks;

    return [
        { label: 'PHP verzió', value: c.php, ok: c.php >= '8.3' },
        { label: 'Laravel', value: c.laravel, ok: true },
        {
            label: 'Környezet',
            value: c.environment,
            ok: c.environment === 'production',
        },
        { label: 'Debug mód', value: c.debug ? 'BE' : 'ki', ok: !c.debug },
        {
            label: 'Időzóna',
            value: c.timezone,
            ok: c.timezone === 'Europe/Budapest',
        },
        {
            label: 'Config cache',
            value: c.config_cached ? 'igen' : 'nem',
            ok: c.config_cached,
        },
        {
            label: 'Adatbázis',
            value: c.database ? 'elérhető' : 'HIBA',
            ok: c.database,
        },
        {
            label: 'Függő migrációk',
            value: c.pending_migrations.length
                ? c.pending_migrations.join(', ')
                : 'nincs',
            ok: !c.pending_migrations.length,
        },
        {
            label: 'Storage írható',
            value: c.storage_writable ? 'igen' : 'NEM',
            ok: c.storage_writable,
        },
        {
            label: 'Ütemező (cron)',
            value: c.scheduler_last_tick
                ? `utolsó futás: ${new Date(c.scheduler_last_tick).toLocaleString('hu-HU')}`
                : 'még nem futott',
            ok: c.scheduler_ok,
        },
        {
            label: 'Várakozó feladat (queue)',
            value: `${c.queue_jobs}${c.queue_oldest_minutes !== null ? `, legrégebbi ${c.queue_oldest_minutes} perce` : ''}`,
            ok: c.queue_oldest_minutes === null || c.queue_oldest_minutes < 5,
        },
        {
            label: 'Sikertelen feladat',
            value: String(c.failed_jobs),
            ok: c.failed_jobs === 0,
        },
        {
            label: 'Levélküldés',
            value: `${c.mail.mailer} ${c.mail.host ?? ''}:${c.mail.port ?? ''} · feladó: ${c.mail.from ?? '–'}`,
            ok: c.mail.mailer === 'smtp',
        },
    ];
});
</script>

<template>
    <Head title="Rendszerállapot" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <FaktPageHeader
            eyebrow="Adminisztráció"
            title="Rendszerállapot"
            description="A szerver legfontosabb ellenőrzései ideiglenes cron és naplófájl nélkül. Jelszó vagy titok soha nem jelenik meg."
        >
            <template #actions
                ><Form action="/admin/rendszer/tesztlevel" method="post"
                    ><Button type="submit" variant="outline"
                        ><Mail class="size-4" />Tesztlevél nekem</Button
                    ></Form
                ><Button variant="outline" as-child
                    ><Link href="/admin">Vissza az adminhoz</Link></Button
                ></template
            >
        </FaktPageHeader>
        <section class="fakt-panel overflow-hidden">
            <div class="flex items-center gap-2 border-b p-5">
                <ServerCog class="size-5 text-primary" />
                <h2 class="font-bold">Ellenőrzések</h2>
            </div>
            <div class="divide-y">
                <div
                    v-for="row in rows"
                    :key="row.label"
                    class="flex items-center gap-3 px-5 py-3 text-sm"
                >
                    <CheckCircle2
                        v-if="row.ok"
                        class="size-4 shrink-0 text-emerald-600"
                    /><XCircle v-else class="size-4 shrink-0 text-red-600" />
                    <span class="w-48 shrink-0 font-medium">{{
                        row.label
                    }}</span
                    ><span class="min-w-0 break-words text-muted-foreground">{{
                        row.value
                    }}</span>
                </div>
            </div>
        </section>
        <section v-if="failedJobs.length" class="fakt-panel overflow-hidden">
            <h2 class="border-b p-5 font-bold">Utolsó sikertelen feladatok</h2>
            <div class="divide-y">
                <p
                    v-for="job in failedJobs"
                    :key="job.id"
                    class="px-5 py-3 font-mono text-xs break-words"
                >
                    {{ job.failed_at }} · {{ job.error }}
                </p>
            </div>
        </section>
        <section class="fakt-panel overflow-hidden">
            <h2 class="border-b p-5 font-bold">Utolsó hibák a naplóban</h2>
            <div class="divide-y">
                <p
                    v-for="(line, index) in logErrors"
                    :key="index"
                    class="px-5 py-3 font-mono text-xs break-words"
                >
                    {{ line }}
                </p>
                <p
                    v-if="!logErrors.length"
                    class="p-8 text-center text-sm text-muted-foreground"
                >
                    Nincs friss hiba a naplóban.
                </p>
            </div>
        </section>
    </div>
</template>
