<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import UiAlert from '@/Components/Ui/UiAlert.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiCheckbox from '@/Components/Ui/UiCheckbox.vue';
import UiInput from '@/Components/Ui/UiInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';

defineProps<{ status?: string }>();
const form = useForm({ email: '', password: '', remember: false });
</script>

<template>
    <Head title="Connexion" />
    <AuthLayout>
        <h1 id="auth-title">Bon retour parmi nous</h1>
        <p class="auth-intro">Connectez-vous à votre espace de gestion EduXora.</p>
        <UiAlert
            v-if="status"
            tone="success"
            title="Information"
            >{{ status }}</UiAlert
        >
        <form
            class="auth-form"
            @submit.prevent="form.post('/login')"
        >
            <UiInput
                v-model="form.email"
                label="Adresse e-mail"
                type="email"
                autocomplete="email"
                required
                :error="form.errors.email"
            />
            <UiInput
                v-model="form.password"
                label="Mot de passe"
                type="password"
                autocomplete="current-password"
                required
                :error="form.errors.password"
            />
            <div class="auth-row">
                <UiCheckbox
                    v-model="form.remember"
                    label="Se souvenir de moi"
                /><Link href="/forgot-password">Mot de passe oublié ?</Link>
            </div>
            <UiButton
                type="submit"
                block
                :loading="form.processing"
                >Se connecter</UiButton
            >
        </form>
        <p class="auth-footer">
            Nouveau sur EduXora ? <Link href="/register">Créer une organisation</Link>
        </p>
    </AuthLayout>
</template>
