<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    edit,
    index,
} from '@/actions/App/Http/Controllers/FeatureRequestController';
import StatusBadge from '@/components/StatusBadge.vue';
import StatusChanger from '@/components/StatusChanger.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import VoteButton from '@/components/VoteButton.vue';
import { formatDate } from '@/lib/formatDate';
import type { FeatureRequest, Product, SelectOption } from '@/types';

defineProps<{
    product: Product;
    featureRequest: FeatureRequest;
    allowedStatuses: SelectOption[];
}>();
</script>

<template>
    <Head :title="featureRequest.title" />

    <div class="max-w-3xl space-y-6 px-4 py-6">
        <TextLink :href="index(product.slug)" class="text-sm">
            Alle Wünsche für {{ product.name }}
        </TextLink>

        <div class="flex gap-4">
            <VoteButton :feature-request="featureRequest" />

            <div class="min-w-0 flex-1 space-y-2">
                <h1 class="text-2xl font-semibold">
                    {{ featureRequest.title }}
                </h1>
                <div class="flex flex-wrap items-center gap-2">
                    <StatusBadge :status="featureRequest.status" />
                    <p class="text-sm text-muted-foreground">
                        {{ featureRequest.authorName ?? 'Gelöschter Account' }}
                        ·
                        {{ formatDate(featureRequest.createdAt) }}
                    </p>
                </div>
            </div>
        </div>

        <p class="whitespace-pre-line">{{ featureRequest.description }}</p>

        <StatusChanger
            :feature-request-id="featureRequest.id"
            :allowed-statuses="allowedStatuses"
        />

        <Button v-if="featureRequest.can.update" variant="outline" as-child>
            <Link
                :href="
                    edit({
                        product: product.slug,
                        featureRequest: featureRequest.id,
                    })
                "
            >
                Bearbeiten
            </Link>
        </Button>
    </div>
</template>
