<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { CheckCircle2, Clock3, ShieldCheck } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'FAKT hozzáférés igénylése',
        description:
            'Hozd létre a fiókodat; a belépést az aktuális Elnök hagyja jóvá.',
    },
});
</script>

<template>
    <Head title="Regisztráció" />

    <div class="mb-6 grid grid-cols-3 gap-2 text-center text-[11px]">
        <div class="rounded-xl bg-primary/10 p-2 text-primary">
            <CheckCircle2 class="mx-auto mb-1 size-4" />Regisztráció
        </div>
        <div class="rounded-xl bg-amber-500/10 p-2 text-amber-700">
            <Clock3 class="mx-auto mb-1 size-4" />Elnöki döntés
        </div>
        <div class="rounded-xl bg-muted p-2 text-muted-foreground">
            <ShieldCheck class="mx-auto mb-1 size-4" />Belépés
        </div>
    </div>

    <Form
        action="/register"
        method="post"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="grid gap-4"
    >
        <div class="grid gap-2">
            <Label for="name">Teljes név</Label>
            <Input
                id="name"
                name="name"
                autocomplete="name"
                autofocus
                required
                placeholder="Vezetéknév Keresztnév"
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2 sm:grid-cols-[1fr_8rem]">
            <div class="grid gap-2">
                <Label for="email">Email cím</Label>
                <Input
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    required
                    placeholder="nev@email.hu"
                />
                <InputError :message="errors.email" />
            </div>
            <div class="grid gap-2">
                <Label for="cohort_year">Évfolyam</Label>
                <Input
                    id="cohort_year"
                    name="cohort_year"
                    type="number"
                    min="2008"
                    max="2100"
                    placeholder="2026"
                />
                <InputError :message="errors.cohort_year" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="registration_note">Bemutatkozás az Elnöknek</Label>
            <textarea
                id="registration_note"
                name="registration_note"
                class="fakt-textarea min-h-24"
                minlength="20"
                maxlength="2000"
                required
                placeholder="Írd le röviden, hogy melyik évfolyamhoz vagy FAKT-közösséghez tartozol."
            />
            <InputError :message="errors.registration_note" />
        </div>

        <div class="grid gap-2 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="password">Jelszó</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    minlength="12"
                    placeholder="Legalább 12 karakter"
                />
                <InputError :message="errors.password" />
            </div>
            <div class="grid gap-2">
                <Label for="password_confirmation">Jelszó újra</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    minlength="12"
                    placeholder="Ismételd meg"
                />
            </div>
        </div>

        <label
            class="flex items-start gap-3 rounded-xl border p-3 text-xs leading-5"
        >
            <input
                type="checkbox"
                name="privacy_accepted"
                value="1"
                required
                class="mt-1"
            />
            <span>
                Tudomásul veszem, hogy a megadott adatokat a hozzáférés
                elbírálásához a FAKT Elnöke kezeli.
            </span>
        </label>
        <InputError :message="errors.privacy_accepted" />

        <Button type="submit" class="mt-1 w-full" :disabled="processing">
            <Spinner v-if="processing" />Hozzáférés igénylése
        </Button>

        <p class="text-center text-sm text-muted-foreground">
            Már van jóváhagyott fiókod?
            <TextLink href="/login">Belépés</TextLink>
        </p>
    </Form>
</template>
