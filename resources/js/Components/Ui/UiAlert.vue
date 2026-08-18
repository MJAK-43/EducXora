<script setup lang="ts">
import { CircleAlert, CircleCheck, Info, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        tone?: 'info' | 'success' | 'warning' | 'danger';
        title: string;
    }>(),
    { tone: 'info' },
);

const icon = computed(
    () =>
        ({ info: Info, success: CircleCheck, warning: TriangleAlert, danger: CircleAlert })[
            props.tone
        ],
);
const role = computed(() =>
    props.tone === 'danger' || props.tone === 'warning' ? 'alert' : 'status',
);
</script>

<template>
    <div
        class="ui-alert"
        :class="`ui-alert--${tone}`"
        :role="role"
    >
        <component
            :is="icon"
            class="ui-alert__icon"
            aria-hidden="true"
        />
        <div class="ui-alert__content">
            <p class="ui-alert__title">{{ title }}</p>
            <p
                v-if="$slots.default"
                class="ui-alert__message"
            >
                <slot />
            </p>
        </div>
    </div>
</template>
