<script setup lang="ts">
import { useId } from 'vue';

defineProps<{ text: string }>();
const tooltipId = `tooltip-${useId()}`;
</script>

<template>
    <span class="ui-tooltip">
        <span :aria-describedby="tooltipId"><slot /></span>
        <span
            :id="tooltipId"
            class="ui-tooltip__content"
            role="tooltip"
            >{{ text }}</span
        >
    </span>
</template>
