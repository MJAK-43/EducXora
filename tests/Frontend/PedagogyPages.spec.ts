import { mount } from '@vue/test-utils';
import { reactive } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { formPost, formPatch, routerPost, routerPatch } = vi.hoisted(() => ({
    formPost: vi.fn(),
    formPatch: vi.fn(),
    routerPost: vi.fn(),
    routerPatch: vi.fn(),
}));
vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { post: routerPost, patch: routerPatch, get: vi.fn() },
    usePage: () => ({ props: { flash: {} } }),
    useForm: (values: Record<string, unknown>) =>
        reactive({ ...values, errors: {}, processing: false, post: formPost, patch: formPatch }),
}));

import QuestionForm from '@/Components/Pedagogy/QuestionForm.vue';
import LearnerHistory from '@/Pages/Pedagogy/LearnerHistory.vue';
import Result from '@/Pages/Pedagogy/Placement/Result.vue';
import Take from '@/Pages/Pedagogy/Placement/Take.vue';
import QuestionIndex from '@/Pages/Pedagogy/Questions/Index.vue';

const stubs = {
    AppLayout: { template: '<main><slot /></main>' },
    PageHeader: { template: '<header><slot name="actions" /></header>' },
    UiCard: { template: '<section><slot name="header" /><slot /></section>' },
    UiAlert: { props: ['title'], template: '<aside>{{ title }}<slot /></aside>' },
    UiBadge: { template: '<span><slot /></span>' },
};
const levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'].map((value) => ({ value, label: value }));

beforeEach(() => {
    formPost.mockReset();
    formPatch.mockReset();
    routerPost.mockReset();
    routerPatch.mockReset();
    vi.spyOn(window, 'confirm').mockReturnValue(true);
});

describe('QuestionForm', () => {
    it('renders exactly four choices and submits organization content', async () => {
        const wrapper = mount(QuestionForm, { props: { levels }, global: { stubs } });
        expect(wrapper.text()).toContain('Choix A');
        expect(wrapper.text()).toContain('Choix D');
        expect(wrapper.findAll('input[required]')).toHaveLength(4);
        await wrapper.get('form').trigger('submit');
        expect(formPost).toHaveBeenCalledWith('/pedagogy/questions');
    });

    it('keeps a system question readonly without mutation action', () => {
        const wrapper = mount(QuestionForm, {
            props: {
                levels,
                question: {
                    uuid: 'question-system',
                    source: 'system',
                    level: 'A1',
                    prompt: 'Wie geht es dir?',
                    choices: { A: 'Gut', B: 'Rot', C: 'Drei', D: 'Montag' },
                    correct_choice: 'A',
                    status: 'active',
                    readonly: true,
                },
                can: { update: false, disable: false, enable: false },
            },
            global: { stubs },
        });
        expect(wrapper.text()).toContain('Question système en lecture seule');
        expect(wrapper.find('button[type="submit"]').exists()).toBe(false);
    });

    it('renders an untrusted prompt as text rather than executable markup', () => {
        const wrapper = mount(QuestionIndex, {
            props: {
                questions: {
                    data: [
                        {
                            uuid: 'question-xss',
                            source: 'organization',
                            source_label: 'Organisation',
                            level: 'A1',
                            prompt: '<script>alert(1)</script>',
                            status: 'active',
                            readonly: false,
                        },
                    ],
                    links: [],
                    total: 1,
                },
                filters: { search: '', level: '', source: '', status: '' },
                levels,
                can: { create: true },
            },
            global: { stubs },
        });
        expect(wrapper.find('script').exists()).toBe(false);
        expect(wrapper.text()).toContain('<script>alert(1)</script>');
    });
});

describe('Placement test', () => {
    const attempt = {
        uuid: 'attempt-uuid',
        learner: { uuid: 'learner-uuid', name: 'Ada Nkoa', current_level: 'A1' },
        question_count: 18,
        answered_count: 0,
        questions: [
            {
                uuid: 'snapshot-uuid',
                position: 1,
                prompt: 'Wie heißt du?',
                choices: { A: 'Ada', B: 'Blau', C: 'Drei', D: 'Montag' },
                selected_choice: null,
            },
        ],
    };

    it('shows one current question and posts its selected answer', async () => {
        const wrapper = mount(Take, {
            props: { attempt, can: { answer: true, complete: true, review: false } },
            global: { stubs },
        });
        expect(wrapper.text()).toContain('Question 1 sur 18');
        await wrapper.get('input[value="A"]').setValue(true);
        await wrapper.get('form').trigger('submit');
        expect(formPost).toHaveBeenCalledWith(
            '/pedagogy/tests/attempt-uuid/answers',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('separates automatic suggestion from human validation', async () => {
        const wrapper = mount(Result, {
            props: {
                attempt: {
                    ...attempt,
                    status: 'completed',
                    raw_score: 15,
                    percentage: '83.33',
                    scoring_version: 'v1',
                    suggested_level: 'C1',
                    suggested_group: null,
                    validated_level: null,
                    validated_group: null,
                    review_reason: null,
                    completed_at: '22/08/2026 10:00',
                    reviewed_at: null,
                    reviewer: null,
                },
                groups: [],
                levels,
                can: { review: true },
            },
            global: { stubs },
        });
        expect(wrapper.text()).toContain('Suggestion non définitive');
        expect(wrapper.text()).toContain('Validation de la direction');
        await wrapper.get('form').trigger('submit');
        expect(formPatch).toHaveBeenCalledWith(
            '/pedagogy/tests/attempt-uuid/review',
            expect.objectContaining({ preserveScroll: true }),
        );
    });
});

describe('Learner pedagogy history', () => {
    it('shows current level, starts a test and submits a reasoned manual update', async () => {
        const wrapper = mount(LearnerHistory, {
            props: {
                learner: {
                    uuid: 'learner-uuid',
                    name: 'Ada Nkoa',
                    status: 'active',
                    initial_level: 'A1',
                    current_level: 'A2',
                    active_group: null,
                },
                attempts: [],
                history: [],
                levels,
                can: { startTest: true, updateLevel: true },
            },
            global: { stubs },
        });
        expect(wrapper.text()).toContain('Niveau actuel');
        await wrapper.findAll('button')[0]?.trigger('click');
        expect(routerPost).toHaveBeenCalledWith('/pedagogy/learners/learner-uuid/tests');
        await wrapper.get('textarea').setValue('Progression observée et confirmée.');
        await wrapper.get('form').trigger('submit');
        expect(formPatch).toHaveBeenCalledWith(
            '/learners/learner-uuid/level',
            expect.objectContaining({ preserveScroll: true }),
        );
    });
});
