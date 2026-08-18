<script setup lang="ts">
import { computed, useId } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: boolean;
        id?: string;
        label: string;
        help?: string;
        disabled?: boolean;
        required?: boolean;
    }>(),
    { modelValue: false, disabled: false, required: false },
);

const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>();
const generatedId = useId();
const controlId = computed(() => props.id ?? `checkbox-${generatedId}`);
</script>

<template>
    <label
        class="ui-choice"
        :for="controlId"
    >
        <input
            :id="controlId"
            class="ui-choice__input"
            type="checkbox"
            :checked="modelValue"
            :disabled="disabled"
            :required="required"
            @change="emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
        />
        <span class="ui-choice__label">
            {{ label }}
            <span
                v-if="help"
                class="ui-choice__help"
                >{{ help }}</span
            >
        </span>
    </label>
</template>
