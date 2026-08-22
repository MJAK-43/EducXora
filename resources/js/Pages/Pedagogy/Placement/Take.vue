<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import UiAlert from '@/Components/Ui/UiAlert.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import UiRadio from '@/Components/Ui/UiRadio.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';

interface AttemptQuestion {
    uuid: string;
    position: number;
    prompt: string;
    choices: Record<string, string>;
    selected_choice: string | null;
}
interface Attempt {
    uuid: string;
    learner: { uuid: string; name: string };
    question_count: number;
    answered_count: number;
    questions: AttemptQuestion[];
}
const props = defineProps<{
    attempt: Attempt;
    can: { answer: boolean; complete: boolean; review: boolean };
}>();
const currentQuestion = computed(() =>
    props.attempt.questions.find((question) => !question.selected_choice),
);
const answerForm = useForm({ question_uuid: '', answer: '' });
const page = usePage();
const flash = page.props.flash as { status?: string } | undefined;
function saveAnswer(): void {
    if (!currentQuestion.value) return;
    answerForm.question_uuid = currentQuestion.value.uuid;
    answerForm.post(`/pedagogy/tests/${props.attempt.uuid}/answers`, { preserveScroll: true });
}
function complete(): void {
    if (window.confirm('Finaliser le test ? Les réponses ne pourront plus être modifiées.')) {
        router.patch(`/pedagogy/tests/${props.attempt.uuid}/complete`);
    }
}
</script>

<template>
    <Head :title="`Test de ${attempt.learner.name}`" />
    <AppLayout :page-title="`Test de ${attempt.learner.name}`">
        <PageHeader
            eyebrow="Test de positionnement"
            :title="attempt.learner.name"
            :description="`${attempt.answered_count} réponse(s) sur ${attempt.question_count}`"
        >
            <template #actions>
                <Link :href="`/learners/${attempt.learner.uuid}/pedagogy`"
                    >Quitter et reprendre plus tard</Link
                >
            </template>
        </PageHeader>
        <UiAlert
            v-if="flash?.status"
            tone="success"
            :title="flash.status"
        />
        <UiAlert
            v-if="answerForm.errors.answer"
            tone="danger"
            title="Réponse non enregistrée"
            >{{ answerForm.errors.answer }}</UiAlert
        >
        <div
            class="placement-progress"
            role="progressbar"
            :aria-valuenow="attempt.answered_count"
            aria-valuemin="0"
            :aria-valuemax="attempt.question_count"
        >
            <span
                :style="{ width: `${(attempt.answered_count / attempt.question_count) * 100}%` }"
            />
        </div>
        <UiCard
            v-if="currentQuestion"
            class="placement-question"
        >
            <p class="placement-question__position">
                Question {{ currentQuestion.position }} sur {{ attempt.question_count }}
            </p>
            <h2>{{ currentQuestion.prompt }}</h2>
            <form
                class="placement-question__answers"
                @submit.prevent="saveAnswer"
            >
                <UiRadio
                    v-for="(label, key) in currentQuestion.choices"
                    :key="key"
                    v-model="answerForm.answer"
                    name="placement-answer"
                    :value="key"
                    :label="`${key}. ${label}`"
                    :disabled="!can.answer || answerForm.processing"
                />
                <UiButton
                    type="submit"
                    :disabled="!answerForm.answer || !can.answer"
                    :loading="answerForm.processing"
                    >Valider cette réponse</UiButton
                >
            </form>
        </UiCard>
        <UiCard v-else>
            <template #header><h2>Toutes les réponses sont enregistrées</h2></template>
            <p>
                La finalisation calcule le score côté serveur et produit une suggestion non
                définitive.
            </p>
            <UiButton
                v-if="can.complete"
                @click="complete"
                >Finaliser le test</UiButton
            >
        </UiCard>
    </AppLayout>
</template>

<style scoped>
.placement-progress {
    height: 0.65rem;
    margin-bottom: var(--space-6);
    overflow: hidden;
    border-radius: var(--radius-lg);
    background: var(--color-surface-muted);
}
.placement-progress span {
    display: block;
    height: 100%;
    background: var(--color-primary);
    transition: width 180ms ease;
}
.placement-question {
    max-width: 52rem;
    margin: 0 auto;
}
.placement-question__position {
    color: var(--color-text-secondary);
    font-weight: var(--font-weight-semibold);
}
.placement-question__answers {
    display: grid;
    gap: var(--space-3);
    margin-top: var(--space-6);
}
@media (prefers-reduced-motion: reduce) {
    .placement-progress span {
        transition: none;
    }
}
</style>
