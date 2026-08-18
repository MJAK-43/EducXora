<script setup lang="ts">
import { ChevronDown } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';

import UiButton from './UiButton.vue';

withDefaults(defineProps<{ label: string }>(), {});
const open = ref(false);
const root = ref<HTMLElement>();

function close(): void {
    open.value = false;
}

function handleDocumentClick(event: MouseEvent): void {
    if (root.value && !root.value.contains(event.target as Node)) close();
}

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') close();
}

onMounted(() => document.addEventListener('click', handleDocumentClick));
onBeforeUnmount(() => document.removeEventListener('click', handleDocumentClick));
</script>

<template>
    <div
        ref="root"
        class="ui-dropdown"
        @keydown="handleKeydown"
    >
        <UiButton
            variant="outline"
            :aria-expanded="open"
            aria-haspopup="menu"
            @click="open = !open"
        >
            {{ label }}
            <ChevronDown
                :size="16"
                style="margin-left: var(--space-2)"
                aria-hidden="true"
            />
        </UiButton>
        <div
            v-if="open"
            class="ui-dropdown__menu"
            role="menu"
            @click="close"
        >
            <slot />
        </div>
    </div>
</template>
