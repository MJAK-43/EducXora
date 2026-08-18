import { mount } from '@vue/test-utils';

import UiAlert from '@/Components/Ui/UiAlert.vue';

describe('UiAlert', () => {
    it('renders informational content as a status', () => {
        const wrapper = mount(UiAlert, {
            props: { title: 'Information', tone: 'info' },
            slots: { default: 'Le socle est prêt.' },
        });

        expect(wrapper.attributes('role')).toBe('status');
        expect(wrapper.text()).toContain('Information');
        expect(wrapper.text()).toContain('Le socle est prêt.');
    });

    it('renders errors as alerts', () => {
        const wrapper = mount(UiAlert, {
            props: { title: 'Erreur', tone: 'danger' },
        });

        expect(wrapper.attributes('role')).toBe('alert');
        expect(wrapper.classes()).toContain('ui-alert--danger');
    });
});
