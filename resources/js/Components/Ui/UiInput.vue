<script setup lang="ts">
import { computed, useId } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue?: string | number;
        id?: string;
        label: string;
        type?: 'text' | 'email' | 'tel' | 'password' | 'number' | 'date' | 'time';
        required?: boolean;
        help?: string;
        error?: string;
        disabled?: boolean;
        readonly?: boolean;
        placeholder?: string;
        autocomplete?: string;
    }>(),
    {
        modelValue: '',
        type: 'text',
        required: false,
        disabled: false,
        readonly: false,
    },
);

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const generatedId = useId();
const controlId = computed(() => props.id ?? `field-${generatedId}`);
const descriptionId = computed(() =>
    props.error || props.help ? `${controlId.value}-description` : undefined,
);

function updateValue(event: Event): void {
    emit('update:modelValue', (event.target as HTMLInputElement).value);
}
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
        <input
            :id="controlId"
            class="ui-field__control"
            :class="{ 'ui-field__control--error': error }"
            :value="modelValue"
            :type="type"
            :required="required"
            :disabled="disabled"
            :readonly="readonly"
            :placeholder="placeholder"
            :autocomplete="autocomplete"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="descriptionId"
            @input="updateValue"
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
