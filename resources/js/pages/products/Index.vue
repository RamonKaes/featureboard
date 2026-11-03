<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { index } from '@/actions/App/Http/Controllers/FeatureRequestController';
import type { Product } from '@/types';

defineProps<{
    products: Product[];
}>();
</script>

<template>
    <Head title="Produkte" />

    <div class="space-y-6 px-4 py-6">
        <div class="space-y-1">
            <h1 class="text-2xl font-semibold">Produkte</h1>
            <p class="text-muted-foreground">
                Wähle ein Produkt, um Wünsche zu sehen und abzustimmen.
            </p>
        </div>

        <ul class="grid gap-4 md:grid-cols-3">
            <li v-for="product in products" :key="product.slug">
                <Link
                    :href="index(product.slug)"
                    class="block h-full space-y-2 rounded-lg border border-border p-4 transition-colors hover:bg-accent"
                >
                    <h2 class="font-semibold">{{ product.name }}</h2>
                    <p class="text-sm text-muted-foreground">
                        {{ product.description }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{
                            product.featureRequestsCount === 1
                                ? '1 Wunsch'
                                : `${product.featureRequestsCount} Wünsche`
                        }}
                    </p>
                </Link>
            </li>
        </ul>
    </div>
</template>
