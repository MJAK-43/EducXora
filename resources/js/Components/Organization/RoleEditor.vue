<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiCheckbox from '@/Components/Ui/UiCheckbox.vue';
import UiInput from '@/Components/Ui/UiInput.vue';

interface Permission {
    id: number;
    name: string;
}
const props = defineProps<{
    role: { uuid: string; name: string; permissions: Permission[]; memberships_count: number };
    permissions: Permission[];
    canUpdate: boolean;
    canDelete: boolean;
}>();
const form = useForm({
    name: props.role.name,
    permissions: props.role.permissions.map((item) => item.name),
});
function toggle(name: string, checked: boolean) {
    form.permissions = checked
        ? [...form.permissions, name]
        : form.permissions.filter((item) => item !== name);
}
function remove() {
    if (window.confirm(`Supprimer le rôle ${props.role.name} ?`))
        router.delete(`/organization/roles/${props.role.uuid}`, { preserveScroll: true });
}
</script>

<template>
    <form
        class="auth-form"
        @submit.prevent="form.patch(`/organization/roles/${role.uuid}`, { preserveScroll: true })"
    >
        <UiInput
            v-model="form.name"
            label="Nom du rôle"
            :disabled="!canUpdate"
            :error="form.errors.name"
        />
        <div class="form-grid">
            <UiCheckbox
                v-for="permission in permissions"
                :key="permission.id"
                :model-value="form.permissions.includes(permission.name)"
                :label="permission.name"
                :disabled="!canUpdate"
                @update:model-value="toggle(permission.name, $event)"
            />
        </div>
        <div class="inline-actions">
            <UiButton
                v-if="canUpdate"
                type="submit"
                size="sm"
                :loading="form.processing"
                >Enregistrer</UiButton
            ><UiButton
                v-if="canDelete"
                type="button"
                size="sm"
                variant="danger"
                :disabled="role.memberships_count > 0"
                @click="remove"
                >Supprimer</UiButton
            >
        </div>
    </form>
</template>
