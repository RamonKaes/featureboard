<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types';

defineProps<{
    pagination: Pick<Paginated<unknown>, 'links' | 'meta'>;
}>();
</script>

<template>
    <nav
        v-if="pagination.meta.last_page > 1"
        aria-label="Seiten"
        class="flex items-center justify-between gap-4"
    >
        <Button v-if="pagination.links.prev" variant="outline" as-child>
            <Link :href="pagination.links.prev" rel="prev">Zurück</Link>
        </Button>
        <span v-else />

        <p class="text-sm text-muted-foreground" aria-current="page">
            Seite {{ pagination.meta.current_page }} von
            {{ pagination.meta.last_page }}
        </p>

        <Button v-if="pagination.links.next" variant="outline" as-child>
            <Link :href="pagination.links.next" rel="next">Weiter</Link>
        </Button>
        <span v-else />
    </nav>
</template>
