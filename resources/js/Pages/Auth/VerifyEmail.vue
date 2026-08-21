<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import UiAlert from '@/Components/Ui/UiAlert.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
defineProps<{ status?: string }>();
</script>
<template>
    <Head title="Vérifier votre e-mail" /><AuthLayout
        ><h1 id="auth-title">Vérifiez votre boîte de réception</h1>
        <p class="auth-intro">
            Cliquez sur le lien envoyé à votre adresse pour activer l’accès à votre organisation.
        </p>
        <UiAlert
            v-if="status === 'verification-link-sent'"
            tone="success"
            title="Lien envoyé"
            >Un nouveau lien vient d’être envoyé.</UiAlert
        >
        <div class="auth-form">
            <UiButton
                block
                @click="router.post('/email/verification-notification')"
                >Renvoyer le lien</UiButton
            ><UiButton
                variant="ghost"
                block
                @click="router.post('/logout')"
                >Se déconnecter</UiButton
            >
        </div></AuthLayout
    >
</template>
