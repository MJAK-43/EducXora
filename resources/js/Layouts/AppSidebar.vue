<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    CalendarDays,
    ClipboardCheck,
    GraduationCap,
    KeyRound,
    Landmark,
    LayoutDashboard,
    LibraryBig,
    Settings2,
    UsersRound,
} from '@lucide/vue';
import { computed, type Component } from 'vue';

defineProps<{ collapsed: boolean; mobileOpen: boolean }>();
const page = usePage();
const auth = page.props.auth as
    | {
          isSuperAdmin?: boolean;
          canViewLearners?: boolean;
          canViewGroups?: boolean;
          canViewSchedule?: boolean;
          canViewAttendance?: boolean;
      }
    | undefined;
const navigation: Array<{ label: string; href: string; icon: Component; visible?: boolean }> = [
    { label: 'Tableau de bord', href: '/dashboard', icon: LayoutDashboard },
    { label: 'Apprenants', href: '/learners', icon: GraduationCap, visible: auth?.canViewLearners },
    { label: 'Groupes', href: '/groups', icon: LibraryBig, visible: auth?.canViewGroups },
    { label: 'Planning', href: '/schedule', icon: CalendarDays, visible: auth?.canViewSchedule },
    {
        label: 'Présences',
        href: '/attendance',
        icon: ClipboardCheck,
        visible: auth?.canViewAttendance,
    },
    { label: 'Utilisateurs', href: '/organization/users', icon: UsersRound },
    { label: 'Rôles et permissions', href: '/organization/roles', icon: KeyRound },
    { label: 'Organisation', href: '/organization/settings', icon: Settings2 },
];
const visibleNavigation = computed(() => navigation.filter((item) => item.visible !== false));
</script>

<template>
    <aside
        id="app-navigation"
        class="app-sidebar"
        :class="{ 'app-sidebar--mobile-open': mobileOpen }"
        aria-label="Navigation principale"
    >
        <div class="app-sidebar__inner">
            <Link
                href="/dashboard"
                class="app-sidebar__brand"
                ><span class="app-sidebar__mark"
                    ><Landmark
                        :size="18"
                        aria-hidden="true" /></span
                ><span class="app-sidebar__label">EduXora</span></Link
            >
            <nav class="app-sidebar__nav">
                <div class="app-sidebar__section-label">Espace de travail</div>
                <Link
                    v-for="item in visibleNavigation"
                    :key="item.href"
                    :href="item.href"
                    class="app-sidebar__item"
                    :class="{ 'app-sidebar__item--active': page.url.startsWith(item.href) }"
                    ><component
                        :is="item.icon"
                        aria-hidden="true"
                    /><span class="app-sidebar__label">{{ item.label }}</span></Link
                >
                <template v-if="auth?.isSuperAdmin"
                    ><div class="app-sidebar__section-label">Plateforme</div>
                    <Link
                        href="/platform/organizations"
                        class="app-sidebar__item"
                        :class="{ 'app-sidebar__item--active': page.url.startsWith('/platform') }"
                        ><Building2 aria-hidden="true" /><span class="app-sidebar__label"
                            >Organisations</span
                        ></Link
                    ></template
                >
            </nav>
            <div class="app-sidebar__footer">
                <div class="app-sidebar__foundation">
                    <span
                        class="app-sidebar__dot"
                        aria-hidden="true"
                    /><span>Identité sécurisée</span>
                </div>
            </div>
        </div>
    </aside>
</template>
