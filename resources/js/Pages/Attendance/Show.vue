<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import UiBadge from '@/Components/Ui/UiBadge.vue';
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
    learner_uuid: string;
    full_name: string;
    status: Status;
    status_label: string;
}
interface Correction {
    uuid: string;
    subject: string;
    before_label: string;
    after_label: string;
    reason: string;
    actor: string;
    created_at_label: string;
}
interface Sheet {
    uuid: string;
    status: 'draft' | 'validated';
    group: { name: string };
    teacher: { name: string; status: Status | null; status_label: string };
    session: { date_label: string; start_time: string; end_time: string };
    learners: Learner[];
    validated_by: string | null;
    validated_at_label: string | null;
    corrections: Correction[];
}
const props = defineProps<{
    sheet: Sheet;
    options: Option[];
    can: { take: boolean; validate: boolean; correct: boolean };
}>();
const correction = useForm<{ subject: string; status: Status; reason: string }>({
    subject: '',
    status: 'present',
    reason: '',
});
function openCorrection(subject: string, status: Status): void {
    correction.subject = subject;
    correction.status = status;
    correction.reason = '';
}
function submitCorrection(): void {
    const url =
        correction.subject === 'teacher'
            ? `/attendance/${props.sheet.uuid}/teacher/correct`
            : `/attendance/${props.sheet.uuid}/learners/${correction.subject}/correct`;
    correction.patch(url, { preserveScroll: true, onSuccess: () => correction.reset() });
}
function tone(status: Status | null): 'success' | 'danger' | 'warning' | 'neutral' {
    return status === 'present'
        ? 'success'
        : status === 'absent'
          ? 'danger'
          : status === 'excused'
            ? 'warning'
            : 'neutral';
}
</script>

<template>
    <Head :title="`Présences · ${sheet.group.name}`" />
    <AppLayout page-title="Feuille de présence">
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
                ><Link
                    v-if="sheet.status === `draft` && can.take"
                    class="primary-link"
                    :href="`/attendance/${sheet.uuid}/take`"
                    >Continuer le pointage</Link
                ></template
            >
        </PageHeader>
        <div class="summary-grid">
            <UiCard
                ><template #header><h2>Statut</h2></template
                ><UiBadge :tone="sheet.status === `validated` ? `success` : `warning`">{{
                    sheet.status === 'validated' ? 'Validé et verrouillé' : 'Brouillon'
                }}</UiBadge>
                <p
                    v-if="sheet.validated_at_label"
                    class="muted"
                >
                    {{ sheet.validated_at_label }} · {{ sheet.validated_by }}
                </p></UiCard
            >
            <UiCard
                ><template #header><h2>Enseignant</h2></template>
                <p>
                    <strong>{{ sheet.teacher.name }}</strong>
                </p>
                <UiBadge :tone="tone(sheet.teacher.status)">{{
                    sheet.teacher.status_label
                }}</UiBadge
                ><button
                    v-if="can.correct && sheet.teacher.status"
                    class="text-button"
                    type="button"
                    @click="openCorrection(`teacher`, sheet.teacher.status)"
                >
                    Corriger
                </button></UiCard
            >
        </div>
        <UiCard class="roster-card"
            ><template #header><h2>Apprenants</h2></template>
            <div class="roster">
                <article
                    v-for="learner in sheet.learners"
                    :key="learner.uuid"
                >
                    <Link :href="`/learners/${learner.learner_uuid}/attendance`"
                        ><strong>{{ learner.full_name }}</strong></Link
                    >
                    <div>
                        <UiBadge :tone="tone(learner.status)">{{ learner.status_label }}</UiBadge
                        ><button
                            v-if="can.correct"
                            class="text-button"
                            type="button"
                            @click="openCorrection(learner.uuid, learner.status)"
                        >
                            Corriger
                        </button>
                    </div>
                </article>
                <p
                    v-if="!sheet.learners.length"
                    class="muted"
                >
                    Aucun apprenant.
                </p>
            </div></UiCard
        >
        <UiCard
            v-if="correction.subject"
            class="correction-form"
            ><template #header><h2>Correction auditée</h2></template>
            <form @submit.prevent="submitCorrection">
                <label
                    >Nouveau statut<select v-model="correction.status">
                        <option
                            v-for="option in options"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select></label
                ><label
                    >Motif<textarea
                        v-model="correction.reason"
                        required
                        maxlength="500"
                        rows="3"
                    />
                </label>
                <p
                    v-if="correction.errors.reason"
                    class="error"
                >
                    {{ correction.errors.reason }}
                </p>
                <div class="actions">
                    <button
                        type="button"
                        @click="correction.reset()"
                    >
                        Annuler</button
                    ><button
                        class="primary-link"
                        type="submit"
                        :disabled="correction.processing"
                    >
                        Enregistrer la correction
                    </button>
                </div>
            </form></UiCard
        >
        <UiCard
            v-if="sheet.corrections.length"
            class="history"
            ><template #header><h2>Historique des corrections</h2></template>
            <ol>
                <li
                    v-for="item in sheet.corrections"
                    :key="item.uuid"
                >
                    <strong>{{ item.subject }}</strong> · {{ item.before_label }} →
                    {{ item.after_label }}
                    <p>{{ item.reason }}</p>
                    <small>{{ item.created_at_label }} · {{ item.actor }}</small>
                </li>
            </ol></UiCard
        >
    </AppLayout>
</template>

<style scoped>
.summary-grid {
    display: grid;
    gap: var(--space-5);
}
.roster-card,
.correction-form,
.history {
    margin-top: var(--space-5);
}
.outline-link,
.primary-link,
.text-button,
.actions button {
    display: inline-flex;
    align-items: center;
    min-height: 2.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 0 var(--space-3);
    background: var(--color-surface);
    color: var(--color-text-primary);
    text-decoration: none;
}
.primary-link {
    border-color: var(--color-primary);
    background: var(--color-primary);
    color: white;
}
.text-button {
    min-height: 2rem;
    margin-left: var(--space-2);
    border: 0;
    color: var(--color-primary);
}
.roster article {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3) 0;
    border-bottom: 1px solid var(--color-border);
}
.roster a {
    color: var(--color-text-primary);
}
.muted,
.history small {
    color: var(--color-text-secondary);
}
.correction-form form,
.correction-form label {
    display: grid;
    gap: var(--space-3);
}
.correction-form label {
    gap: var(--space-1);
}
select,
textarea {
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: var(--space-2);
    font: inherit;
}
.actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-3);
}
.error {
    color: var(--color-danger);
}
.history li {
    padding: var(--space-3) 0;
}
.history p {
    margin: var(--space-1) 0;
}
@media (min-width: 48rem) {
    .summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 39.99rem) {
    .roster article,
    .actions {
        align-items: stretch;
        flex-direction: column;
    }
    .roster article > div {
        display: flex;
        justify-content: space-between;
    }
}
</style>
