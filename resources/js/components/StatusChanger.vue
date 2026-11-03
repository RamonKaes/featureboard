<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import FeatureRequestStatusController from '@/actions/App/Http/Controllers/FeatureRequestStatusController';
import { Button } from '@/components/ui/button';
import type { SelectOption } from '@/types';

const props = defineProps<{
    featureRequestId: number;
    allowedStatuses: SelectOption[];
}>();

const isSubmitting = ref(false);

// The pressed button disappears, so keyboard users would otherwise lose their place.
async function focusStatusArea(): Promise<void> {
    await nextTick();
    const target =
        document.getElementById('status-changer-heading') ??
        document.querySelector('h1');

    target?.focus();
}

function changeStatus(status: string): void {
    router.visit(FeatureRequestStatusController(props.featureRequestId), {
        data: { status },
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => void focusStatusArea(),
        onStart: () => (isSubmitting.value = true),
        onFinish: () => (isSubmitting.value = false),
    });
}
</script>

<template>
    <section
        v-if="allowedStatuses.length > 0"
        aria-labelledby="status-changer-heading"
        class="space-y-2 rounded-md border border-border p-4"
    >
        <h2
            id="status-changer-heading"
            tabindex="-1"
            class="text-sm font-medium outline-none"
        >
            Status ändern
        </h2>
        <div class="flex flex-wrap gap-2">
            <Button
                v-for="status in allowedStatuses"
                :key="status.value"
                variant="outline"
                :disabled="isSubmitting"
                @click="changeStatus(status.value)"
            >
                {{ status.label }}
            </Button>
        </div>
    </section>
</template>
