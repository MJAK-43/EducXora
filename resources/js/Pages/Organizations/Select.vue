<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
defineProps<{ organizations: Array<{ uuid: string; name: string; status: string }> }>();
</script>
<template>
    <Head title="Choisir une organisation" /><AuthLayout
        ><h1 id="auth-title">Choisir une organisation</h1>
        <p class="auth-intro">Sélectionnez l’espace dans lequel vous souhaitez travailler.</p>
        <div class="content-grid">
            <UiCard
                v-for="organization in organizations"
                :key="organization.uuid"
                ><template #header
                    ><strong>{{ organization.name }}</strong></template
                ><UiButton
                    block
                    :disabled="organization.status !== 'active'"
                    @click="
                        router.post('/organizations/select', {
                            organization_uuid: organization.uuid,
                        })
                    "
                    >Ouvrir cet espace</UiButton
                ></UiCard
            >
            <p v-if="!organizations.length">Aucune adhésion active. Contactez un administrateur.</p>
        </div></AuthLayout
    >
</template>
