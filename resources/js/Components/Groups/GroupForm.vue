<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import UiInput from '@/Components/Ui/UiInput.vue';
import UiSelect from '@/Components/Ui/UiSelect.vue';

interface Option {
    value: string;
    label: string;
}
interface GroupValue {
    uuid: string;
    name: string;
    language: string;
    level: string;
    capacity: number;
    members_count: number;
    teacher_membership_uuid: string;
}
const props = defineProps<{
    group?: GroupValue;
    options: { levels: Option[]; languages: Option[]; teachers: Option[] };
}>();
const form = useForm({
    name: props.group?.name ?? '',
    language: props.group?.language ?? 'de',
    level: props.group?.level ?? 'A1',
    capacity: props.group?.capacity ?? 20,
    teacher_membership_uuid: props.group?.teacher_membership_uuid ?? '',
});
function errorFor(key: string): string | undefined {
    return (form.errors as Record<string, string | undefined>)[key];
}
function submit(): void {
    if (props.group) form.patch(`/groups/${props.group.uuid}`, { preserveScroll: true });
    else form.post('/groups', { preserveScroll: true });
}
</script>

<template>
    <form
        class="phase-form"
        novalidate
        @submit.prevent="submit"
    >
        <UiCard>
            <template #header><h2>Configuration du groupe</h2></template>
            <div class="phase-form__grid">
                <UiInput
                    v-model="form.name"
                    label="Nom du groupe"
                    required
                    :error="form.errors.name"
                />
                <UiSelect
                    v-model="form.teacher_membership_uuid"
                    label="Enseignant responsable"
                    required
                    placeholder="Choisir un enseignant"
                    :options="options.teachers"
                    :error="form.errors.teacher_membership_uuid"
                />
                <UiSelect
                    v-model="form.language"
                    label="Langue"
                    required
                    :options="options.languages"
                    :error="form.errors.language"
                />
                <UiSelect
                    v-model="form.level"
                    label="Niveau CECRL"
                    required
                    :options="options.levels"
                    :error="form.errors.level"
                />
                <UiInput
                    v-model="form.capacity"
                    label="Capacité"
                    type="number"
                    required
                    :help="
                        group
                            ? `${group.members_count} apprenant(s) actuellement affecté(s)`
                            : 'Nombre maximal d’apprenants'
                    "
                    :error="form.errors.capacity"
                />
            </div>
            <p
                v-if="errorFor('group')"
                class="ui-field__error"
                role="alert"
            >
                {{ errorFor('group') }}
            </p>
        </UiCard>
        <div class="phase-form__actions">
            <Link
                :href="group ? `/groups/${group.uuid}` : '/groups'"
                class="phase-link"
                >Annuler</Link
            >
            <UiButton
                type="submit"
                :loading="form.processing"
                >{{ group ? 'Enregistrer' : 'Créer le groupe' }}</UiButton
            >
        </div>
    </form>
</template>

<style scoped>
.phase-form {
    display: grid;
    max-width: 56rem;
    gap: var(--space-6);
}
.phase-form__grid {
    display: grid;
    gap: var(--space-5);
}
.phase-form__actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--space-4);
}
.phase-link {
    color: var(--color-text-secondary);
}
@media (min-width: 40rem) {
    .phase-form__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
