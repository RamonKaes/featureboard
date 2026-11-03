<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    index,
    show,
} from '@/actions/App/Http/Controllers/FeatureRequestController';
import StatusBadge from '@/components/StatusBadge.vue';
import TextLink from '@/components/TextLink.vue';
import type { Product, RoadmapColumn } from '@/types';

defineProps<{
    product: Product;
    columns: RoadmapColumn[];
}>();
</script>

<template>
    <Head :title="`Roadmap für ${product.name}`" />

    <div class="space-y-6 px-4 py-6">
        <TextLink :href="index(product.slug)" class="text-sm">
            Alle Wünsche für {{ product.name }}
        </TextLink>

        <div class="space-y-1">
            <h1 class="text-2xl font-semibold">
                Roadmap für {{ product.name }}
            </h1>
            <p class="text-muted-foreground">
                Woran gerade gearbeitet wird und was schon fertig ist.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <section
                v-for="column in columns"
                :key="column.status.value"
                :aria-labelledby="`roadmap-${column.status.value}`"
                class="space-y-3"
            >
                <h2
                    :id="`roadmap-${column.status.value}`"
                    class="flex items-center gap-2 font-semibold"
                >
                    <StatusBadge :status="column.status" />
                    <span class="text-sm font-normal text-muted-foreground">
                        {{ column.featureRequests.length }}
                    </span>
                </h2>

                <ul v-if="column.featureRequests.length > 0" class="space-y-2">
                    <li
                        v-for="featureRequest in column.featureRequests"
                        :key="featureRequest.id"
                    >
                        <Link
                            :href="
                                show({
                                    product: product.slug,
                                    featureRequest: featureRequest.id,
                                })
                            "
                            class="block space-y-1 rounded-lg border border-border p-3 transition-colors hover:bg-accent"
                        >
                            <span class="block text-sm font-medium">
                                {{ featureRequest.title }}
                            </span>
                            <span class="block text-xs text-muted-foreground">
                                {{
                                    featureRequest.votesCount === 1
                                        ? '1 Stimme'
                                        : `${featureRequest.votesCount} Stimmen`
                                }}
                            </span>
                        </Link>
                    </li>
                </ul>
                <p
                    v-else
                    class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground"
                >
                    Noch keine Wünsche.
                </p>
            </section>
        </div>
    </div>
</template>
