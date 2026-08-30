<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    BookOpenCheck,
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
import { computed, ref, type Component } from 'vue';

defineProps<{ collapsed: boolean; mobileOpen: boolean }>();
const emit = defineEmits<{ closeMobile: [] }>();
const sidebar = ref<HTMLElement | null>(null);
const page = usePage();
const auth = page.props.auth as
    | {
          isSuperAdmin?: boolean;
          canViewLearners?: boolean;
          canViewGroups?: boolean;
          canViewSchedule?: boolean;
          canViewAttendance?: boolean;
          canViewQuestionBank?: boolean;
          canViewUsers?: boolean;
          canViewRoles?: boolean;
          canViewOrganization?: boolean;
          canViewAudit?: boolean;
      }
    | undefined;
interface NavigationItem {
    label: string;
    href: string;
    icon: Component;
    visible?: boolean;
}
interface NavigationSection {
    label: string;
    items: NavigationItem[];
}
const rawSections: NavigationSection[] = [
    {
        label: 'Vue d’ensemble',
        items: [{ label: 'Tableau de bord', href: '/dashboard', icon: LayoutDashboard }],
    },
    {
        label: 'Gestion quotidienne',
        items: [
            {
                label: 'Apprenants',
                href: '/learners',
                icon: GraduationCap,
                visible: auth?.canViewLearners,
            },
            { label: 'Groupes', href: '/groups', icon: LibraryBig, visible: auth?.canViewGroups },
            {
                label: 'Planning',
                href: '/schedule',
                icon: CalendarDays,
                visible: auth?.canViewSchedule,
            },
            {
                label: 'Présences',
                href: '/attendance',
                icon: ClipboardCheck,
                visible: auth?.canViewAttendance,
            },
            {
                label: 'Banque de questions',
                href: '/pedagogy/questions',
                icon: BookOpenCheck,
                visible: auth?.canViewQuestionBank,
            },
        ],
    },
    {
        label: 'Administration',
        items: [
            {
                label: 'Utilisateurs',
                href: '/organization/users',
                icon: UsersRound,
                visible: auth?.canViewUsers,
            },
            {
                label: 'Rôles et permissions',
                href: '/organization/roles',
                icon: KeyRound,
                visible: auth?.canViewRoles,
            },
            {
                label: 'Journal d’audit',
                href: '/organization/audit',
                icon: Activity,
                visible: auth?.canViewAudit,
            },
            {
                label: 'Organisation',
                href: '/organization/settings',
                icon: Settings2,
                visible: auth?.canViewOrganization,
            },
        ],
    },
];
const sections = computed<NavigationSection[]>(() =>
    rawSections.map((section) => ({
        ...section,
        items: section.items.filter((item) => item.visible !== false),
    })),
);
function isActive(href: string): boolean {
    return href === '/dashboard' ? page.url === href : page.url.startsWith(href);
}

function focusableElements(): HTMLElement[] {
    return Array.from(
        sidebar.value?.querySelectorAll<HTMLElement>(
            'a[href], button:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])',
        ) ?? [],
    );
}

defineExpose({
    element: (): HTMLElement | null => sidebar.value,
    focusFirstItem: (): void => focusableElements()[0]?.focus(),
    focusableElements,
});
</script>

<template>
    <aside
        id="app-navigation"
        ref="sidebar"
        class="app-sidebar"
        :class="{ 'app-sidebar--mobile-open': mobileOpen }"
        :role="mobileOpen ? 'dialog' : undefined"
        :aria-modal="mobileOpen ? 'true' : undefined"
        aria-label="Navigation principale"
    >
        <div class="app-sidebar__inner">
            <Link
                href="/dashboard"
                class="app-sidebar__brand"
                @click="emit('closeMobile')"
                ><span class="app-sidebar__mark"
                    ><Landmark
                        :size="18"
                        aria-hidden="true" /></span
                ><span class="app-sidebar__label">EduXora</span></Link
            >
            <nav class="app-sidebar__nav">
                <template
                    v-for="section in sections"
                    :key="section.label"
                >
                    <template v-if="section.items.length">
                        <div class="app-sidebar__section-label">{{ section.label }}</div>
                        <Link
                            v-for="item in section.items"
                            :key="item.href"
                            :href="item.href"
                            class="app-sidebar__item"
                            :class="{ 'app-sidebar__item--active': isActive(item.href) }"
                            :aria-current="isActive(item.href) ? 'page' : undefined"
                            @click="emit('closeMobile')"
                        >
                            <component
                                :is="item.icon"
                                aria-hidden="true"
                            /><span class="app-sidebar__label">{{ item.label }}</span>
                        </Link>
                    </template>
                </template>
                <template v-if="auth?.isSuperAdmin">
                    <div class="app-sidebar__section-label">Plateforme</div>
                    <Link
                        href="/platform/organizations"
                        class="app-sidebar__item"
                        :class="{ 'app-sidebar__item--active': page.url.startsWith('/platform') }"
                        :aria-current="page.url.startsWith('/platform') ? 'page' : undefined"
                        @click="emit('closeMobile')"
                        ><Building2 aria-hidden="true" /><span class="app-sidebar__label"
                            >Organisations</span
                        ></Link
                    >
                </template>
            </nav>
            <div class="app-sidebar__footer">
                <div class="app-sidebar__foundation">
                    <span
                        class="app-sidebar__dot"
                        aria-hidden="true"
                    /><span>Connexion sécurisée</span>
                </div>
            </div>
        </div>
    </aside>
</template>
