<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronUp } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import {
    destroy,
    store,
} from '@/actions/App/Http/Controllers/FeatureRequestVoteController';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import type { FeatureRequest, Paginated } from '@/types';

const props = defineProps<{
    featureRequest: FeatureRequest;
}>();

const page = usePage();
const isSubmitting = ref(false);

const canToggle = computed(
    () => props.featureRequest.can.vote || props.featureRequest.hasVoted,
);
const showLogin = computed(
    () => !page.props.auth.user && props.featureRequest.acceptsVotes,
);
const countLabel = computed(() =>
    props.featureRequest.votesCount === 1
        ? '1 Stimme'
        : `${props.featureRequest.votesCount} Stimmen`,
);

const classes =
    'flex min-h-14 w-14 shrink-0 flex-col items-center justify-center rounded-md border text-sm font-semibold';

function withToggledVote(featureRequest: FeatureRequest): FeatureRequest {
    if (featureRequest.id !== props.featureRequest.id) {
        return featureRequest;
    }

    return {
        ...featureRequest,
        hasVoted: !featureRequest.hasVoted,
        votesCount:
            featureRequest.votesCount + (featureRequest.hasVoted ? -1 : 1),
    };
}

function showFailure(): boolean {
    toast.error('Deine Stimme konnte nicht gespeichert werden.');

    return false;
}

function toggleVote(): void {
    if (isSubmitting.value) {
        return;
    }

    const action = props.featureRequest.hasVoted ? destroy : store;

    router.visit(action(props.featureRequest.id), {
        preserveScroll: true,
        // Inertia restores the previous props by itself when the visit fails.
        optimistic: ({ featureRequest, featureRequests }) => {
            const list = featureRequests as
                | Paginated<FeatureRequest>
                | undefined;

            return {
                ...(featureRequest
                    ? {
                          featureRequest: withToggledVote(
                              featureRequest as FeatureRequest,
                          ),
                      }
                    : {}),
                ...(list
                    ? {
                          featureRequests: {
                              ...list,
                              data: list.data.map(withToggledVote),
                          },
                      }
                    : {}),
            };
        },
        onStart: () => (isSubmitting.value = true),
        onFinish: () => (isSubmitting.value = false),
        onHttpException: showFailure,
        onNetworkError: showFailure,
    });
}
</script>

<template>
    <button
        v-if="canToggle"
        type="button"
        :aria-pressed="featureRequest.hasVoted"
        :aria-busy="isSubmitting"
        :aria-label="`Für „${featureRequest.title}“ abstimmen, ${countLabel}`"
        :class="
            cn(
                classes,
                'cursor-pointer transition-colors hover:bg-accent focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                featureRequest.hasVoted &&
                    'border-primary bg-primary text-primary-foreground hover:bg-primary/90',
            )
        "
        @click="toggleVote"
    >
        <ChevronUp class="size-4" aria-hidden="true" />
        {{ featureRequest.votesCount }}
    </button>

    <Link
        v-else-if="showLogin"
        :href="login()"
        :aria-label="`Zum Abstimmen anmelden, ${countLabel}`"
        :class="cn(classes, 'hover:bg-accent')"
    >
        <ChevronUp class="size-4" aria-hidden="true" />
        {{ featureRequest.votesCount }}
    </Link>

    <div v-else :class="cn(classes, 'bg-muted text-muted-foreground')">
        <span aria-hidden="true">{{ featureRequest.votesCount }}</span>
        <span class="sr-only">{{ countLabel }}</span>
    </div>
</template>
