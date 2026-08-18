<script setup lang="ts">
withDefaults(
    defineProps<{
        modelValue?: boolean;
        label: string;
        help?: string;
        disabled?: boolean;
    }>(),
    { modelValue: false, disabled: false },
);

const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>();
</script>

<template>
    <button
        type="button"
        class="ui-switch"
        role="switch"
        :aria-checked="modelValue"
        :disabled="disabled"
        @click="emit('update:modelValue', !modelValue)"
    >
        <span
            class="ui-switch__track"
            aria-hidden="true"
            ><span class="ui-switch__thumb"
        /></span>
        <span class="ui-switch__content">
            <span class="ui-choice__label">{{ label }}</span>
            <span
                v-if="help"
                class="ui-choice__help"
                >{{ help }}</span
            >
        </span>
    </button>
</template>
