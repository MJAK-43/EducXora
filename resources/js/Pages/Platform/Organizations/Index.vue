<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import UiBadge from '@/Components/Ui/UiBadge.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import UiInput from '@/Components/Ui/UiInput.vue';
import OrganizationEditor from '@/Components/Platform/OrganizationEditor.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
interface Organization {
    uuid: string;
    name: string;
    email?: string;
    status: string;
    memberships_count: number;
    phone?: string;
}
defineProps<{ organizations: { data: Organization[] } }>();
const form = useForm({ name: '', email: '', phone: '' });
function toggle(org: Organization) {
    router.patch(`/platform/organizations/${org.uuid}/status`, {
        status: org.status === 'active' ? 'suspended' : 'active',
    });
}
</script>
<template>
    <Head title="Organisations" /><AppLayout page-title="Plateforme"
        ><PageHeader
            eyebrow="Super Admin"
            title="Organisations"
            description="Création, suspension et réactivation des tenants EduXora."
        /><UiCard
            ><template #header><h2>Créer une organisation</h2></template>
            <form
                class="form-grid"
                @submit.prevent="
                    form.post('/platform/organizations', { onSuccess: () => form.reset() })
                "
            >
                <UiInput
                    v-model="form.name"
                    label="Nom"
                    required
                    :error="form.errors.name"
                /><UiInput
                    v-model="form.email"
                    label="E-mail"
                    type="email"
                    :error="form.errors.email"
                /><UiInput
                    v-model="form.phone"
                    label="Téléphone"
                /><UiButton
                    type="submit"
                    :loading="form.processing"
                    >Créer</UiButton
                >
            </form></UiCard
        >
        <div
            class="data-table-wrap"
            style="margin-top: var(--space-6)"
        >
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Organisation</th>
                        <th>Membres</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="org in organizations.data"
                        :key="org.uuid"
                    >
                        <td><OrganizationEditor :organization="org" /></td>
                        <td>{{ org.memberships_count }}</td>
                        <td>
                            <UiBadge :tone="org.status === 'active' ? 'success' : 'warning'">{{
                                org.status
                            }}</UiBadge>
                        </td>
                        <td>
                            <UiButton
                                size="sm"
                                :variant="org.status === 'active' ? 'danger' : 'outline'"
                                @click="toggle(org)"
                                >{{ org.status === 'active' ? 'Suspendre' : 'Réactiver' }}</UiButton
                            >
                        </td>
                    </tr>
                </tbody>
            </table>
        </div></AppLayout
    >
</template>
