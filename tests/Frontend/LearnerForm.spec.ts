import { mount } from '@vue/test-utils';
import { reactive } from 'vue';
import { vi } from 'vitest';

const post = vi.fn();
vi.mock('@inertiajs/vue3', () => ({
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: (values: Record<string, unknown>) =>
        reactive({ ...values, errors: {}, processing: false, post }),
}));

import LearnerForm from '@/Components/Learners/LearnerForm.vue';

const options = {
    levels: ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'].map((value) => ({ value, label: value })),
    languages: [{ value: 'de', label: 'Allemand' }],
};

describe('LearnerForm', () => {
    it('renders labelled required fields and constrained private photo input', () => {
        const wrapper = mount(LearnerForm, { props: { options } });
        const labels = wrapper.findAll('label').map((label) => label.text());

        expect(labels).toContain('Prénom *');
        expect(labels).toContain('Nom *');
        expect(labels).toContain('Téléphone *');
        expect(wrapper.get('#learner-photo').attributes('accept')).toBe(
            'image/jpeg,image/png,image/webp',
        );
        expect(wrapper.text()).toContain('Stockage privé');
    });

    it('submits creation as multipart data', async () => {
        post.mockClear();
        const wrapper = mount(LearnerForm, { props: { options } });
        await wrapper.get('form').trigger('submit');

        expect(post).toHaveBeenCalledWith(
            '/learners',
            expect.objectContaining({ forceFormData: true }),
        );
    });

    it('prepares an edit request and exposes photo removal', async () => {
        post.mockClear();
        const wrapper = mount(LearnerForm, {
            props: {
                options,
                learner: {
                    uuid: 'learner-uuid',
                    first_name: 'Alice',
                    last_name: 'Mballa',
                    birth_date: '2002-04-15',
                    phone: '+237699123456',
                    email: null,
                    language: 'de',
                    initial_level: 'A1',
                    has_photo: true,
                    photo_url: '/photo',
                },
            },
        });
        expect(wrapper.text()).toContain('Retirer la photo actuelle');
        await wrapper.get('form').trigger('submit');
        expect(post).toHaveBeenCalledWith('/learners/learner-uuid', expect.any(Object));
    });
});
