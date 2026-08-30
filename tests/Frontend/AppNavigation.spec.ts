import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { afterEach, describe, expect, it, vi } from 'vitest';

const { currentPage, routerPost } = vi.hoisted(() => ({
    currentPage: {
        url: '/dashboard',
        props: { auth: {} as Record<string, unknown>, flash: {} },
    },
    routerPost: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { post: routerPost },
    usePage: () => currentPage,
}));

import AppLayout from '@/Layouts/AppLayout.vue';
import AppSidebar from '@/Layouts/AppSidebar.vue';

function setAuth(auth: Record<string, unknown>): void {
    currentPage.url = '/dashboard';
    currentPage.props.auth = {
        user: { name: 'Ada Nkoa' },
        organizations: [{ uuid: 'org-1', name: 'Centre Atlas' }],
        activeOrganizationUuid: 'org-1',
        roleNames: [],
        ...auth,
    };
}

afterEach(() => {
    document.body.innerHTML = '';
    routerPost.mockReset();
});

describe('Role-aware application navigation', () => {
    it('hides the user directory and question bank from a teacher', () => {
        setAuth({
            roleNames: ['Teacher/Trainer'],
            canViewGroups: true,
            canViewSchedule: true,
            canViewAttendance: true,
            canViewUsers: false,
            canViewQuestionBank: false,
        });

        const wrapper = mount(AppSidebar, {
            props: { collapsed: false, mobileOpen: false },
        });

        expect(wrapper.text()).toContain('Groupes');
        expect(wrapper.text()).toContain('Planning');
        expect(wrapper.text()).toContain('Présences');
        expect(wrapper.text()).not.toContain('Utilisateurs');
        expect(wrapper.text()).not.toContain('Banque de questions');
        wrapper.unmount();
    });

    it('hides the question bank from staff while retaining operational modules', () => {
        setAuth({
            roleNames: ['Staff'],
            canViewLearners: true,
            canViewGroups: true,
            canViewSchedule: true,
            canViewAttendance: true,
            canViewQuestionBank: false,
        });

        const wrapper = mount(AppSidebar, {
            props: { collapsed: false, mobileOpen: false },
        });

        expect(wrapper.text()).toContain('Apprenants');
        expect(wrapper.text()).toContain('Présences');
        expect(wrapper.text()).not.toContain('Banque de questions');
        wrapper.unmount();
    });

    it('keeps the implemented administration available to an organization admin', () => {
        setAuth({
            roleNames: ['Organization Admin'],
            canViewLearners: true,
            canViewGroups: true,
            canViewSchedule: true,
            canViewAttendance: true,
            canViewQuestionBank: true,
            canViewUsers: true,
            canViewRoles: true,
            canViewOrganization: true,
            canViewAudit: true,
        });

        const wrapper = mount(AppSidebar, {
            props: { collapsed: false, mobileOpen: false },
        });

        expect(wrapper.text()).toContain('Utilisateurs');
        expect(wrapper.text()).toContain('Banque de questions');
        expect(wrapper.text()).toContain('Journal d’audit');
        wrapper.unmount();
    });
});

describe('Mobile navigation accessibility', () => {
    it('moves and traps focus, closes with Escape, and restores the trigger focus', async () => {
        setAuth({
            roleNames: ['Organization Admin'],
            canViewGroups: true,
            canViewQuestionBank: true,
            canViewUsers: true,
        });

        const wrapper = mount(AppLayout, {
            props: { pageTitle: 'Tableau de bord' },
            attachTo: document.body,
        });
        const trigger = wrapper.get<HTMLButtonElement>('.app-topbar__mobile-toggle');

        await trigger.trigger('click');
        await nextTick();

        const drawer = wrapper.get<HTMLElement>('#app-navigation');
        const links = drawer.findAll<HTMLAnchorElement>('a[href]');
        expect(trigger.attributes('aria-expanded')).toBe('true');
        expect(drawer.attributes('role')).toBe('dialog');
        expect(drawer.attributes('aria-modal')).toBe('true');
        expect(document.activeElement).toBe(links[0]?.element);

        links.at(-1)?.element.focus();
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', bubbles: true }));
        expect(document.activeElement).toBe(links[0]?.element);

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
        await nextTick();
        await nextTick();

        expect(wrapper.find('.app-drawer-backdrop').exists()).toBe(false);
        expect(trigger.attributes('aria-expanded')).toBe('false');
        expect(document.activeElement).toBe(trigger.element);
        wrapper.unmount();
    });
});
