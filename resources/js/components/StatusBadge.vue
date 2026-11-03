<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { FeatureRequestStatus } from '@/types';

defineProps<{
    status: FeatureRequestStatus;
}>();

const colors: Record<FeatureRequestStatus['value'], string> = {
    new: 'bg-sky-100 text-sky-900 dark:bg-sky-950 dark:text-sky-100',
    planned:
        'bg-violet-100 text-violet-900 dark:bg-violet-950 dark:text-violet-100',
    in_progress:
        'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-100',
    done: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100',
    declined:
        'bg-neutral-200 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100',
};
</script>

<template>
    <Badge variant="secondary" :class="colors[status.value]">
        {{ status.label }}
    </Badge>
</template>
