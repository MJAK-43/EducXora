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
interface LearnerValue {
    uuid: string;
    first_name: string;
    last_name: string;
    birth_date: string;
    phone: string;
    email: string | null;
    language: string;
    initial_level: string;
    has_photo: boolean;
    photo_url: string | null;
}
const props = defineProps<{
    learner?: LearnerValue;
    options: { levels: Option[]; languages: Option[] };
}>();
const form = useForm({
    _method: props.learner ? 'patch' : 'post',
    first_name: props.learner?.first_name ?? '',
    last_name: props.learner?.last_name ?? '',
    birth_date: props.learner?.birth_date ?? '',
    phone: props.learner?.phone ?? '',
    email: props.learner?.email ?? '',
    language: props.learner?.language ?? 'de',
    initial_level: props.learner?.initial_level ?? 'A1',
    photo: null as File | null,
    remove_photo: false,
});

function choosePhoto(event: Event): void {
    form.photo = (event.target as HTMLInputElement).files?.[0] ?? null;
}
function submit(): void {
    form.post(props.learner ? `/learners/${props.learner.uuid}` : '/learners', {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <form
        class="learner-form"
        novalidate
        @submit.prevent="submit"
    >
        <UiCard>
            <template #header><h2>Identité et contact</h2></template>
            <div class="learner-form__grid">
                <UiInput
                    v-model="form.first_name"
                    label="Prénom"
                    required
                    autocomplete="given-name"
                    :error="form.errors.first_name"
                />
                <UiInput
                    v-model="form.last_name"
                    label="Nom"
                    required
                    autocomplete="family-name"
                    :error="form.errors.last_name"
                />
                <UiInput
                    v-model="form.birth_date"
                    label="Date de naissance"
                    type="date"
                    required
                    :error="form.errors.birth_date"
                />
                <UiInput
                    v-model="form.phone"
                    label="Téléphone"
                    type="tel"
                    required
                    autocomplete="tel"
                    help="Ex. 6 99 12 34 56 ou +237 699 123 456"
                    :error="form.errors.phone"
                />
                <UiInput
                    v-model="form.email"
                    label="E-mail (facultatif)"
                    type="email"
                    autocomplete="email"
                    :error="form.errors.email"
                />
            </div>
        </UiCard>
        <UiCard>
            <template #header><h2>Profil d’apprentissage initial</h2></template>
            <div class="learner-form__grid">
                <UiSelect
                    v-model="form.language"
                    label="Langue"
                    :options="options.languages"
                    required
                    :error="form.errors.language"
                />
                <UiSelect
                    v-model="form.initial_level"
                    label="Niveau CECRL initial"
                    :options="options.levels"
                    required
                    :error="form.errors.initial_level"
                />
            </div>
        </UiCard>
        <UiCard>
            <template #header><h2>Photo facultative</h2></template>
            <div class="ui-field">
                <label
                    class="ui-field__label"
                    for="learner-photo"
                    >Photo</label
                >
                <input
                    id="learner-photo"
                    class="ui-field__control"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    :aria-invalid="form.errors.photo ? 'true' : undefined"
                    aria-describedby="learner-photo-help"
                    @change="choosePhoto"
                />
                <p
                    id="learner-photo-help"
                    :class="form.errors.photo ? 'ui-field__error' : 'ui-field__help'"
                >
                    {{ form.errors.photo ?? 'JPEG, PNG ou WebP, 2 Mo maximum. Stockage privé.' }}
                </p>
            </div>
            <label
                v-if="learner?.has_photo"
                class="learner-form__remove"
                ><input
                    v-model="form.remove_photo"
                    type="checkbox"
                />
                Retirer la photo actuelle</label
            >
        </UiCard>
        <div class="learner-form__actions">
            <Link
                :href="learner ? `/learners/${learner.uuid}` : '/learners'"
                class="learner-link"
                >Annuler</Link
            >
            <UiButton
                type="submit"
                :loading="form.processing"
                >{{ learner ? 'Enregistrer' : 'Créer l’apprenant' }}</UiButton
            >
        </div>
    </form>
</template>

<style scoped>
.learner-form {
    display: grid;
    gap: var(--space-6);
    max-width: 56rem;
}
.learner-form__grid {
    display: grid;
    gap: var(--space-5);
}
.learner-form__actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--space-4);
}
.learner-form__remove {
    display: flex;
    gap: var(--space-2);
    align-items: center;
    margin-top: var(--space-4);
}
.learner-link {
    color: var(--color-text-secondary);
}
@media (min-width: 40rem) {
    .learner-form__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
