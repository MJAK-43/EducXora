import { mount } from '@vue/test-utils';
import { reactive } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { post, patch, deleteRequest, reset } = vi.hoisted(() => ({
    post: vi.fn(),
    patch: vi.fn(),
    deleteRequest: vi.fn(),
    reset: vi.fn(),
}));
vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { patch, delete: deleteRequest },
    useForm: (values: Record<string, unknown>) =>
        reactive({ ...values, errors: {}, processing: false, post, patch, reset }),
}));

import GroupForm from '@/Components/Groups/GroupForm.vue';
import GroupMemberManager from '@/Components/Groups/GroupMemberManager.vue';
import CourseSessionForm from '@/Components/Schedule/CourseSessionForm.vue';
import WeeklySchedule from '@/Components/Schedule/WeeklySchedule.vue';

const teacherOption = { value: '11111111-1111-4111-8111-111111111111', label: 'Mme Nkoa' };
const options = {
    levels: ['A1', 'A2', 'B1'].map((value) => ({ value, label: value })),
    languages: [{ value: 'de', label: 'Allemand' }],
    teachers: [teacherOption],
};
const groupOption = { value: '22222222-2222-4222-8222-222222222222', label: 'Allemand A1' };
const scheduleOptions = {
    groups: [groupOption],
    teachers: options.teachers,
};

beforeEach(() => {
    post.mockReset();
    patch.mockReset();
    deleteRequest.mockReset();
    reset.mockReset();
    vi.spyOn(window, 'confirm').mockReturnValue(true);
});

describe('GroupForm', () => {
    it('renders all required business fields with visible labels', () => {
        const wrapper = mount(GroupForm, { props: { options } });
        expect(wrapper.text()).toContain('Nom du groupe');
        expect(wrapper.text()).toContain('Enseignant responsable');
        expect(wrapper.text()).toContain('Niveau CECRL');
        expect(wrapper.text()).toContain('Capacité');
        expect(wrapper.findAll('[required]')).toHaveLength(5);
    });

    it('submits a new group', async () => {
        const wrapper = mount(GroupForm, { props: { options } });
        await wrapper.get('form').trigger('submit');
        expect(post).toHaveBeenCalledWith(
            '/groups',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('submits edition and displays the current membership count', async () => {
        const wrapper = mount(GroupForm, {
            props: {
                options,
                group: {
                    uuid: 'group-uuid',
                    name: 'A1 matin',
                    language: 'de',
                    level: 'A1',
                    capacity: 12,
                    members_count: 7,
                    teacher_membership_uuid: teacherOption.value,
                },
            },
        });
        expect(wrapper.text()).toContain('7 apprenant(s) actuellement affecté(s)');
        await wrapper.get('form').trigger('submit');
        expect(patch).toHaveBeenCalledWith('/groups/group-uuid', expect.any(Object));
    });
});

describe('GroupMemberManager', () => {
    const member = {
        uuid: 'learner-uuid',
        full_name: 'Alice Mballa',
        phone: '+237699123456',
        assigned_at_label: '21/08/2026',
    };
    const candidate = { value: 'candidate-uuid', label: 'Berthe Nkoa', phone: '+237677123456' };

    it('lists members and candidate selector', () => {
        const wrapper = mount(GroupMemberManager, {
            props: { groupUuid: 'group-uuid', members: [member], candidates: [candidate] },
        });
        expect(wrapper.text()).toContain('Alice Mballa');
        expect(wrapper.text()).toContain('Berthe Nkoa');
        expect(wrapper.text()).toContain('affecté le 21/08/2026');
    });

    it('attaches the selected learner', async () => {
        const wrapper = mount(GroupMemberManager, {
            props: { groupUuid: 'group-uuid', members: [], candidates: [candidate] },
        });
        await wrapper.get('select').setValue(candidate.value);
        await wrapper.get('form').trigger('submit');
        expect(post).toHaveBeenCalledWith(
            '/groups/group-uuid/learners',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('detaches after explicit confirmation and freezes archived membership', async () => {
        const wrapper = mount(GroupMemberManager, {
            props: { groupUuid: 'group-uuid', members: [member], candidates: [], disabled: false },
        });
        await wrapper.get('button.text-button').trigger('click');
        expect(deleteRequest).toHaveBeenCalledWith(
            '/groups/group-uuid/learners/learner-uuid',
            expect.any(Object),
        );
        await wrapper.setProps({ disabled: true });
        expect(wrapper.find('form').exists()).toBe(false);
        expect(wrapper.find('button.text-button').exists()).toBe(false);
    });
});

describe('CourseSessionForm', () => {
    it('uses native date and time controls with required group and teacher', () => {
        const wrapper = mount(CourseSessionForm, {
            props: { options: scheduleOptions, initialDate: '2026-09-14' },
        });
        expect(wrapper.get('input[type="date"]').attributes('required')).toBeDefined();
        expect(wrapper.findAll('input[type="time"]')).toHaveLength(2);
        expect(wrapper.findAll('select[required]')).toHaveLength(2);
    });

    it('submits a new session', async () => {
        const wrapper = mount(CourseSessionForm, {
            props: { options: scheduleOptions, initialDate: '2026-09-14' },
        });
        await wrapper.get('form').trigger('submit');
        expect(post).toHaveBeenCalledWith(
            '/schedule',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('prepares edition with existing values', async () => {
        const session = {
            uuid: 'session-uuid',
            group_uuid: groupOption.value,
            teacher_membership_uuid: teacherOption.value,
            room: 'Salle A',
            date: '2026-09-14',
            start_time: '09:00',
            end_time: '10:30',
        };
        const wrapper = mount(CourseSessionForm, { props: { options: scheduleOptions, session } });
        expect((wrapper.get('input[type="date"]').element as HTMLInputElement).value).toBe(
            '2026-09-14',
        );
        await wrapper.get('form').trigger('submit');
        expect(patch).toHaveBeenCalledWith('/schedule/session-uuid', expect.any(Object));
    });
});

describe('WeeklySchedule', () => {
    const can = { update: true, cancel: true };
    const session = {
        uuid: 'session-uuid',
        group_name: 'Allemand A1',
        teacher_name: 'Mme Nkoa',
        room: 'Salle A',
        date: '2026-09-14',
        date_label: '14/09/2026',
        start_time: '09:00',
        end_time: '10:30',
        status: 'scheduled' as const,
    };

    it('renders the seven days and the complete session card', () => {
        const wrapper = mount(WeeklySchedule, {
            props: { sessions: [session], can, weekStart: '2026-09-14' },
        });
        expect(wrapper.findAll('.schedule-day')).toHaveLength(7);
        expect(wrapper.text()).toContain('Allemand A1');
        expect(wrapper.text()).toContain('Mme Nkoa');
        expect(wrapper.text()).toContain('09:00–10:30');
    });

    it('shows explicit empty states for days without sessions', () => {
        const wrapper = mount(WeeklySchedule, {
            props: { sessions: [], can, weekStart: '2026-09-14' },
        });
        expect(wrapper.findAll('.schedule-day__empty')).toHaveLength(7);
    });

    it('cancels a scheduled session after confirmation', async () => {
        const wrapper = mount(WeeklySchedule, {
            props: { sessions: [session], can, weekStart: '2026-09-14' },
        });
        await wrapper.get('.session-card__actions button').trigger('click');
        expect(patch).toHaveBeenCalledWith(
            '/schedule/session-uuid/cancel',
            {},
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('marks cancelled sessions and hides mutation actions', () => {
        const wrapper = mount(WeeklySchedule, {
            props: {
                sessions: [{ ...session, status: 'cancelled' as const }],
                can,
                weekStart: '2026-09-14',
            },
        });
        expect(wrapper.get('.session-card').classes()).toContain('session-card--cancelled');
        expect(wrapper.text()).toContain('Annulée');
        expect(wrapper.find('.session-card__actions').exists()).toBe(false);
    });
});
