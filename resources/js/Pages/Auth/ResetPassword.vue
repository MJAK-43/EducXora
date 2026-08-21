<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiInput from '@/Components/Ui/UiInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
const props = defineProps<{ token: string; email: string }>();
const form = useForm({
    token: props.token,
    email: props.email ?? '',
    password: '',
    password_confirmation: '',
});
</script>
<template>
    <Head title="Nouveau mot de passe" /><AuthLayout
        ><h1 id="auth-title">Choisir un nouveau mot de passe</h1>
        <form
            class="auth-form"
            @submit.prevent="form.post('/reset-password')"
        >
            <UiInput
                v-model="form.email"
                label="Adresse e-mail"
                type="email"
                required
                :error="form.errors.email"
            /><UiInput
                v-model="form.password"
                label="Nouveau mot de passe"
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
                >Réinitialiser</UiButton
            >
        </form></AuthLayout
    >
</template>
