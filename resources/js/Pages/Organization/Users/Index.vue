<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import UiBadge from '@/Components/Ui/UiBadge.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import UiInput from '@/Components/Ui/UiInput.vue';
import UiSelect from '@/Components/Ui/UiSelect.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
interface Member {
    id: number;
    uuid: string;
    name: string;
    email: string;
    status: string;
    roles: string[];
    role_ids: number[];
}
const props = defineProps<{
    memberships: { data: Member[] };
    roles: Array<{ id: number; name: string }>;
    can: { invite: boolean; update: boolean };
}>();
const invite = useForm({ email: '', role_id: String(props.roles[0]?.id ?? '') });
const options = props.roles.map((role) => ({ value: String(role.id), label: role.name }));
function toggle(member: Member) {
    router.patch(
        `/organization/users/${member.id}`,
        { status: member.status === 'active' ? 'inactive' : 'active', roles: member.role_ids },
        { preserveScroll: true },
    );
}
function changeRole(member: Member, event: Event) {
    router.patch(
        `/organization/users/${member.id}`,
        { status: member.status, roles: [Number((event.target as HTMLSelectElement).value)] },
        { preserveScroll: true },
    );
}
</script>
<template>
    <Head title="Utilisateurs" /><AppLayout page-title="Utilisateurs"
        ><PageHeader
            eyebrow="Administration"
            title="Utilisateurs et invitations"
            description="Les rôles sont attribués dans le périmètre exclusif de cette organisation."
        /><UiCard v-if="can.invite"
            ><template #header><h2>Inviter un membre</h2></template>
            <form
                class="form-grid"
                @submit.prevent="
                    invite.post('/organization/invitations', {
                        onSuccess: () => invite.reset('email'),
                    })
                "
            >
                <UiInput
                    v-model="invite.email"
                    label="Adresse e-mail"
                    type="email"
                    required
                    :error="invite.errors.email"
                /><UiSelect
                    v-model="invite.role_id"
                    label="Rôle"
                    :options="options"
                    required
                    :error="invite.errors.role_id"
                /><UiButton
                    type="submit"
                    :loading="invite.processing"
                    >Envoyer l’invitation</UiButton
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
                        <th>Membre</th>
                        <th>Rôles</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="member in memberships.data"
                        :key="member.id"
                    >
                        <td>
                            <strong>{{ member.name }}</strong
                            ><br /><span>{{ member.email }}</span>
                        </td>
                        <td>
                            <select
                                v-if="can.update"
                                class="topbar-organization"
                                :value="member.role_ids[0]"
                                aria-label="Rôle du membre"
                                @change="changeRole(member, $event)"
                            >
                                <option
                                    v-for="role in roles"
                                    :key="role.id"
                                    :value="role.id"
                                >
                                    {{ role.name }}
                                </option>
                            </select>
                            <span v-else>{{ member.roles.join(', ') }}</span>
                        </td>
                        <td>
                            <UiBadge :tone="member.status === 'active' ? 'success' : 'neutral'">{{
                                member.status
                            }}</UiBadge>
                        </td>
                        <td>
                            <UiButton
                                v-if="can.update"
                                size="sm"
                                variant="outline"
                                @click="toggle(member)"
                                >{{
                                    member.status === 'active' ? 'Désactiver' : 'Réactiver'
                                }}</UiButton
                            >
                        </td>
                    </tr>
                    <tr v-if="!memberships.data.length">
                        <td colspan="4">Aucun membre.</td>
                    </tr>
                </tbody>
            </table>
        </div></AppLayout
    >
</template>
