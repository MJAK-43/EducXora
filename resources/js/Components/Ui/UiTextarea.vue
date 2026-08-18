<script setup lang="ts">
import { computed, useId } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: string;
        id?: string;
        label: string;
        required?: boolean;
        help?: string;
        error?: string;
        disabled?: boolean;
        readonly?: boolean;
        placeholder?: string;
        rows?: number;
    }>(),
    { modelValue: '', required: false, disabled: false, readonly: false, rows: 4 },
);

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const generatedId = useId();
const controlId = computed(() => props.id ?? `textarea-${generatedId}`);
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
        <textarea
            :id="controlId"
            class="ui-field__control"
            :class="{ 'ui-field__control--error': error }"
            :value="modelValue"
            :required="required"
            :disabled="disabled"
            :readonly="readonly"
            :placeholder="placeholder"
            :rows="rows"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="descriptionId"
            @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
        />
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
