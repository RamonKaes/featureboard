<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';
import { focusFirstError } from '@/lib/focusFirstError';

defineOptions({
    layout: {
        title: 'Passwort vergessen',
        description:
            'Gib deine E-Mail-Adresse ein, um einen Link zum Zurücksetzen zu erhalten',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Passwort vergessen" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-700 dark:text-green-500"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form
            v-bind="email.form()"
            v-slot="{ errors, processing }"
            @error="focusFirstError"
        >
            <div class="grid gap-2">
                <Label for="email">E-Mail-Adresse</Label>
                <Input
                    id="email"
                    :aria-invalid="errors.email ? true : undefined"
                    aria-describedby="email-error"
                    type="email"
                    name="email"
                    autocomplete="off"
                    v-focus
                    placeholder="email@example.com"
                />
                <InputError id="email-error" :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="w-full"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    Link zum Zurücksetzen senden
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>Oder zurück zur</span>
            <TextLink :href="login()">Anmeldung</TextLink>
        </div>
    </div>
</template>
