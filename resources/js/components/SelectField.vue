<script setup lang="ts">
import { Label } from '@/components/ui/label';
import type { SelectOption } from '@/types';

defineProps<{
    id: string;
    label: string;
    options: SelectOption[];
    emptyLabel?: string;
}>();

const model = defineModel<string | null>();
</script>

<template>
    <div class="grid gap-2">
        <Label :for="id">{{ label }}</Label>
        <select
            :id="id"
            v-model="model"
            class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
            <option v-if="emptyLabel" :value="null">{{ emptyLabel }}</option>
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
    </div>
</template>
