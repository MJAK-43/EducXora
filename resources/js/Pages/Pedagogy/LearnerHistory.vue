<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import UiAlert from '@/Components/Ui/UiAlert.vue';
import UiBadge from '@/Components/Ui/UiBadge.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import UiEmptyState from '@/Components/Ui/UiEmptyState.vue';
import UiSelect from '@/Components/Ui/UiSelect.vue';
import UiTextarea from '@/Components/Ui/UiTextarea.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';

interface Learner {
    uuid: string;
    name: string;
    status: string;
    initial_level: string;
    current_level: string;
    active_group: { uuid: string; name: string } | null;
}
interface Attempt {
    uuid: string;
    status: string;
    score: number | null;
    question_count: number;
    percentage: string | null;
    suggested_level: string | null;
    validated_level: string | null;
    started_at: string;
}
interface HistoryItem {
    uuid: string;
    from_level: string;
    to_level: string;
    source: string;
    reason: string | null;
    actor: string | null;
    occurred_at: string;
}
const props = defineProps<{
    learner: Learner;
    attempts: Attempt[];
    history: HistoryItem[];
    levels: Array<{ value: string; label: string }>;
    can: { startTest: boolean; updateLevel: boolean };
}>();
const page = usePage();
const flash = page.props.flash as { status?: string } | undefined;
const levelForm = useForm({ level: props.learner.current_level, reason: '' });
function startTest(): void {
    if (window.confirm(`Démarrer un test de 18 questions pour ${props.learner.name} ?`)) {
        router.post(`/pedagogy/learners/${props.learner.uuid}/tests`);
    }
}
function updateLevel(): void {
    levelForm.patch(`/learners/${props.learner.uuid}/level`, { preserveScroll: true });
}
function sourceLabel(source: string): string {
    return (
        {
            placement_test: 'Test validé',
            teacher_evaluation: 'Évaluation enseignant',
            director_override: 'Décision direction',
        }[source] ?? source
    );
}
</script>

<template>
    <Head :title="`Suivi pédagogique de ${learner.name}`" />
    <AppLayout :page-title="`Suivi pédagogique de ${learner.name}`">
        <PageHeader
            eyebrow="Suivi pédagogique"
            :title="learner.name"
            description="Niveau courant, tests de positionnement et historique des décisions."
        >
            <template #actions>
                <Link :href="`/learners/${learner.uuid}`">Dossier apprenant</Link>
                <UiButton
                    v-if="can.startTest && learner.status === 'active'"
                    @click="startTest"
                    >Démarrer un test</UiButton
                >
            </template>
        </PageHeader>
        <UiAlert
            v-if="flash?.status"
            tone="success"
            :title="flash.status"
        />
        <div class="level-overview">
            <UiCard
                ><span class="muted">Niveau initial</span
                ><strong>{{ learner.initial_level }}</strong></UiCard
            >
            <UiCard
                ><span class="muted">Niveau actuel</span
                ><strong>{{ learner.current_level }}</strong></UiCard
            >
            <UiCard
                ><span class="muted">Groupe actif</span
                ><strong>{{ learner.active_group?.name ?? 'Aucun' }}</strong></UiCard
            >
        </div>
        <form
            v-if="can.updateLevel"
            class="level-form"
            @submit.prevent="updateLevel"
        >
            <UiCard>
                <template #header><h2>Évaluation pédagogique manuelle</h2></template>
                <div class="level-form__grid">
                    <UiSelect
                        v-model="levelForm.level"
                        label="Nouveau niveau actuel"
                        :options="levels"
                        required
                        :error="levelForm.errors.level"
                    />
                    <UiTextarea
                        v-model="levelForm.reason"
                        label="Motif pédagogique"
                        required
                        :error="levelForm.errors.reason"
                    />
                </div>
                <UiButton
                    type="submit"
                    :loading="levelForm.processing"
                    >Enregistrer le niveau</UiButton
                >
            </UiCard>
        </form>
        <section
            class="history-section"
            aria-labelledby="attempts-title"
        >
            <h2 id="attempts-title">Tests de positionnement</h2>
            <UiEmptyState
                v-if="!attempts.length"
                title="Aucun test"
                message="Aucun test de positionnement n’a encore été démarré."
            />
            <div
                v-else
                class="data-table-wrap"
            >
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Score</th>
                            <th>Suggestion</th>
                            <th>Validé</th>
                            <th><span class="sr-only">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="attempt in attempts"
                            :key="attempt.uuid"
                        >
                            <td data-label="Date">{{ attempt.started_at }}</td>
                            <td data-label="Statut">
                                <UiBadge
                                    :tone="
                                        attempt.status === 'reviewed'
                                            ? 'success'
                                            : attempt.status === 'completed'
                                              ? 'warning'
                                              : 'info'
                                    "
                                    >{{ attempt.status }}</UiBadge
                                >
                            </td>
                            <td data-label="Score">
                                {{
                                    attempt.score === null
                                        ? '—'
                                        : `${attempt.score}/${attempt.question_count} · ${attempt.percentage}%`
                                }}
                            </td>
                            <td data-label="Suggestion">{{ attempt.suggested_level ?? '—' }}</td>
                            <td data-label="Validé">{{ attempt.validated_level ?? '—' }}</td>
                            <td data-label="Action">
                                <Link :href="`/pedagogy/tests/${attempt.uuid}`">Ouvrir</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <section
            class="history-section"
            aria-labelledby="levels-title"
        >
            <h2 id="levels-title">Historique des niveaux</h2>
            <UiEmptyState
                v-if="!history.length"
                title="Aucun changement"
                message="Le niveau actuel correspond encore au niveau initial."
            />
            <ol
                v-else
                class="level-timeline"
            >
                <li
                    v-for="item in history"
                    :key="item.uuid"
                >
                    <div>
                        <strong>{{ item.from_level }} → {{ item.to_level }}</strong
                        ><UiBadge>{{ sourceLabel(item.source) }}</UiBadge>
                    </div>
                    <p>{{ item.reason ?? 'Suggestion validée sans modification.' }}</p>
                    <small
                        >{{ item.actor ?? 'Utilisateur supprimé' }} · {{ item.occurred_at }}</small
                    >
                </li>
            </ol>
        </section>
    </AppLayout>
</template>

<style scoped>
.level-overview {
    display: grid;
    gap: var(--space-4);
}
.level-overview :deep(.ui-card__body) {
    display: grid;
    gap: var(--space-2);
}
.level-overview strong {
    font-size: var(--font-size-title);
}
.muted {
    color: var(--color-text-secondary);
}
.level-form {
    max-width: 60rem;
    margin-top: var(--space-6);
}
.level-form__grid {
    display: grid;
    gap: var(--space-4);
    margin-bottom: var(--space-4);
}
.history-section {
    margin-top: var(--space-8);
}
.level-timeline {
    display: grid;
    gap: var(--space-4);
    margin: 0;
    padding: 0;
    list-style: none;
}
.level-timeline li {
    border-left: 0.25rem solid var(--color-primary);
    border-radius: var(--radius-md);
    padding: var(--space-4);
    background: var(--color-surface);
}
.level-timeline li div {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: var(--space-3);
}
.level-timeline p {
    margin-bottom: var(--space-2);
}
.level-timeline small {
    color: var(--color-text-secondary);
}
@media (min-width: 48rem) {
    .level-overview {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .level-form__grid {
        grid-template-columns: minmax(10rem, 0.5fr) minmax(0, 2fr);
    }
}
@media (max-width: 47.99rem) {
    .data-table thead {
        display: none;
    }
    .data-table,
    .data-table tbody,
    .data-table tr,
    .data-table td {
        display: block;
    }
    .data-table tr {
        padding: var(--space-3);
        border-bottom: 1px solid var(--color-border);
    }
    .data-table td {
        display: grid;
        grid-template-columns: 7rem 1fr;
        gap: var(--space-3);
        border: 0;
        padding: var(--space-2);
    }
    .data-table td::before {
        content: attr(data-label);
        color: var(--color-text-secondary);
        font-size: var(--font-size-caption);
        font-weight: var(--font-weight-semibold);
    }
}
</style>
