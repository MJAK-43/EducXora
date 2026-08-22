<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiSelect from '@/Components/Ui/UiSelect.vue';

interface Member {
    uuid: string;
    full_name: string;
    phone: string;
    assigned_at_label: string;
}
interface Candidate {
    value: string;
    label: string;
    phone: string;
}
const props = defineProps<{
    groupUuid: string;
    members: Member[];
    candidates: Candidate[];
    disabled?: boolean;
}>();
const form = useForm({ learner_uuid: '' });
function attach(): void {
    form.post(`/groups/${props.groupUuid}/learners`, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
function detach(member: Member): void {
    if (window.confirm(`Retirer ${member.full_name} du groupe ?`))
        router.delete(`/groups/${props.groupUuid}/learners/${member.uuid}`, {
            preserveScroll: true,
        });
}
</script>

<template>
    <div class="member-manager">
        <form
            v-if="!disabled"
            class="member-manager__add"
            @submit.prevent="attach"
        >
            <UiSelect
                v-model="form.learner_uuid"
                label="Ajouter un apprenant compatible"
                required
                placeholder="Choisir un apprenant"
                :options="candidates"
                :error="form.errors.learner_uuid"
            />
            <UiButton
                type="submit"
                :loading="form.processing"
                :disabled="!form.learner_uuid"
                >Ajouter</UiButton
            >
        </form>
        <p
            v-if="!candidates.length && !disabled"
            class="muted"
        >
            Aucun apprenant actif, libre et compatible n’est disponible.
        </p>
        <div
            v-if="members.length"
            class="member-manager__list"
        >
            <article
                v-for="member in members"
                :key="member.uuid"
                class="member-card"
            >
                <div>
                    <Link :href="`/learners/${member.uuid}/pedagogy`"
                        ><strong>{{ member.full_name }}</strong></Link
                    ><span>{{ member.phone }} · affecté le {{ member.assigned_at_label }}</span>
                </div>
                <button
                    v-if="!disabled"
                    type="button"
                    class="text-button"
                    @click="detach(member)"
                >
                    Retirer
                </button>
            </article>
        </div>
        <p
            v-else
            class="muted"
        >
            Aucun apprenant dans ce groupe.
        </p>
    </div>
</template>

<style scoped>
.member-manager {
    display: grid;
    gap: var(--space-5);
}
.member-manager__add {
    display: grid;
    align-items: end;
    gap: var(--space-3);
}
.member-manager__list {
    display: grid;
    gap: var(--space-3);
}
.member-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-4);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: var(--space-4);
}
.member-card span {
    display: block;
    margin-top: var(--space-1);
    color: var(--color-text-secondary);
    font-size: var(--font-size-caption);
}
.text-button {
    border: 0;
    background: none;
    color: var(--color-danger);
    text-decoration: underline;
}
.muted {
    color: var(--color-text-secondary);
}
@media (min-width: 40rem) {
    .member-manager__add {
        grid-template-columns: minmax(0, 1fr) auto;
    }
}
</style>
