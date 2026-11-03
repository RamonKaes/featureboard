<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import { show } from '@/actions/App/Http/Controllers/FeatureRequestController';
import StatusBadge from '@/components/StatusBadge.vue';
import { similar } from '@/routes/feature-requests';
import type { Product, SimilarFeatureRequest } from '@/types';

const props = defineProps<{
    product: Product;
    title: string;
    except?: number;
}>();

const MIN_LENGTH = 3;
const DEBOUNCE_MS = 300;

const results = ref<SimilarFeatureRequest[]>([]);
const http = useHttp<
    { title: string; except: number | null },
    SimilarFeatureRequest[]
>({
    title: '',
    except: props.except ?? null,
});

watchDebounced(
    () => props.title.trim(),
    (title) => {
        http.cancel();

        if (title.length < MIN_LENGTH) {
            results.value = [];

            return;
        }

        http.title = title;
        void http.get(similar.url(props.product.slug), {
            onSuccess: (response) => (results.value = response),
        });
    },
    { debounce: DEBOUNCE_MS },
);
</script>

<template>
    <div aria-live="polite">
        <section
            v-if="results.length > 0"
            class="space-y-2 rounded-md border border-border bg-muted/50 p-3"
        >
            <h2 class="text-sm font-medium">
                Ähnliche Wünsche, vielleicht gibt es deinen schon:
            </h2>
            <ul class="space-y-1">
                <li
                    v-for="result in results"
                    :key="result.id"
                    class="flex flex-wrap items-center gap-2 text-sm"
                >
                    <a
                        :href="
                            show.url({
                                product: product.slug,
                                featureRequest: result.id,
                            })
                        "
                        target="_blank"
                        rel="noopener"
                        class="underline underline-offset-4"
                    >
                        {{ result.title }}
                        <span class="sr-only">(öffnet in neuem Tab)</span>
                    </a>
                    <StatusBadge :status="result.status" />
                    <span class="text-xs text-muted-foreground">
                        {{
                            result.votesCount === 1
                                ? '1 Stimme'
                                : `${result.votesCount} Stimmen`
                        }}
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
