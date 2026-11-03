<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    index,
    store,
} from '@/actions/App/Http/Controllers/FeatureRequestController';
import FeatureRequestForm from '@/components/FeatureRequestForm.vue';
import type { Product } from '@/types';

defineProps<{
    product: Product;
}>();
</script>

<template>
    <Head title="Wunsch einreichen" />

    <div class="max-w-3xl space-y-6 px-4 py-6">
        <h1 class="text-2xl font-semibold">
            Wunsch für {{ product.name }} einreichen
        </h1>

        <FeatureRequestForm
            :product="product"
            :action="store.form(product.slug)"
            :cancel-href="index(product.slug)"
            submit-label="Wunsch einreichen"
        />
    </div>
</template>
