<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiInput from '@/Components/Ui/UiInput.vue';

const props = defineProps<{
    organization: { uuid: string; name: string; email?: string; phone?: string };
}>();
const form = useForm({
    name: props.organization.name,
    email: props.organization.email ?? '',
    phone: props.organization.phone ?? '',
});
</script>

<template>
    <form
        class="settings-form"
        @submit.prevent="
            form.patch(`/platform/organizations/${organization.uuid}`, { preserveScroll: true })
        "
    >
        <div class="form-grid">
            <UiInput
                v-model="form.name"
                label="Nom"
                required
                :error="form.errors.name"
            /><UiInput
                v-model="form.email"
                label="E-mail"
                type="email"
                :error="form.errors.email"
            />
        </div>
        <div class="inline-actions">
            <UiInput
                v-model="form.phone"
                label="Téléphone"
                :error="form.errors.phone"
            /><UiButton
                type="submit"
                size="sm"
                :loading="form.processing"
                >Enregistrer</UiButton
            >
        </div>
    </form>
</template>
