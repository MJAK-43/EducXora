<script setup lang="ts">
import { X } from '@lucide/vue';
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

import UiIconButton from './UiIconButton.vue';

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        closeLabel?: string;
    }>(),
    { closeLabel: 'Fermer la fenêtre' },
);

const emit = defineEmits<{ close: [] }>();
const dialog = ref<HTMLElement>();
let previouslyFocused: HTMLElement | null = null;

const focusableSelector =
    'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

function focusableElements(): HTMLElement[] {
    return dialog.value
        ? Array.from(dialog.value.querySelectorAll<HTMLElement>(focusableSelector))
        : [];
}

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        emit('close');
        return;
    }

    if (event.key !== 'Tab') return;

    const elements = focusableElements();
    const first = elements[0];
    const last = elements[elements.length - 1];
    if (!first || !last) return;

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

watch(
    () => props.open,
    async (open) => {
        if (open) {
            previouslyFocused = document.activeElement as HTMLElement | null;
            document.body.style.overflow = 'hidden';
            await nextTick();
            (focusableElements()[0] ?? dialog.value)?.focus();
        } else {
            document.body.style.overflow = '';
            previouslyFocused?.focus();
        }
    },
);

onBeforeUnmount(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="ui-modal-backdrop"
            @mousedown.self="emit('close')"
        >
            <section
                ref="dialog"
                class="ui-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="ui-modal-title"
                tabindex="-1"
                @keydown="handleKeydown"
            >
                <header class="ui-modal__header">
                    <h2
                        id="ui-modal-title"
                        class="ui-modal__title"
                    >
                        {{ title }}
                    </h2>
                    <UiIconButton
                        :label="closeLabel"
                        @click="emit('close')"
                    >
                        <X
                            :size="20"
                            aria-hidden="true"
                        />
                    </UiIconButton>
                </header>
                <div class="ui-modal__body"><slot /></div>
                <footer
                    v-if="$slots.footer"
                    class="ui-modal__footer"
                >
                    <slot name="footer" />
                </footer>
            </section>
        </div>
    </Teleport>
</template>
