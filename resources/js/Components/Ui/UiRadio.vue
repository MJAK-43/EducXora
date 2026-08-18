<script setup lang="ts">
import { computed, useId } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        value: string;
        name: string;
        id?: string;
        label: string;
        disabled?: boolean;
    }>(),
    { modelValue: '', disabled: false },
);

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const generatedId = useId();
const controlId = computed(() => props.id ?? `radio-${generatedId}`);
</script>

<template>
    <label
        class="ui-choice"
        :for="controlId"
    >
        <input
            :id="controlId"
            class="ui-choice__input"
            type="radio"
            :name="name"
            :value="value"
            :checked="modelValue === value"
            :disabled="disabled"
            @change="emit('update:modelValue', value)"
        />
        <span class="ui-choice__label">{{ label }}</span>
    </label>
</template>
