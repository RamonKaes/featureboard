<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    show,
    update,
} from '@/actions/App/Http/Controllers/FeatureRequestController';
import FeatureRequestForm from '@/components/FeatureRequestForm.vue';
import type { FeatureRequest, Product } from '@/types';

const props = defineProps<{
    product: Product;
    featureRequest: FeatureRequest;
}>();

const route = {
    product: props.product.slug,
    featureRequest: props.featureRequest.id,
};
</script>

<template>
    <Head title="Wunsch bearbeiten" />

    <div class="max-w-3xl space-y-6 px-4 py-6">
        <h1 class="text-2xl font-semibold">Wunsch bearbeiten</h1>

        <FeatureRequestForm
            :product="product"
            :feature-request-id="featureRequest.id"
            :action="update.form(route)"
            :cancel-href="show(route)"
            :title="featureRequest.title"
            :description="featureRequest.description"
            submit-label="Speichern"
        />
    </div>
</template>
