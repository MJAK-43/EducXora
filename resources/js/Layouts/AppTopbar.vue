<script setup lang="ts">
import { Bell, Menu, PanelLeftClose, PanelLeftOpen } from '@lucide/vue';

import UiAvatar from '@/Components/Ui/UiAvatar.vue';
import UiIconButton from '@/Components/Ui/UiIconButton.vue';

defineProps<{ collapsed: boolean; pageTitle: string }>();
defineEmits<{ toggleDesktop: []; openMobile: [] }>();
</script>

<template>
    <header class="app-topbar">
        <div class="app-topbar__start">
            <UiIconButton
                class="app-topbar__mobile-toggle"
                label="Ouvrir la navigation"
                @click="$emit('openMobile')"
            >
                <Menu
                    :size="20"
                    aria-hidden="true"
                />
            </UiIconButton>
            <UiIconButton
                class="app-topbar__desktop-toggle"
                :label="collapsed ? 'Déployer la navigation' : 'Réduire la navigation'"
                :pressed="collapsed"
                @click="$emit('toggleDesktop')"
            >
                <PanelLeftOpen
                    v-if="collapsed"
                    :size="20"
                    aria-hidden="true"
                />
                <PanelLeftClose
                    v-else
                    :size="20"
                    aria-hidden="true"
                />
            </UiIconButton>
            <div
                class="app-topbar__breadcrumb"
                aria-label="Fil d'Ariane"
            >
                EduXora <span aria-hidden="true">/</span> <strong>{{ pageTitle }}</strong>
            </div>
        </div>
        <div class="app-topbar__end">
            <UiIconButton
                label="Notifications — aucune notification"
                disabled
            >
                <Bell
                    :size="19"
                    aria-hidden="true"
                />
            </UiIconButton>
            <div class="app-topbar__profile">
                <UiAvatar name="Équipe EduXora" />
                <div class="app-topbar__profile-copy">
                    <span class="app-topbar__profile-name">Équipe EduXora</span>
                    <span class="app-topbar__profile-role">Environnement de développement</span>
                </div>
            </div>
        </div>
    </header>
</template>
