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
interface SessionValue {
    uuid: string;
    group_uuid: string;
    teacher_membership_uuid: string;
    room: string | null;
    date: string;
    start_time: string;
    end_time: string;
}
const props = defineProps<{
    session?: SessionValue;
    options: { groups: Option[]; teachers: Option[] };
    initialDate?: string;
}>();
const form = useForm({
    group_uuid: props.session?.group_uuid ?? '',
    teacher_membership_uuid: props.session?.teacher_membership_uuid ?? '',
    room: props.session?.room ?? '',
    date: props.session?.date ?? props.initialDate ?? '',
    start_time: props.session?.start_time ?? '09:00',
    end_time: props.session?.end_time ?? '10:30',
});
function errorFor(key: string): string | undefined {
    return (form.errors as Record<string, string | undefined>)[key];
}
function submit(): void {
    if (props.session) form.patch(`/schedule/${props.session.uuid}`, { preserveScroll: true });
    else form.post('/schedule', { preserveScroll: true });
}
</script>

<template>
    <form
        class="session-form"
        novalidate
        @submit.prevent="submit"
    >
        <UiCard>
            <template #header><h2>Créneau pédagogique</h2></template>
            <div class="session-form__grid">
                <UiSelect
                    v-model="form.group_uuid"
                    label="Groupe"
                    required
                    placeholder="Choisir un groupe"
                    :options="options.groups"
                    :error="form.errors.group_uuid"
                />
                <UiSelect
                    v-model="form.teacher_membership_uuid"
                    label="Enseignant"
                    required
                    placeholder="Choisir un enseignant"
                    :options="options.teachers"
                    :error="form.errors.teacher_membership_uuid"
                />
                <UiInput
                    v-model="form.room"
                    label="Salle (facultatif)"
                    :error="form.errors.room"
                />
                <UiInput
                    v-model="form.date"
                    label="Date"
                    type="date"
                    required
                    :error="form.errors.date"
                />
                <UiInput
                    v-model="form.start_time"
                    label="Heure de début"
                    type="time"
                    required
                    :error="form.errors.start_time ?? errorFor('starts_at')"
                />
                <UiInput
                    v-model="form.end_time"
                    label="Heure de fin"
                    type="time"
                    required
                    :error="form.errors.end_time"
                />
            </div>
            <p
                v-if="errorFor('session')"
                class="ui-field__error"
                role="alert"
            >
                {{ errorFor('session') }}
            </p>
        </UiCard>
        <div class="session-form__actions">
            <Link
                href="/schedule"
                class="session-link"
                >Annuler</Link
            >
            <UiButton
                type="submit"
                :loading="form.processing"
                >{{ session ? 'Enregistrer' : 'Créer la séance' }}</UiButton
            >
        </div>
    </form>
</template>

<style scoped>
.session-form {
    display: grid;
    max-width: 56rem;
    gap: var(--space-6);
}
.session-form__grid {
    display: grid;
    gap: var(--space-5);
}
.session-form__actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--space-4);
}
.session-link {
    color: var(--color-text-secondary);
}
@media (min-width: 40rem) {
    .session-form__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
