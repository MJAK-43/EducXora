<script setup lang="ts">
withDefaults(
    defineProps<{
        name: string;
        src?: string;
    }>(),
    { src: undefined },
);

function initials(name: string): string {
    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}
</script>

<template>
    <span
        class="ui-avatar"
        :aria-label="name"
    >
        <img
            v-if="src"
            :src="src"
            alt=""
        />
        <span
            v-else
            aria-hidden="true"
            >{{ initials(name) }}</span
        >
    </span>
</template>
