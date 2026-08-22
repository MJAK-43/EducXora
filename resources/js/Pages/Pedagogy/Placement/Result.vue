<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import UiAlert from '@/Components/Ui/UiAlert.vue';
import UiBadge from '@/Components/Ui/UiBadge.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import UiSelect from '@/Components/Ui/UiSelect.vue';
import UiTextarea from '@/Components/Ui/UiTextarea.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';

interface Attempt {
    uuid: string;
    status: string;
    learner: { uuid: string; name: string; current_level: string };
    raw_score: number;
    question_count: number;
    percentage: string;
    scoring_version: string;
    suggested_level: string;
    suggested_group: { uuid: string; name: string } | null;
    validated_level: string | null;
    validated_group: { uuid: string; name: string } | null;
    review_reason: string | null;
    completed_at: string;
    reviewed_at: string | null;
    reviewer: string | null;
}
interface GroupOption {
    uuid: string;
    name: string;
    level: string;
    language: string;
    capacity: number;
    active_count: number;
    available: boolean;
}
const props = defineProps<{
    attempt: Attempt;
    groups: GroupOption[];
    levels: Array<{ value: string; label: string }>;
    can: { review: boolean };
}>();
const page = usePage();
const flash = page.props.flash as { status?: string } | undefined;
const reviewForm = useForm({
    validated_level: props.attempt.validated_level ?? props.attempt.suggested_level,
    group_uuid: props.attempt.validated_group?.uuid ?? props.attempt.suggested_group?.uuid ?? '',
    reason: props.attempt.review_reason ?? '',
});
const groupOptions = computed(() => [
    { value: '', label: 'Aucun groupe pour le moment' },
    ...props.groups
        .filter((group) => group.level === reviewForm.validated_level && group.language === 'de')
        .map((group) => ({
            value: group.uuid,
            label: `${group.name} · ${group.active_count}/${group.capacity}${group.available ? '' : ' · complet'}`,
            disabled: !group.available && group.uuid !== props.attempt.suggested_group?.uuid,
        })),
]);
function review(): void {
    reviewForm.patch(`/pedagogy/tests/${props.attempt.uuid}/review`, { preserveScroll: true });
}
function errorFor(key: string): string | undefined {
    return (reviewForm.errors as Record<string, string | undefined>)[key];
}
</script>

<template>
    <Head :title="`Résultat de ${attempt.learner.name}`" />
    <AppLayout :page-title="`Résultat de ${attempt.learner.name}`">
        <PageHeader
            eyebrow="Test de positionnement"
            :title="attempt.status === 'reviewed' ? 'Décision validée' : 'Suggestion à valider'"
            :description="attempt.learner.name"
        >
            <template #actions
                ><Link :href="`/learners/${attempt.learner.uuid}/pedagogy`"
                    >Historique pédagogique</Link
                ></template
            >
        </PageHeader>
        <UiAlert
            v-if="flash?.status"
            tone="success"
            :title="flash.status"
        />
        <UiAlert
            v-if="attempt.status !== 'reviewed'"
            tone="warning"
            title="Suggestion non définitive"
        >
            Le score automatise une proposition. Seule la validation humaine fixe le niveau et le
            groupe.
        </UiAlert>
        <div class="result-grid">
            <UiCard>
                <template #header><h2>Score serveur</h2></template>
                <p class="result-score">{{ attempt.raw_score }} / {{ attempt.question_count }}</p>
                <p>{{ attempt.percentage }} % · barème {{ attempt.scoring_version }}</p>
            </UiCard>
            <UiCard>
                <template #header><h2>Suggestion</h2></template>
                <p>
                    <UiBadge tone="info">{{ attempt.suggested_level }}</UiBadge>
                </p>
                <p>{{ attempt.suggested_group?.name ?? 'Aucun groupe disponible' }}</p>
            </UiCard>
            <UiCard v-if="attempt.status === 'reviewed'">
                <template #header><h2>Décision humaine</h2></template>
                <p>
                    <UiBadge tone="success">{{ attempt.validated_level }}</UiBadge>
                </p>
                <p>{{ attempt.validated_group?.name ?? 'Aucun groupe retenu' }}</p>
                <p class="muted">{{ attempt.reviewer }} · {{ attempt.reviewed_at }}</p>
                <p v-if="attempt.review_reason">{{ attempt.review_reason }}</p>
            </UiCard>
        </div>
        <form
            v-if="can.review && attempt.status === 'completed'"
            class="review-form"
            @submit.prevent="review"
        >
            <UiCard>
                <template #header><h2>Validation de la direction</h2></template>
                <div class="review-form__grid">
                    <UiSelect
                        v-model="reviewForm.validated_level"
                        label="Niveau retenu"
                        :options="levels"
                        required
                        :error="reviewForm.errors.validated_level"
                    />
                    <UiSelect
                        v-model="reviewForm.group_uuid"
                        label="Groupe retenu"
                        :options="groupOptions"
                        :error="reviewForm.errors.group_uuid"
                    />
                </div>
                <UiTextarea
                    v-model="reviewForm.reason"
                    label="Justification"
                    help="Obligatoire si la décision diffère de la suggestion automatique."
                    :error="reviewForm.errors.reason"
                />
                <UiAlert
                    v-if="errorFor('test')"
                    tone="danger"
                    :title="errorFor('test') ?? 'Erreur de validation'"
                />
                <UiButton
                    type="submit"
                    :loading="reviewForm.processing"
                    >Valider la décision</UiButton
                >
            </UiCard>
        </form>
    </AppLayout>
</template>

<style scoped>
.result-grid {
    display: grid;
    gap: var(--space-4);
}
.result-score {
    margin: 0;
    color: var(--color-primary);
    font-size: 2rem;
    font-weight: var(--font-weight-bold);
}
.muted {
    color: var(--color-text-secondary);
}
.review-form {
    max-width: 56rem;
    margin-top: var(--space-6);
}
.review-form__grid {
    display: grid;
    gap: var(--space-4);
    margin-bottom: var(--space-5);
}
@media (min-width: 48rem) {
    .result-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .review-form__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
