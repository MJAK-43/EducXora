<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import UiAlert from '@/Components/Ui/UiAlert.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiInput from '@/Components/Ui/UiInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
defineProps<{ status?: string }>();
const form = useForm({ email: '' });
</script>
<template>
    <Head title="Mot de passe oublié" /><AuthLayout
        ><h1 id="auth-title">Réinitialiser le mot de passe</h1>
        <p class="auth-intro">Nous vous enverrons un lien sécurisé si le compte existe.</p>
        <UiAlert
            v-if="status"
            tone="success"
            title="Demande prise en compte"
            >{{ status }}</UiAlert
        >
        <form
            class="auth-form"
            @submit.prevent="form.post('/forgot-password')"
        >
            <UiInput
                v-model="form.email"
                label="Adresse e-mail"
                type="email"
                required
                :error="form.errors.email"
            /><UiButton
                type="submit"
                block
                :loading="form.processing"
                >Envoyer le lien</UiButton
            >
        </form>
        <p class="auth-footer"><Link href="/login">Retour à la connexion</Link></p></AuthLayout
    >
</template>
