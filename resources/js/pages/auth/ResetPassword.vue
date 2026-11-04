<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';
import { focusFirstError } from '@/lib/focusFirstError';

defineOptions({
    layout: {
        title: 'Passwort zurücksetzen',
        description: 'Gib dein neues Passwort ein',
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <Head title="Passwort zurücksetzen" />

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        @error="focusFirstError"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">E-Mail-Adresse</Label>
                <Input
                    id="email"
                    :aria-invalid="errors.email ? true : undefined"
                    aria-describedby="email-error"
                    type="email"
                    name="email"
                    autocomplete="email"
                    v-model="inputEmail"
                    class="mt-1 block w-full"
                    readonly
                />
                <InputError
                    id="email-error"
                    :message="errors.email"
                    class="mt-2"
                />
            </div>

            <div class="grid gap-2">
                <Label for="password">Passwort</Label>
                <PasswordInput
                    id="password"
                    :aria-invalid="errors.password ? true : undefined"
                    aria-describedby="password-error"
                    name="password"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
                    autofocus
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
                    name="password_confirmation"
                    autocomplete="new-password"
                    class="mt-1 block w-full"
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
                class="mt-4 w-full"
                :disabled="processing"
                data-test="reset-password-button"
            >
                <Spinner v-if="processing" />
                Passwort zurücksetzen
            </Button>
        </div>
    </Form>
</template>
