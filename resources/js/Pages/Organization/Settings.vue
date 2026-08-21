<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiInput from '@/Components/Ui/UiInput.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
const props = defineProps<{ organization: Record<string, string>; canUpdate: boolean }>();
const form = useForm({
    name: props.organization.name,
    email: props.organization.email ?? '',
    phone: props.organization.phone ?? '',
    country_code: props.organization.country_code,
    timezone: props.organization.timezone,
    currency: props.organization.currency,
    locale: props.organization.locale,
});
</script>
<template>
    <Head title="Organisation" /><AppLayout page-title="Organisation"
        ><PageHeader
            eyebrow="Administration"
            title="Paramètres de l’organisation"
            description="Coordonnées et préférences régionales de votre espace."
        />
        <form
            class="settings-form"
            @submit.prevent="form.patch('/organization/settings')"
        >
            <UiInput
                v-model="form.name"
                label="Nom"
                required
                :disabled="!canUpdate"
                :error="form.errors.name"
            />
            <div class="form-grid">
                <UiInput
                    v-model="form.email"
                    label="E-mail"
                    type="email"
                    :disabled="!canUpdate"
                    :error="form.errors.email"
                /><UiInput
                    v-model="form.phone"
                    label="Téléphone"
                    type="tel"
                    :disabled="!canUpdate"
                    :error="form.errors.phone"
                />
            </div>
            <div class="form-grid">
                <UiInput
                    v-model="form.country_code"
                    label="Pays (ISO)"
                    required
                    :disabled="!canUpdate"
                /><UiInput
                    v-model="form.timezone"
                    label="Fuseau horaire"
                    required
                    :disabled="!canUpdate"
                />
            </div>
            <div class="form-grid">
                <UiInput
                    v-model="form.currency"
                    label="Devise"
                    required
                    :disabled="!canUpdate"
                /><UiInput
                    v-model="form.locale"
                    label="Langue"
                    required
                    :disabled="!canUpdate"
                />
            </div>
            <UiButton
                v-if="canUpdate"
                type="submit"
                :loading="form.processing"
                >Enregistrer</UiButton
            >
        </form></AppLayout
    >
</template>
