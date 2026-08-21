<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { LogOut, Menu, PanelLeftClose, PanelLeftOpen } from '@lucide/vue';
import UiAvatar from '@/Components/Ui/UiAvatar.vue';
import UiIconButton from '@/Components/Ui/UiIconButton.vue';

defineProps<{ collapsed: boolean; pageTitle: string }>();
defineEmits<{ toggleDesktop: []; openMobile: [] }>();
const page = usePage();
const auth = page.props.auth as {
    user?: { name: string };
    organizations?: Array<{ uuid: string; name: string }>;
    activeOrganizationUuid?: string;
};
function switchOrganization(event: Event) {
    router.post('/organizations/select', {
        organization_uuid: (event.target as HTMLSelectElement).value,
    });
}
</script>

<template>
    <header class="app-topbar">
        <div class="app-topbar__start">
            <UiIconButton
                class="app-topbar__mobile-toggle"
                label="Ouvrir la navigation"
                @click="$emit('openMobile')"
                ><Menu
                    :size="20"
                    aria-hidden="true" /></UiIconButton
            ><UiIconButton
                class="app-topbar__desktop-toggle"
                :label="collapsed ? 'Déployer la navigation' : 'Réduire la navigation'"
                :pressed="collapsed"
                @click="$emit('toggleDesktop')"
                ><PanelLeftOpen
                    v-if="collapsed"
                    :size="20"
                    aria-hidden="true" /><PanelLeftClose
                    v-else
                    :size="20"
                    aria-hidden="true"
            /></UiIconButton>
            <div
                class="app-topbar__breadcrumb"
                aria-label="Fil d’Ariane"
            >
                EduXora <span aria-hidden="true">/</span> <strong>{{ pageTitle }}</strong>
            </div>
        </div>
        <div class="app-topbar__end">
            <select
                v-if="auth?.organizations?.length"
                class="topbar-organization"
                :value="auth.activeOrganizationUuid"
                aria-label="Organisation active"
                @change="switchOrganization"
            >
                <option
                    v-for="organization in auth.organizations"
                    :key="organization.uuid"
                    :value="organization.uuid"
                >
                    {{ organization.name }}
                </option>
            </select>
            <div class="app-topbar__profile">
                <UiAvatar :name="auth?.user?.name ?? 'Utilisateur'" />
                <div class="app-topbar__profile-copy">
                    <span class="app-topbar__profile-name">{{ auth?.user?.name }}</span
                    ><span class="app-topbar__profile-role">Compte sécurisé</span>
                </div>
            </div>
            <UiIconButton
                label="Se déconnecter"
                @click="router.post('/logout')"
                ><LogOut
                    :size="19"
                    aria-hidden="true"
            /></UiIconButton>
        </div>
    </header>
</template>
