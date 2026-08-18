<script setup lang="ts">
import { computed } from 'vue';

import UiSpinner from './UiSpinner.vue';

type ButtonVariant = 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger';
type ButtonSize = 'sm' | 'md' | 'lg';

const props = withDefaults(
    defineProps<{
        variant?: ButtonVariant;
        size?: ButtonSize;
        type?: 'button' | 'submit' | 'reset';
        disabled?: boolean;
        loading?: boolean;
        block?: boolean;
    }>(),
    {
        variant: 'primary',
        size: 'md',
        type: 'button',
        disabled: false,
        loading: false,
        block: false,
    },
);

const emit = defineEmits<{ click: [event: MouseEvent] }>();
const isDisabled = computed(() => props.disabled || props.loading);

function handleClick(event: MouseEvent): void {
    if (!isDisabled.value) {
        emit('click', event);
    }
}
</script>

<template>
    <button
        :type="type"
        class="ui-button"
        :class="[`ui-button--${variant}`, `ui-button--${size}`, { 'ui-button--block': block }]"
        :disabled="isDisabled"
        :aria-busy="loading || undefined"
        @click="handleClick"
    >
        <UiSpinner
            v-if="loading"
            class="ui-button__spinner"
            label="Traitement en cours"
        />
        <slot name="icon" />
        <slot />
    </button>
</template>
