<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { focusFirstError } from '@/lib/focusFirstError';
import { edit } from '@/routes/security';

// oxfmt-ignore
type Props = {
    passwordRules: string;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Sicherheitseinstellungen',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Sicherheitseinstellungen" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Passwort ändern"
            description="Ein langes, zufälliges Passwort schützt dein Konto am besten"
        />

        <Form
            v-bind="SecurityController.update.form()"
            :options="{
                preserveScroll: true,
            }"
            reset-on-success
            :reset-on-error="[
                'password',
                'password_confirmation',
                'current_password',
            ]"
            class="space-y-6"
            v-slot="{ errors, processing }"
            @error="focusFirstError"
        >
            <div class="grid gap-2">
                <Label for="current_password">Aktuelles Passwort</Label>
                <PasswordInput
                    id="current_password"
                    :aria-invalid="errors.current_password ? true : undefined"
                    aria-describedby="current_password-error"
                    name="current_password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    placeholder="Aktuelles Passwort"
                />
                <InputError
                    id="current_password-error"
                    :message="errors.current_password"
                />
            </div>

            <div class="grid gap-2">
                <Label for="password">Neues Passwort</Label>
                <PasswordInput
                    id="password"
                    :aria-invalid="errors.password ? true : undefined"
                    aria-describedby="password-error"
                    name="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Neues Passwort"
                    :passwordrules="props.passwordRules"
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
                    name="password_confirmation"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                    placeholder="Passwort bestätigen"
                    :passwordrules="props.passwordRules"
                />
                <InputError
                    id="password_confirmation-error"
                    :message="errors.password_confirmation"
                />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-password-button"
                >
                    Speichern
                </Button>
            </div>
        </Form>
    </div>
</template>
