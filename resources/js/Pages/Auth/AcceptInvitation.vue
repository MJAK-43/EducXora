<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiInput from '@/Components/Ui/UiInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
defineProps<{ token: string; email: string; organization: string; existingUser: boolean }>();
const form = useForm({ first_name: '', last_name: '', password: '', password_confirmation: '' });
</script>
<template>
    <Head title="Accepter l’invitation" /><AuthLayout
        ><h1 id="auth-title">Rejoindre {{ organization }}</h1>
        <p class="auth-intro">
            Invitation destinée à <strong>{{ email }}</strong
            >.
        </p>
        <div
            v-if="existingUser"
            class="auth-form"
        >
            <p>Connectez-vous avec cette adresse, puis ouvrez à nouveau le lien d’invitation.</p>
            <Link
                href="/login"
                class="ui-button ui-button--primary ui-button--md ui-button--block"
                >Se connecter</Link
            >
        </div>
        <form
            v-else
            class="auth-form"
            @submit.prevent="form.post(`/invitations/${token}`)"
        >
            <div class="form-grid">
                <UiInput
                    v-model="form.first_name"
                    label="Prénom"
                    required
                    :error="form.errors.first_name"
                /><UiInput
                    v-model="form.last_name"
                    label="Nom"
                    required
                    :error="form.errors.last_name"
                />
            </div>
            <UiInput
                v-model="form.password"
                label="Mot de passe"
                type="password"
                required
                :error="form.errors.password"
            /><UiInput
                v-model="form.password_confirmation"
                label="Confirmer"
                type="password"
                required
            /><UiButton
                type="submit"
                block
                :loading="form.processing"
                >Accepter et rejoindre</UiButton
            >
        </form></AuthLayout
    >
</template>
