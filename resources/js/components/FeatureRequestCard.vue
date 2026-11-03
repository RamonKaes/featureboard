<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { show } from '@/actions/App/Http/Controllers/FeatureRequestController';
import StatusBadge from '@/components/StatusBadge.vue';
import VoteButton from '@/components/VoteButton.vue';
import { formatDate } from '@/lib/formatDate';
import type { FeatureRequest, Product } from '@/types';

defineProps<{
    product: Product;
    featureRequest: FeatureRequest;
}>();
</script>

<template>
    <article class="flex gap-4 rounded-lg border border-border p-4">
        <VoteButton :feature-request="featureRequest" />

        <div class="min-w-0 flex-1 space-y-1">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="font-semibold">
                    <Link
                        :href="
                            show({
                                product: product.slug,
                                featureRequest: featureRequest.id,
                            })
                        "
                        class="underline-offset-4 hover:underline"
                    >
                        {{ featureRequest.title }}
                    </Link>
                </h2>
                <StatusBadge :status="featureRequest.status" />
            </div>

            <p class="line-clamp-2 text-sm text-muted-foreground">
                {{ featureRequest.description }}
            </p>

            <p class="text-xs text-muted-foreground">
                {{ featureRequest.authorName ?? 'Gelöschter Account' }} ·
                {{ formatDate(featureRequest.createdAt) }}
            </p>
        </div>
    </article>
</template>
