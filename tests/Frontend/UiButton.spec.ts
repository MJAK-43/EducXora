import { mount } from '@vue/test-utils';

import UiButton from '@/Components/Ui/UiButton.vue';

describe('UiButton', () => {
    it('renders its label and emits a click', async () => {
        const wrapper = mount(UiButton, { slots: { default: 'Enregistrer' } });

        expect(wrapper.text()).toContain('Enregistrer');
        await wrapper.get('button').trigger('click');
        expect(wrapper.emitted('click')).toHaveLength(1);
    });

    it('prevents interaction while loading', async () => {
        const wrapper = mount(UiButton, {
            props: { loading: true },
            slots: { default: 'Enregistrement' },
        });

        const button = wrapper.get('button');
        expect(button.attributes('disabled')).toBeDefined();
        expect(button.attributes('aria-busy')).toBe('true');
        await button.trigger('click');
        expect(wrapper.emitted('click')).toBeUndefined();
    });

    it('applies variant and size classes', () => {
        const wrapper = mount(UiButton, {
            props: { variant: 'danger', size: 'lg' },
        });

        expect(wrapper.classes()).toContain('ui-button--danger');
        expect(wrapper.classes()).toContain('ui-button--lg');
    });
});
