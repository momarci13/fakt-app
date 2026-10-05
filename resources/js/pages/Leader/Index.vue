<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import FaktPageHeader from '@/components/FaktPageHeader.vue';

type Member = {
    id: number;
    name: string;
    open_tasks: number;
    overdue_tasks: number;
    attendance_rate: number | null;
    missing_rsvps: number;
};
defineProps<{
    groups: Array<{
        key: string;
        name: string;
        kind: string;
        color?: string | null;
        members: Member[];
    }>;
}>();
const rateClass = (rate: number | null) =>
    rate === null
        ? 'text-muted-foreground'
        : rate >= 80
          ? 'text-emerald-700 dark:text-emerald-300'
          : rate >= 60
            ? 'text-amber-700 dark:text-amber-300'
            : 'text-red-700 dark:text-red-300';
</script>

<template>
    <Head title="Vezetői áttekintés" />
    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <FaktPageHeader
            eyebrow="Vezetői nézet"
            title="Vezetői áttekintés"
            description="A Teamjeid és projektjeid tagjai: nyitott és lejárt feladatok, jelenléti arány a kötelező eseményeken, és hány kötelező eseményre nem jeleztek vissza a következő két hétben."
        />
        <section
            v-for="group in groups"
            :key="group.key"
            class="fakt-panel overflow-hidden"
        >
            <div class="flex items-center gap-3 border-b p-5">
                <span
                    class="size-3 rounded-full"
                    :style="{ background: group.color ?? 'var(--primary)' }"
                />
                <h2 class="font-bold">{{ group.name }}</h2>
                <span class="text-xs text-muted-foreground"
                    >{{ group.kind }} · {{ group.members.length }} fő</span
                >
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted text-left text-xs">
                        <tr>
                            <th class="px-5 py-2 font-semibold">Tag</th>
                            <th class="px-3 py-2 text-right font-semibold">
                                Nyitott feladat
                            </th>
                            <th class="px-3 py-2 text-right font-semibold">
                                Lejárt
                            </th>
                            <th class="px-3 py-2 text-right font-semibold">
                                Jelenlét
                            </th>
                            <th class="px-5 py-2 text-right font-semibold">
                                Hiányzó visszajelzés
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="member in group.members" :key="member.id">
                            <td class="px-5 py-2.5 font-medium">
                                {{ member.name }}
                            </td>
                            <td class="px-3 py-2.5 text-right">
                                {{ member.open_tasks }}
                            </td>
                            <td
                                class="px-3 py-2.5 text-right"
                                :class="
                                    member.overdue_tasks > 0 &&
                                    'font-semibold text-red-700 dark:text-red-300'
                                "
                            >
                                {{ member.overdue_tasks }}
                            </td>
                            <td
                                class="px-3 py-2.5 text-right font-semibold"
                                :class="rateClass(member.attendance_rate)"
                            >
                                {{
                                    member.attendance_rate === null
                                        ? '–'
                                        : `${member.attendance_rate}%`
                                }}
                            </td>
                            <td class="px-5 py-2.5 text-right">
                                {{ member.missing_rsvps }}
                            </td>
                        </tr>
                        <tr v-if="!group.members.length">
                            <td
                                colspan="5"
                                class="p-6 text-center text-muted-foreground"
                            >
                                Még nincs tag.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <p
            v-if="!groups.length"
            class="fakt-panel p-10 text-center text-sm text-muted-foreground"
        >
            Nincs olyan Team vagy projekt, amelyet vezetsz.
        </p>
    </div>
</template>
