<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    create,
    index,
} from '@/actions/App/Http/Controllers/FeatureRequestController';
import FeatureRequestCard from '@/components/FeatureRequestCard.vue';
import PaginationNav from '@/components/PaginationNav.vue';
import SelectField from '@/components/SelectField.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { home, login } from '@/routes';
import type {
    FeatureRequest,
    FeatureRequestFilters,
    Paginated,
    Product,
    SelectOption,
} from '@/types';

const props = defineProps<{
    product: Product;
    featureRequests: Paginated<FeatureRequest>;
    filters: FeatureRequestFilters;
    statusOptions: SelectOption[];
    sortOptions: SelectOption[];
}>();

const page = usePage();
const status = ref(props.filters.status);
const sort = ref(props.filters.sort);

watch([status, sort], () => {
    router.get(
        index.url(props.product.slug, {
            query: { status: status.value ?? undefined, sort: sort.value },
        }),
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
});
</script>

<template>
    <Head :title="product.name" />

    <div class="space-y-6 px-4 py-6">
        <TextLink :href="home()" class="text-sm">Alle Produkte</TextLink>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold">{{ product.name }}</h1>
                <p class="text-muted-foreground">{{ product.description }}</p>
            </div>

            <Button v-if="page.props.auth.user" as-child>
                <Link :href="create(product.slug)">Wunsch einreichen</Link>
            </Button>
            <Button v-else variant="outline" as-child>
                <Link :href="login()">Zum Einreichen anmelden</Link>
            </Button>
        </div>

        <form class="flex flex-wrap gap-4" @submit.prevent>
            <SelectField
                id="status"
                v-model="status"
                label="Status"
                :options="statusOptions"
                empty-label="Alle"
            />
            <SelectField
                id="sort"
                v-model="sort"
                label="Sortierung"
                :options="sortOptions"
            />
        </form>

        <p class="text-sm text-muted-foreground" role="status">
            {{
                featureRequests.meta.total === 1
                    ? '1 Wunsch'
                    : `${featureRequests.meta.total} Wünsche`
            }}
        </p>

        <ul v-if="featureRequests.data.length > 0" class="space-y-4">
            <li
                v-for="featureRequest in featureRequests.data"
                :key="featureRequest.id"
            >
                <FeatureRequestCard
                    :product="product"
                    :feature-request="featureRequest"
                />
            </li>
        </ul>
        <p v-else class="rounded-lg border border-dashed p-8 text-center">
            Keine Wünsche gefunden.
        </p>

        <PaginationNav :pagination="featureRequests" />
    </div>
</template>
