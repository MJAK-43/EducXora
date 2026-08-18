<script setup lang="ts">
import {
    BookOpenCheck,
    CalendarDays,
    ChartNoAxesCombined,
    CircleDollarSign,
    GraduationCap,
    Landmark,
    LayoutDashboard,
    MessageSquareText,
    Settings2,
    UserRoundCheck,
    UsersRound,
} from '@lucide/vue';
import type { Component } from 'vue';

defineProps<{ collapsed: boolean; mobileOpen: boolean }>();

interface NavigationItem {
    label: string;
    icon: Component;
    active?: boolean;
}

const navigation: NavigationItem[] = [
    { label: 'Dashboard', icon: LayoutDashboard },
    { label: 'Apprenants', icon: GraduationCap },
    { label: 'Groupes', icon: UsersRound },
    { label: 'Planning', icon: CalendarDays },
    { label: 'Présences', icon: UserRoundCheck },
    { label: 'Pédagogie', icon: BookOpenCheck },
    { label: 'Finance', icon: CircleDollarSign },
    { label: 'Communications', icon: MessageSquareText },
    { label: 'Rapports', icon: ChartNoAxesCombined },
    { label: 'Administration', icon: Settings2 },
];
</script>

<template>
    <aside
        id="app-navigation"
        class="app-sidebar"
        :class="{ 'app-sidebar--mobile-open': mobileOpen }"
        aria-label="Navigation principale"
    >
        <div class="app-sidebar__inner">
            <div class="app-sidebar__brand">
                <span class="app-sidebar__mark"
                    ><Landmark
                        :size="18"
                        aria-hidden="true"
                /></span>
                <span class="app-sidebar__label">EduXora</span>
            </div>
            <nav class="app-sidebar__nav">
                <div class="app-sidebar__section-label">Produit</div>
                <button
                    type="button"
                    class="app-sidebar__item app-sidebar__item--active"
                    aria-current="page"
                >
                    <LayoutDashboard aria-hidden="true" />
                    <span class="app-sidebar__label">Foundation</span>
                </button>
                <div class="app-sidebar__section-label">Aperçu des modules</div>
                <button
                    v-for="item in navigation"
                    :key="item.label"
                    type="button"
                    class="app-sidebar__item"
                    disabled
                    :title="
                        collapsed
                            ? `${item.label} — disponible dans une phase ultérieure`
                            : undefined
                    "
                >
                    <component
                        :is="item.icon"
                        aria-hidden="true"
                    />
                    <span class="app-sidebar__label">{{ item.label }}</span>
                </button>
            </nav>
            <div class="app-sidebar__footer">
                <div class="app-sidebar__foundation">
                    <span
                        class="app-sidebar__dot"
                        aria-hidden="true"
                    />
                    <span>Socle technique actif</span>
                </div>
            </div>
        </div>
    </aside>
</template>
