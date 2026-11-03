<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
    >
        <path fill="currentColor" d="M12 3 3 13h5.5v8h7v-8H21Z" />
    </svg>
</template>
