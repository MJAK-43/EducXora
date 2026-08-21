import { mount } from '@vue/test-utils';
import { reactive } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { patch, reset } = vi.hoisted(() => ({ patch: vi.fn(), reset: vi.fn() }));
vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: { props: ['href'], template: '<a :href=href><slot /></a>' },
    useForm: (values: Record<string, unknown>) =>
        reactive({ ...values, errors: {}, processing: false, patch, reset }),
}));

import Show from '@/Pages/Attendance/Show.vue';
import Take from '@/Pages/Attendance/Take.vue';

const stubs = {
    AppLayout: { template: '<main><slot /></main>' },
    PageHeader: { template: '<header><slot name=actions /></header>' },
    UiCard: { template: '<section><slot name=header /><slot /></section>' },
    UiAlert: { template: '<aside />' },
    UiBadge: { template: '<span><slot /></span>' },
};
const options: Array<{ value: 'present' | 'absent' | 'excused'; label: string }> = [
    { value: 'present', label: 'Présent' },
    { value: 'absent', label: 'Absent' },
    { value: 'excused', label: 'Excusé' },
];
const sheet = {
    uuid: 'sheet-uuid',
    status: 'draft' as const,
    group: { name: 'Allemand A1' },
    teacher: { name: 'Mme Nkoa', status: null, status_label: 'Non pointé' },
    session: { date_label: '21/08/2026', start_time: '09:00', end_time: '10:30' },
    learners: [
        {
            uuid: 'attendance-1',
            learner_uuid: 'learner-1',
            full_name: 'Alice Mballa',
            status: null,
        },
        { uuid: 'attendance-2', learner_uuid: 'learner-2', full_name: 'Berthe Nkoa', status: null },
    ],
    validated_by: null,
    validated_at_label: null,
    corrections: [],
};

beforeEach(() => {
    patch.mockReset();
    reset.mockReset();
    vi.spyOn(window, 'confirm').mockReturnValue(true);
});

describe('Attendance Take', () => {
    it('renders every learner and all three explicit statuses', () => {
        const wrapper = mount(Take, {
            props: { sheet, options, canValidate: true },
            global: { stubs },
        });
        expect(wrapper.findAll('.roster-row')).toHaveLength(2);
        expect(wrapper.text()).toContain('Alice Mballa');
        expect(wrapper.text()).toContain('Présent');
        expect(wrapper.text()).toContain('Absent');
        expect(wrapper.text()).toContain('Excusé');
    });

    it('submits the learner draft to the scoped sheet endpoint', async () => {
        const wrapper = mount(Take, {
            props: { sheet, options, canValidate: true },
            global: { stubs },
        });
        await wrapper.get('form').trigger('submit');
        expect(patch).toHaveBeenCalledWith(
            '/attendance/sheet-uuid/draft',
            expect.objectContaining({ preserveScroll: true }),
        );
    });
});

describe('Attendance Show', () => {
    const validated = {
        ...sheet,
        status: 'validated' as const,
        teacher: { name: 'Mme Nkoa', status: 'present' as const, status_label: 'Présent' },
        learners: [
            {
                uuid: 'attendance-1',
                learner_uuid: 'learner-1',
                full_name: 'Alice Mballa',
                status: 'present' as const,
                status_label: 'Présent',
            },
        ],
        validated_by: 'Admin',
        validated_at_label: '21/08/2026 11:00',
    };

    it('shows locked validation state and correction controls to authorized staff', () => {
        const wrapper = mount(Show, {
            props: {
                sheet: validated,
                options,
                can: { take: true, validate: true, correct: true },
            },
            global: { stubs },
        });
        expect(wrapper.text()).toContain('Validé et verrouillé');
        expect(wrapper.findAll('button.text-button')).toHaveLength(2);
    });

    it('requires a visible reason and posts a scoped learner correction', async () => {
        const wrapper = mount(Show, {
            props: {
                sheet: validated,
                options,
                can: { take: true, validate: true, correct: true },
            },
            global: { stubs },
        });
        await wrapper.findAll('button.text-button')[1]?.trigger('click');
        expect(wrapper.get('textarea').attributes('required')).toBeDefined();
        await wrapper.get('textarea').setValue('Justificatif reçu.');
        await wrapper.get('.correction-form form').trigger('submit');
        expect(patch).toHaveBeenCalledWith(
            '/attendance/sheet-uuid/learners/attendance-1/correct',
            expect.objectContaining({ preserveScroll: true }),
        );
    });
});
