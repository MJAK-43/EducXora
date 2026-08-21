<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ShieldCheck, Users, Settings } from '@lucide/vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import UiBadge from '@/Components/Ui/UiBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
defineProps<{
    organization: { uuid: string; name: string; status: string };
    permissions: string[];
}>();
</script>
<template>
    <Head title="Tableau de bord" /><AppLayout page-title="Tableau de bord"
        ><PageHeader
            eyebrow="Phase 2"
            :title="organization.name"
            description="Identité, organisation et accès sont opérationnels."
        />
        <div class="foundation-grid">
            <UiCard
                ><div class="foundation-status">
                    <span class="foundation-status__icon"><ShieldCheck :size="21" /></span>
                    <h2>Accès sécurisé</h2>
                    <p>Votre session, votre adhésion et vos permissions ont été vérifiées.</p>
                    <UiBadge tone="success">{{ organization.status }}</UiBadge>
                </div></UiCard
            ><UiCard
                ><div class="foundation-status">
                    <span class="foundation-status__icon"><Users :size="21" /></span>
                    <h2>Équipe</h2>
                    <p>Invitez et gérez les membres autorisés de votre organisation.</p>
                    <Link
                        v-if="permissions.includes('users.view')"
                        href="/organization/users"
                        >Gérer les utilisateurs</Link
                    >
                </div></UiCard
            ><UiCard
                ><div class="foundation-status">
                    <span class="foundation-status__icon"><Settings :size="21" /></span>
                    <h2>Organisation</h2>
                    <p>Configurez les informations propres à votre structure.</p>
                    <Link
                        v-if="permissions.includes('organization.view')"
                        href="/organization/settings"
                        >Ouvrir les réglages</Link
                    >
                </div></UiCard
            >
        </div></AppLayout
    >
</template>
