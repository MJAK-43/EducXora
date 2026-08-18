<script setup lang="ts">
withDefaults(
    defineProps<{
        label?: string;
    }>(),
    { label: 'Chargement en cours' },
);
</script>

<template>
    <svg
        class="ui-spinner"
        viewBox="0 0 24 24"
        fill="none"
        role="status"
        :aria-label="label"
    >
        <circle
            cx="12"
            cy="12"
            r="9"
            stroke="currentColor"
            stroke-width="3"
            opacity=".25"
        />
        <path
            d="M21 12a9 9 0 0 0-9-9"
            stroke="currentColor"
            stroke-width="3"
            stroke-linecap="round"
        />
    </svg>
</template>
