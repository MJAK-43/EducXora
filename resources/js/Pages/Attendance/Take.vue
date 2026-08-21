<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import UiAlert from '@/Components/Ui/UiAlert.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';

type Status = 'present' | 'absent' | 'excused';
interface Option {
    value: Status;
    label: string;
}
interface Learner {
    uuid: string;
    full_name: string;
    status: Status | null;
}
interface Sheet {
    uuid: string;
    group: { name: string };
    teacher: { name: string; status: Status | null };
    session: { date_label: string; start_time: string; end_time: string };
    learners: Learner[];
}
const props = defineProps<{ sheet: Sheet; options: Option[]; canValidate: boolean }>();
const learnerForm = useForm({
    attendances: props.sheet.learners.map((learner) => ({
        learner_uuid: learner.uuid,
        status: learner.status,
    })),
});
const teacherForm = useForm<{ status: Status | null }>({ status: props.sheet.teacher.status });
const validationForm = useForm({});
function markAllPresent(): void {
    learnerForm.attendances.forEach((attendance) => {
        attendance.status = 'present';
    });
}
function saveLearners(): void {
    learnerForm.patch(`/attendance/${props.sheet.uuid}/draft`, { preserveScroll: true });
}
function saveTeacher(): void {
    teacherForm.patch(`/attendance/${props.sheet.uuid}/teacher`, { preserveScroll: true });
}
function validateSheet(): void {
    if (window.confirm('Valider définitivement cette feuille ?'))
        validationForm.patch(`/attendance/${props.sheet.uuid}/validate`);
}
</script>

<template>
    <Head :title="`Pointage · ${sheet.group.name}`" />
    <AppLayout page-title="Pointage">
        <PageHeader
            eyebrow="Présences"
            :title="sheet.group.name"
            :description="`${sheet.session.date_label} · ${sheet.session.start_time}–${sheet.session.end_time}`"
        >
            <template #actions
                ><Link
                    class="outline-link"
                    href="/attendance"
                    >Retour</Link
                ></template
            >
        </PageHeader>
        <UiAlert
            v-if="Object.keys(learnerForm.errors).length || Object.keys(teacherForm.errors).length"
            tone="danger"
            title="Le pointage contient des erreurs. Vérifiez tous les statuts."
        />
        <div class="take-grid">
            <UiCard>
                <template #header
                    ><div class="card-heading">
                        <h2>Apprenants</h2>
                        <button
                            type="button"
                            @click="markAllPresent"
                        >
                            Tous présents
                        </button>
                    </div></template
                >
                <form @submit.prevent="saveLearners">
                    <fieldset
                        v-for="(learner, index) in sheet.learners"
                        :key="learner.uuid"
                        class="roster-row"
                    >
                        <legend>{{ learner.full_name }}</legend>
                        <label
                            v-for="option in options"
                            :key="option.value"
                            ><!-- @vue-ignore -->
                            <input
                                v-model="learnerForm.attendances[index].status"
                                type="radio"
                                :name="`learner-${learner.uuid}`"
                                :value="option.value"
                            />{{ option.label }}</label
                        >
                    </fieldset>
                    <p
                        v-if="!sheet.learners.length"
                        class="muted"
                    >
                        Aucun apprenant dans le groupe à la date de la séance.
                    </p>
                    <button
                        class="primary-button"
                        type="submit"
                        :disabled="learnerForm.processing"
                    >
                        Enregistrer le brouillon
                    </button>
                </form>
            </UiCard>
            <div class="side-column">
                <UiCard
                    ><template #header><h2>Enseignant</h2></template>
                    <p>{{ sheet.teacher.name }}</p>
                    <form
                        class="teacher-form"
                        @submit.prevent="saveTeacher"
                    >
                        <label
                            v-for="option in options"
                            :key="option.value"
                            ><input
                                v-model="teacherForm.status"
                                type="radio"
                                name="teacher-status"
                                :value="option.value"
                            />{{ option.label }}</label
                        ><button
                            class="primary-button"
                            type="submit"
                            :disabled="teacherForm.processing"
                        >
                            Enregistrer
                        </button>
                    </form></UiCard
                >
                <UiCard
                    ><template #header><h2>Validation</h2></template>
                    <p class="muted">
                        La validation verrouille le pointage. Toute modification ultérieure devient
                        une correction auditée.
                    </p>
                    <button
                        v-if="canValidate"
                        class="validate-button"
                        type="button"
                        :disabled="validationForm.processing"
                        @click="validateSheet"
                    >
                        Valider et verrouiller
                    </button></UiCard
                >
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.take-grid,
.side-column {
    display: grid;
    gap: var(--space-5);
}
.card-heading,
.teacher-form {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
    align-items: center;
    justify-content: space-between;
}
.card-heading button,
.outline-link,
.primary-button,
.validate-button {
    min-height: 2.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 0 var(--space-4);
    background: var(--color-surface);
    color: var(--color-text-primary);
    text-decoration: none;
}
.roster-row {
    display: grid;
    gap: var(--space-2);
    margin: 0;
    padding: var(--space-4) 0;
    border: 0;
    border-bottom: 1px solid var(--color-border);
}
.roster-row legend {
    font-weight: var(--font-weight-semibold);
}
.roster-row label,
.teacher-form label {
    display: flex;
    gap: var(--space-2);
    align-items: center;
    min-height: 2.5rem;
}
.primary-button,
.validate-button {
    margin-top: var(--space-4);
    border-color: var(--color-primary);
    background: var(--color-primary);
    color: white;
}
.validate-button {
    width: 100%;
}
.muted {
    color: var(--color-text-secondary);
}
@media (min-width: 48rem) {
    .roster-row {
        grid-template-columns: minmax(12rem, 1fr) repeat(3, auto);
        align-items: center;
    }
    .roster-row legend {
        grid-column: auto;
    }
    .take-grid {
        grid-template-columns: minmax(0, 2fr) minmax(16rem, 0.8fr);
    }
}
@media (max-width: 39.99rem) {
    .teacher-form {
        align-items: stretch;
        flex-direction: column;
    }
    .primary-button {
        width: 100%;
    }
}
</style>
