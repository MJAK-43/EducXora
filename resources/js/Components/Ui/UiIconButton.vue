<script setup lang="ts">
withDefaults(
    defineProps<{
        label: string;
        type?: 'button' | 'submit' | 'reset';
        disabled?: boolean;
        pressed?: boolean;
    }>(),
    { type: 'button', disabled: false },
);

defineEmits<{ click: [event: MouseEvent] }>();
</script>

<template>
    <button
        class="ui-icon-button"
        :type="type"
        :disabled="disabled"
        :aria-label="label"
        :aria-pressed="pressed"
        @click="$emit('click', $event)"
    >
        <slot />
    </button>
</template>
