<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { focusFirstError } from '@/lib/focusFirstError';

defineOptions({
    layout: {
        title: 'Bei deinem Konto anmelden',
        description: 'Gib deine E-Mail-Adresse und dein Passwort ein',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Anmelden" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-700 dark:text-green-500"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        @error="focusFirstError"
        class="flex flex-col gap-6"
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
                    required
                    v-focus
                    autocomplete="email"
                    placeholder="email@example.com"
                />
                <InputError id="email-error" :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Passwort</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                    >
                        Passwort vergessen?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    :aria-invalid="errors.password ? true : undefined"
                    aria-describedby="password-error"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Passwort"
                />
                <InputError id="password-error" :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" />
                    <span>Angemeldet bleiben</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Anmelden
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Noch kein Konto?
            <TextLink :href="register()">Registrieren</TextLink>
        </div>
    </Form>
</template>
