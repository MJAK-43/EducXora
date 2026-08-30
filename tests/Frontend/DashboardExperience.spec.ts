import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
}));

import Dashboard from '@/Pages/Dashboard.vue';

const stubs = {
    AppLayout: { template: '<main><slot /></main>' },
    PageHeader: { template: '<header><slot name="actions" /></header>' },
    UiCard: { template: '<section><slot name="header" /><slot /></section>' },
    UiBadge: { template: '<span><slot /></span>' },
    UiEmptyState: { props: ['title', 'message'], template: '<p>{{ title }} {{ message }}</p>' },
};

const overview = {
    dateLabel: 'Samedi 22 août 2026',
    metrics: { activeLearners: 42, activeGroups: 4, sessionsToday: 2, attendanceRate: 91 },
    attendance: { present: 20, absent: 1, excused: 1, pendingSessions: 1 },
    todaySessions: [
        {
            uuid: 'session-1',
            groupName: 'Allemand A1',
            teacherName: 'Mme Nkoa',
            room: 'Salle 2',
            startsAt: '09:00',
            endsAt: '10:30',
            status: 'scheduled' as const,
            attendanceUuid: 'sheet-1',
            attendanceStatus: 'validated' as const,
        },
    ],
    groups: [
        {
            uuid: 'group-1',
            name: 'Allemand A1',
            level: 'A1',
            teacherName: 'Mme Nkoa',
            learnerCount: 12,
            capacity: 15,
        },
    ],
    recentLearners: [
        {
            uuid: 'learner-1',
            name: 'Alice Mballa',
            level: 'A1',
            status: 'active',
            registeredOn: '22/08/2026',
        },
    ],
    recentActivity: [
        { action: 'learner.created', actor: 'Admin', occurredAt: 'il y a une minute' },
    ],
    can: {
        viewLearners: true,
        viewGroups: true,
        viewSchedule: true,
        viewAttendance: true,
        viewAudit: true,
    },
};

describe('Dashboard experience', () => {
    it('renders operational metrics and actionable real data', () => {
        const wrapper = mount(Dashboard, {
            props: {
                organization: { uuid: 'org-1', name: 'Centre Atlas', status: 'active' },
                overview,
            },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Apprenants actifs');
        expect(wrapper.text()).toContain('42');
        expect(wrapper.text()).toContain('Allemand A1');
        expect(wrapper.text()).toContain('Alice Mballa');
        expect(wrapper.find('a[href="/attendance/sheet-1"]').exists()).toBe(true);
        expect(wrapper.find('a[href="/organization/audit"]').exists()).toBe(true);
    });

    it('hides unauthorized datasets without replacing them with fake values', () => {
        const wrapper = mount(Dashboard, {
            props: {
                organization: { uuid: 'org-1', name: 'Centre Atlas', status: 'active' },
                overview: {
                    ...overview,
                    metrics: { ...overview.metrics, activeLearners: null },
                    recentLearners: [],
                    recentActivity: [],
                    can: { ...overview.can, viewLearners: false, viewAudit: false },
                },
            },
            global: { stubs },
        });

        expect(wrapper.text()).not.toContain('Apprenants actifs');
        expect(wrapper.text()).not.toContain('Derniers apprenants');
        expect(wrapper.text()).not.toContain('Activité récente');
    });
});
