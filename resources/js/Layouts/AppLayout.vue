<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import UiAlert from '@/Components/Ui/UiAlert.vue';

import AppMain from './AppMain.vue';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';

withDefaults(defineProps<{ pageTitle?: string }>(), { pageTitle: 'Foundation' });

const collapsed = ref(false);
const mobileOpen = ref(false);
const sidebar = ref<{
    element: () => HTMLElement | null;
    focusFirstItem: () => void;
    focusableElements: () => HTMLElement[];
} | null>(null);
const topbar = ref<{ focusMobileToggle: () => void } | null>(null);
const page = usePage();
const flashStatus = computed(() => (page.props.flash as { status?: string } | undefined)?.status);

async function openMobile(): Promise<void> {
    mobileOpen.value = true;
    await nextTick();
    sidebar.value?.focusFirstItem();
}

async function closeMobile(restoreFocus = true): Promise<void> {
    if (!mobileOpen.value) return;

    mobileOpen.value = false;
    if (restoreFocus) {
        await nextTick();
        topbar.value?.focusMobileToggle();
    }
}

function handleDrawerKeydown(event: KeyboardEvent): void {
    if (!mobileOpen.value) return;

    if (event.key === 'Escape') {
        event.preventDefault();
        void closeMobile();
        return;
    }

    if (event.key !== 'Tab') return;

    const drawer = sidebar.value?.element();
    const focusable = sidebar.value?.focusableElements() ?? [];
    if (!drawer || focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    const active = document.activeElement;

    if (event.shiftKey && (active === first || !drawer.contains(active))) {
        event.preventDefault();
        last?.focus();
    } else if (!event.shiftKey && (active === last || !drawer.contains(active))) {
        event.preventDefault();
        first?.focus();
    }
}

onMounted(() => document.addEventListener('keydown', handleDrawerKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', handleDrawerKeydown));
</script>

<template>
    <div
        class="app-layout"
        :class="{ 'app-layout--collapsed': collapsed }"
    >
        <a
            class="sr-only"
            href="#main-content"
            >Aller au contenu principal</a
        >
        <AppSidebar
            ref="sidebar"
            :collapsed="collapsed"
            :mobile-open="mobileOpen"
            @close-mobile="closeMobile(false)"
        />
        <button
            v-if="mobileOpen"
            class="app-drawer-backdrop"
            type="button"
            aria-label="Fermer la navigation"
            @click="closeMobile()"
        />
        <div class="app-frame">
            <AppTopbar
                ref="topbar"
                :collapsed="collapsed"
                :mobile-open="mobileOpen"
                :page-title="pageTitle"
                @toggle-desktop="collapsed = !collapsed"
                @open-mobile="openMobile"
            />
            <AppMain>
                <UiAlert
                    v-if="flashStatus"
                    class="app-flash"
                    tone="success"
                    title="Opération réussie"
                    >{{ flashStatus }}</UiAlert
                >
                <slot />
            </AppMain>
        </div>
    </div>
</template>
