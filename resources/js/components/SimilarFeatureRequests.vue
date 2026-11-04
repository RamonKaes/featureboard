<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { ref } from 'vue';
import { show } from '@/actions/App/Http/Controllers/FeatureRequestController';
import StatusBadge from '@/components/StatusBadge.vue';
import VoteButton from '@/components/VoteButton.vue';
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

function search(): void {
    const title = props.title.trim();

    http.cancel();

    if (title.length < MIN_LENGTH) {
        results.value = [];

        return;
    }

    http.title = title;
    void http.get(similar.url(props.product.slug), {
        onSuccess: (response) => (results.value = response),
    });
}

watchDebounced(() => props.title.trim(), search, { debounce: DEBOUNCE_MS });
</script>

<template>
    <div aria-live="polite">
        <section
            v-if="results.length > 0"
            class="space-y-2 rounded-md border border-border bg-muted/50 p-3"
        >
            <h2 class="text-sm font-medium">
                {{
                    except
                        ? 'Ähnliche Wünsche:'
                        : 'Gibt es deinen Wunsch schon? Dann stimme stattdessen ab:'
                }}
            </h2>
            <ul class="space-y-2">
                <li
                    v-for="result in results"
                    :key="result.id"
                    class="flex items-center gap-3 text-sm"
                >
                    <!-- The results are not page props, so ask again once the vote is through. -->
                    <VoteButton :feature-request="result" @settled="search" />
                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        <a
                            :href="
                                show.url({
                                    product: product.slug,
                                    featureRequest: result.id,
                                })
                            "
                            target="_blank"
                            rel="noopener"
                            class="wrap-anywhere underline underline-offset-4"
                        >
                            {{ result.title }}
                            <span class="sr-only">(öffnet in neuem Tab)</span>
                        </a>
                        <StatusBadge :status="result.status" />
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>
