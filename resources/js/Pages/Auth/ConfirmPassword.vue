<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiInput from '@/Components/Ui/UiInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
const form = useForm({ password: '' });
</script>
<template>
    <Head title="Confirmer le mot de passe" /><AuthLayout
        ><h1 id="auth-title">Confirmez votre identité</h1>
        <p class="auth-intro">Cette action sensible nécessite votre mot de passe.</p>
        <form
            class="auth-form"
            @submit.prevent="form.post('/confirm-password')"
        >
            <UiInput
                v-model="form.password"
                label="Mot de passe"
                type="password"
                required
                :error="form.errors.password"
            /><UiButton
                type="submit"
                block
                :loading="form.processing"
                >Confirmer</UiButton
            >
        </form></AuthLayout
    >
</template>
