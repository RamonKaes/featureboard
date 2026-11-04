<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { focusFirstError } from '@/lib/focusFirstError';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Konto erstellen',
        description: 'Gib deine Daten ein, um ein Konto zu erstellen',
    },
});
</script>

<template>
    <Head title="Registrieren" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        @error="focusFirstError"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    :aria-invalid="errors.name ? true : undefined"
                    aria-describedby="name-error"
                    type="text"
                    required
                    v-focus
                    autocomplete="name"
                    name="name"
                    placeholder="Vor- und Nachname"
                />
                <InputError id="name-error" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">E-Mail-Adresse</Label>
                <Input
                    id="email"
                    :aria-invalid="errors.email ? true : undefined"
                    aria-describedby="email-error"
                    type="email"
                    required
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError id="email-error" :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Passwort</Label>
                <PasswordInput
                    id="password"
                    :aria-invalid="errors.password ? true : undefined"
                    aria-describedby="password-error"
                    required
                    autocomplete="new-password"
                    name="password"
                    placeholder="Passwort"
                    :passwordrules="passwordRules"
                />
                <InputError id="password-error" :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Passwort bestätigen</Label>
                <PasswordInput
                    id="password_confirmation"
                    :aria-invalid="
                        errors.password_confirmation ? true : undefined
                    "
                    aria-describedby="password_confirmation-error"
                    required
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Passwort bestätigen"
                    :passwordrules="passwordRules"
                />
                <InputError
                    id="password_confirmation-error"
                    :message="errors.password_confirmation"
                />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Konto erstellen
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Schon ein Konto?
            <TextLink :href="login()" class="underline underline-offset-4"
                >Anmelden</TextLink
            >
        </div>
    </Form>
</template>
