<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div>
        <p
            v-if="message"
            role="alert"
            class="text-sm text-red-600 dark:text-red-500"
        >
            {{ message }}
        </p>
    </div>
</template>
