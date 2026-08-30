<script setup lang="ts">
import { ref } from 'vue';
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

const button = ref<HTMLButtonElement | null>(null);

defineExpose({
    focus: (): void => button.value?.focus(),
});
</script>

<template>
    <button
        ref="button"
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
