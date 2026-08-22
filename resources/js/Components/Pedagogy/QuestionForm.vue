<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import UiAlert from '@/Components/Ui/UiAlert.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import UiInput from '@/Components/Ui/UiInput.vue';
import UiSelect from '@/Components/Ui/UiSelect.vue';
import UiTextarea from '@/Components/Ui/UiTextarea.vue';

interface Option {
    value: string;
    label: string;
}
interface Question {
    uuid: string;
    source: string;
    level: string;
    prompt: string;
    choices: Record<'A' | 'B' | 'C' | 'D', string>;
    correct_choice: string;
    status: string;
    readonly: boolean;
}
const props = defineProps<{
    question?: Question;
    levels: Option[];
    can?: { update: boolean; disable: boolean; enable: boolean };
}>();
const form = useForm({
    language: 'de',
    level: props.question?.level ?? 'A1',
    prompt: props.question?.prompt ?? '',
    choice_a: props.question?.choices.A ?? '',
    choice_b: props.question?.choices.B ?? '',
    choice_c: props.question?.choices.C ?? '',
    choice_d: props.question?.choices.D ?? '',
    correct_choice: props.question?.correct_choice ?? 'A',
});
const answerOptions = ['A', 'B', 'C', 'D'].map((value) => ({ value, label: `Réponse ${value}` }));
function submit(): void {
    if (props.question)
        form.patch(`/pedagogy/questions/${props.question.uuid}`, { preserveScroll: true });
    else form.post('/pedagogy/questions');
}
function disableQuestion(): void {
    if (props.question && window.confirm('Désactiver cette question pour les prochains tests ?')) {
        router.patch(
            `/pedagogy/questions/${props.question.uuid}/disable`,
            {},
            { preserveScroll: true },
        );
    }
}
function enableQuestion(): void {
    if (props.question && window.confirm('Réactiver cette question pour les prochains tests ?')) {
        router.patch(
            `/pedagogy/questions/${props.question.uuid}/enable`,
            {},
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <UiAlert
        v-if="question?.readonly"
        tone="info"
        title="Question système en lecture seule"
    >
        Elle peut être consultée, mais seule une mise à jour technique du catalogue global peut la
        modifier.
    </UiAlert>
    <form
        class="question-form"
        novalidate
        @submit.prevent="submit"
    >
        <UiCard>
            <template #header><h2>Contenu de la question</h2></template>
            <div class="question-form__grid">
                <UiInput
                    model-value="Allemand"
                    label="Langue"
                    readonly
                />
                <UiSelect
                    v-model="form.level"
                    label="Niveau CECRL"
                    :options="levels"
                    required
                    :disabled="question?.readonly"
                    :error="form.errors.level"
                />
            </div>
            <UiTextarea
                v-model="form.prompt"
                label="Énoncé"
                required
                :readonly="question?.readonly"
                :error="form.errors.prompt"
            />
            <div class="question-form__choices">
                <UiInput
                    v-model="form.choice_a"
                    label="Choix A"
                    required
                    :readonly="question?.readonly"
                    :error="form.errors.choice_a"
                />
                <UiInput
                    v-model="form.choice_b"
                    label="Choix B"
                    required
                    :readonly="question?.readonly"
                    :error="form.errors.choice_b"
                />
                <UiInput
                    v-model="form.choice_c"
                    label="Choix C"
                    required
                    :readonly="question?.readonly"
                    :error="form.errors.choice_c"
                />
                <UiInput
                    v-model="form.choice_d"
                    label="Choix D"
                    required
                    :readonly="question?.readonly"
                    :error="form.errors.choice_d"
                />
            </div>
            <UiSelect
                v-model="form.correct_choice"
                label="Bonne réponse"
                :options="answerOptions"
                required
                :disabled="question?.readonly"
                :error="form.errors.correct_choice"
            />
        </UiCard>
        <div class="question-form__actions">
            <Link href="/pedagogy/questions">Retour à la banque</Link>
            <UiButton
                v-if="question && can?.disable && question.status === 'active'"
                type="button"
                variant="outline"
                @click="disableQuestion"
            >
                Désactiver
            </UiButton>
            <UiButton
                v-if="question && can?.enable && question.status === 'inactive'"
                type="button"
                variant="outline"
                @click="enableQuestion"
            >
                Réactiver
            </UiButton>
            <UiButton
                v-if="!question || can?.update"
                type="submit"
                :loading="form.processing"
            >
                {{ question ? 'Enregistrer' : 'Créer la question' }}
            </UiButton>
        </div>
    </form>
</template>

<style scoped>
.question-form {
    display: grid;
    max-width: 60rem;
    gap: var(--space-6);
}
.question-form__grid,
.question-form__choices {
    display: grid;
    gap: var(--space-4);
}
.question-form__choices {
    margin: var(--space-5) 0;
}
.question-form__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: var(--space-4);
}
@media (min-width: 48rem) {
    .question-form__grid,
    .question-form__choices {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
