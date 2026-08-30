<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { LogOut, Menu, PanelLeftClose, PanelLeftOpen } from '@lucide/vue';
import { computed, ref } from 'vue';
import UiAvatar from '@/Components/Ui/UiAvatar.vue';
import UiIconButton from '@/Components/Ui/UiIconButton.vue';
defineProps<{ collapsed: boolean; mobileOpen: boolean; pageTitle: string }>();
defineEmits<{ toggleDesktop: []; openMobile: [] }>();
const mobileToggle = ref<InstanceType<typeof UiIconButton> | null>(null);
const page = usePage();
const auth = page.props.auth as {
    user?: { name: string };
    organizations?: Array<{ uuid: string; name: string }>;
    activeOrganizationUuid?: string;
    roleNames?: string[];
};
const roleLabel = computed(() => auth?.roleNames?.join(', ') || 'Membre');

defineExpose({
    focusMobileToggle: (): void => mobileToggle.value?.focus(),
});

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
                ref="mobileToggle"
                class="app-topbar__mobile-toggle"
                label="Ouvrir la navigation"
                aria-controls="app-navigation"
                :aria-expanded="mobileOpen"
                @click="$emit('openMobile')"
                ><Menu
                    :size="20"
                    aria-hidden="true"
            /></UiIconButton>
            <UiIconButton
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
                <span>EduXora</span><span aria-hidden="true">/</span
                ><strong>{{ pageTitle }}</strong>
            </div>
        </div>
        <div class="app-topbar__end">
            <label
                v-if="auth?.organizations?.length"
                class="topbar-organization-wrap"
                ><span class="sr-only">Organisation active</span
                ><select
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
                </select></label
            >
            <div class="app-topbar__profile">
                <UiAvatar :name="auth?.user?.name ?? 'Utilisateur'" />
                <div class="app-topbar__profile-copy">
                    <span class="app-topbar__profile-name">{{ auth?.user?.name }}</span
                    ><span class="app-topbar__profile-role">{{ roleLabel }}</span>
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
