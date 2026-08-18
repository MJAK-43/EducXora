<script setup lang="ts">
import { ref } from 'vue';

import AppMain from './AppMain.vue';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';

withDefaults(defineProps<{ pageTitle?: string }>(), { pageTitle: 'Foundation' });

const collapsed = ref(false);
const mobileOpen = ref(false);
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
            :collapsed="collapsed"
            :mobile-open="mobileOpen"
        />
        <button
            v-if="mobileOpen"
            class="app-drawer-backdrop"
            type="button"
            aria-label="Fermer la navigation"
            @click="mobileOpen = false"
        />
        <div class="app-frame">
            <AppTopbar
                :collapsed="collapsed"
                :page-title="pageTitle"
                @toggle-desktop="collapsed = !collapsed"
                @open-mobile="mobileOpen = true"
            />
            <AppMain><slot /></AppMain>
        </div>
    </div>
</template>
