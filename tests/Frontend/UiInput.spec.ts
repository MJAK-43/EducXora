import { mount } from '@vue/test-utils';

import UiInput from '@/Components/Ui/UiInput.vue';

describe('UiInput', () => {
    it('associates its visible label and emits the value', async () => {
        const wrapper = mount(UiInput, { props: { label: 'Nom du centre' } });
        const input = wrapper.get('input');

        expect(wrapper.get('label').attributes('for')).toBe(input.attributes('id'));
        await input.setValue('InoviXora');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['InoviXora']);
    });

    it('exposes errors to assistive technology', () => {
        const wrapper = mount(UiInput, {
            props: { label: 'Email', error: 'Adresse invalide' },
        });
        const input = wrapper.get('input');
        const error = wrapper.get('.ui-field__error');

        expect(input.attributes('aria-invalid')).toBe('true');
        expect(input.attributes('aria-describedby')).toBe(error.attributes('id'));
        expect(error.text()).toBe('Adresse invalide');
    });

    it('supports disabled and readonly states', () => {
        const disabled = mount(UiInput, { props: { label: 'Code', disabled: true } });
        const readonly = mount(UiInput, { props: { label: 'Référence', readonly: true } });

        expect(disabled.get('input').attributes('disabled')).toBeDefined();
        expect(readonly.get('input').attributes('readonly')).toBeDefined();
    });
});
