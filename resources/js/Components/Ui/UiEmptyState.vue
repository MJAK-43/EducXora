<script setup lang="ts">
import { Inbox } from '@lucide/vue';

defineProps<{ title: string; message: string }>();
</script>

<template>
    <div class="ui-empty">
        <span class="ui-empty__icon"
            ><Inbox
                :size="22"
                aria-hidden="true"
        /></span>
        <h3 class="ui-empty__title">{{ title }}</h3>
        <p class="ui-empty__message">{{ message }}</p>
        <div
            v-if="$slots.action"
            style="margin-top: var(--space-5)"
        >
            <slot name="action" />
        </div>
    </div>
</template>
