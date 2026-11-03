<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { nextTick } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineProps<{
    action: { action: string; method: 'get' | 'post' };
    cancelHref: NonNullable<InertiaLinkProps['href']>;
    submitLabel: string;
    title?: string;
    description?: string;
}>();

async function focusFirstError(): Promise<void> {
    await nextTick();
    document.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus();
}
</script>

<template>
    <Form
        v-bind="action"
        v-slot="{ errors, processing }"
        class="space-y-6"
        @error="focusFirstError"
    >
        <div class="grid gap-2">
            <Label for="title">Titel</Label>
            <Input
                id="title"
                name="title"
                :default-value="title"
                aria-required="true"
                aria-describedby="title-hint title-error"
                :aria-invalid="errors.title ? true : undefined"
            />
            <p id="title-hint" class="text-xs text-muted-foreground">
                5 bis 120 Zeichen
            </p>
            <InputError id="title-error" :message="errors.title" />
        </div>

        <div class="grid gap-2">
            <Label for="description">Beschreibung</Label>
            <textarea
                id="description"
                name="description"
                rows="6"
                :value="description"
                aria-required="true"
                aria-describedby="description-hint description-error"
                :aria-invalid="errors.description ? true : undefined"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive md:text-sm dark:bg-input/30"
            />
            <p id="description-hint" class="text-xs text-muted-foreground">
                10 bis 5000 Zeichen
            </p>
            <InputError id="description-error" :message="errors.description" />
        </div>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">
                <Spinner v-if="processing" />
                {{ submitLabel }}
            </Button>
            <Button variant="ghost" as-child>
                <Link :href="cancelHref">Abbrechen</Link>
            </Button>
        </div>
    </Form>
</template>
