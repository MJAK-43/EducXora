<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import UiBadge from '@/Components/Ui/UiBadge.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import UiCheckbox from '@/Components/Ui/UiCheckbox.vue';
import UiInput from '@/Components/Ui/UiInput.vue';
import RoleEditor from '@/Components/Organization/RoleEditor.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
interface Permission {
    id: number;
    name: string;
}
interface Role {
    uuid: string;
    name: string;
    is_system: boolean;
    memberships_count: number;
    permissions: Permission[];
}
defineProps<{ roles: Role[]; permissions: Permission[]; can: Record<string, boolean> }>();
const form = useForm<{ name: string; permissions: string[] }>({ name: '', permissions: [] });
function toggle(name: string, checked: boolean) {
    form.permissions = checked
        ? [...form.permissions, name]
        : form.permissions.filter((item) => item !== name);
}
</script>
<template>
    <Head title="Rôles et permissions" /><AppLayout page-title="Rôles"
        ><PageHeader
            eyebrow="Administration"
            title="Rôles et permissions"
            description="Les rôles système sont protégés; les rôles personnalisés restent limités à cette organisation."
        /><UiCard v-if="can.create && can.assign"
            ><template #header><h2>Nouveau rôle personnalisé</h2></template>
            <form
                class="auth-form"
                @submit.prevent="
                    form.post('/organization/roles', { onSuccess: () => form.reset() })
                "
            >
                <UiInput
                    v-model="form.name"
                    label="Nom du rôle"
                    required
                    :error="form.errors.name"
                />
                <div class="form-grid">
                    <UiCheckbox
                        v-for="permission in permissions"
                        :key="permission.id"
                        :model-value="form.permissions.includes(permission.name)"
                        :label="permission.name"
                        @update:model-value="toggle(permission.name, $event)"
                    />
                </div>
                <UiButton
                    type="submit"
                    :loading="form.processing"
                    >Créer le rôle</UiButton
                >
            </form></UiCard
        >
        <div
            class="content-grid"
            style="margin-top: var(--space-6)"
        >
            <UiCard
                v-for="role in roles"
                :key="role.uuid"
                ><template #header
                    ><div class="auth-row">
                        <h2>{{ role.name }}</h2>
                        <UiBadge :tone="role.is_system ? 'info' : 'neutral'">{{
                            role.is_system ? 'Système' : 'Personnalisé'
                        }}</UiBadge>
                    </div></template
                >
                <p v-if="role.is_system">
                    {{ role.permissions.map((p) => p.name).join(' · ') || 'Aucune permission' }}
                </p>
                <RoleEditor
                    v-else
                    :role="role"
                    :permissions="permissions"
                    :can-update="Boolean(can.update && can.assign)"
                    :can-delete="Boolean(can.delete)"
                />
                <small>{{ role.memberships_count }} attribution(s)</small></UiCard
            >
        </div></AppLayout
    >
</template>
