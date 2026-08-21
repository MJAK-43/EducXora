<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiInput from '@/Components/Ui/UiInput.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
const form = useForm({
    organization_name: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});
</script>
<template>
    <Head title="Créer une organisation" />
    <AuthLayout>
        <h1 id="auth-title">Créer votre espace</h1>
        <p class="auth-intro">
            Votre compte administrateur et votre organisation seront prêts ensemble.
        </p>
        <form
            class="auth-form"
            @submit.prevent="form.post('/register')"
        >
            <UiInput
                v-model="form.organization_name"
                label="Nom de l’organisation"
                required
                :error="form.errors.organization_name"
            />
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
                v-model="form.email"
                label="Adresse e-mail"
                type="email"
                autocomplete="email"
                required
                :error="form.errors.email"
            />
            <UiInput
                v-model="form.phone"
                label="Téléphone (facultatif)"
                type="tel"
                autocomplete="tel"
                :error="form.errors.phone"
            />
            <UiInput
                v-model="form.password"
                label="Mot de passe"
                type="password"
                autocomplete="new-password"
                required
                help="12 caractères, majuscule, minuscule, chiffre et symbole."
                :error="form.errors.password"
            />
            <UiInput
                v-model="form.password_confirmation"
                label="Confirmer le mot de passe"
                type="password"
                autocomplete="new-password"
                required
            />
            <UiButton
                type="submit"
                block
                :loading="form.processing"
                >Créer mon espace</UiButton
            >
        </form>
        <p class="auth-footer">Déjà inscrit ? <Link href="/login">Se connecter</Link></p>
    </AuthLayout>
</template>
