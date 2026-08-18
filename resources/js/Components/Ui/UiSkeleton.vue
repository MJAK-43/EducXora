<script setup lang="ts">
withDefaults(defineProps<{ width?: string; height?: string; label?: string }>(), {
    width: '100%',
    height: '1rem',
    label: 'Contenu en cours de chargement',
});
</script>

<template>
    <div
        class="ui-skeleton"
        role="status"
        :aria-label="label"
        :style="{ width, height }"
    />
</template>
