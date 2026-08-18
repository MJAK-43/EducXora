<script setup lang="ts">
import { computed, useId } from 'vue';

export interface SelectOption {
    value: string;
    label: string;
    disabled?: boolean;
}

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        id?: string;
        label: string;
        options: SelectOption[];
        required?: boolean;
        help?: string;
        error?: string;
        disabled?: boolean;
        placeholder?: string;
    }>(),
    { modelValue: '', required: false, disabled: false },
);

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const generatedId = useId();
const controlId = computed(() => props.id ?? `select-${generatedId}`);
const descriptionId = computed(() =>
    props.error || props.help ? `${controlId.value}-description` : undefined,
);
</script>

<template>
    <div class="ui-field">
        <label
            class="ui-field__label"
            :for="controlId"
        >
            {{ label }}
            <span
                v-if="required"
                class="ui-field__required"
                aria-hidden="true"
                >*</span
            >
        </label>
        <select
            :id="controlId"
            class="ui-field__control"
            :class="{ 'ui-field__control--error': error }"
            :value="modelValue"
            :required="required"
            :disabled="disabled"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="descriptionId"
            @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        >
            <option
                v-if="placeholder"
                value=""
                disabled
            >
                {{ placeholder }}
            </option>
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
                :disabled="option.disabled"
            >
                {{ option.label }}
            </option>
        </select>
        <p
            v-if="error"
            :id="descriptionId"
            class="ui-field__error"
        >
            {{ error }}
        </p>
        <p
            v-else-if="help"
            :id="descriptionId"
            class="ui-field__help"
        >
            {{ help }}
        </p>
    </div>
</template>
