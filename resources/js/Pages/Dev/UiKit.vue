<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { MoreHorizontal, Plus } from '@lucide/vue';
import { ref } from 'vue';

import UiAlert from '@/Components/Ui/UiAlert.vue';
import UiAvatar from '@/Components/Ui/UiAvatar.vue';
import UiBadge from '@/Components/Ui/UiBadge.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import UiCheckbox from '@/Components/Ui/UiCheckbox.vue';
import UiDivider from '@/Components/Ui/UiDivider.vue';
import UiDropdown from '@/Components/Ui/UiDropdown.vue';
import UiEmptyState from '@/Components/Ui/UiEmptyState.vue';
import UiIconButton from '@/Components/Ui/UiIconButton.vue';
import UiInput from '@/Components/Ui/UiInput.vue';
import UiModal from '@/Components/Ui/UiModal.vue';
import UiRadio from '@/Components/Ui/UiRadio.vue';
import UiSelect from '@/Components/Ui/UiSelect.vue';
import UiSkeleton from '@/Components/Ui/UiSkeleton.vue';
import UiSpinner from '@/Components/Ui/UiSpinner.vue';
import UiSwitch from '@/Components/Ui/UiSwitch.vue';
import UiTextarea from '@/Components/Ui/UiTextarea.vue';
import UiTooltip from '@/Components/Ui/UiTooltip.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';

const modalOpen = ref(false);
const name = ref('Centre de démonstration');
const email = ref('contact@exemple.test');
const notes = ref('Une note courte et utile.');
const language = ref('de');
const accepted = ref(true);
const radio = ref('standard');
const notifications = ref(false);

const options = [
    { value: 'de', label: 'Allemand' },
    { value: 'fr', label: 'Français — aperçu uniquement', disabled: true },
];

const swatches = [
    { name: 'Primary', color: 'var(--color-primary)', text: '#fff' },
    { name: 'Surface', color: 'var(--color-surface)', text: 'var(--color-text-primary)' },
    {
        name: 'Surface secondaire',
        color: 'var(--color-surface-secondary)',
        text: 'var(--color-text-primary)',
    },
    { name: 'Success', color: 'var(--color-success-soft)', text: 'var(--color-success)' },
    { name: 'Warning', color: 'var(--color-warning-soft)', text: 'var(--color-warning)' },
    { name: 'Danger', color: 'var(--color-danger-soft)', text: 'var(--color-danger)' },
];

const spaces = [4, 8, 12, 16, 24, 32, 48];
</script>

<template>
    <Head title="Catalogue UI" />
    <AppLayout page-title="Catalogue UI">
        <PageHeader
            eyebrow="Développement uniquement"
            title="Design system EduXora"
            description="Catalogue interne des fondations visuelles et interactives. Cette route retourne 404 hors environnement local."
        >
            <template #actions><UiBadge tone="warning">/dev/ui</UiBadge></template>
        </PageHeader>

        <section
            class="ui-catalog__section"
            aria-labelledby="type-title"
        >
            <div class="ui-catalog__section-header">
                <h2
                    id="type-title"
                    class="text-h2"
                >
                    Typographie
                </h2>
                <p>Hiérarchie compacte, lisible et fondée sur la pile système.</p>
            </div>
            <div class="ui-catalog__surface ui-stack">
                <p class="text-display">Display — Piloter avec clarté</p>
                <h1 class="text-h1">Heading 1 — Vue opérationnelle</h1>
                <h2 class="text-h2">Heading 2 — Informations essentielles</h2>
                <h3 class="text-h3">Heading 3 — Détail d'une section</h3>
                <p class="text-body">Body — Une lecture confortable pour les usages quotidiens.</p>
                <p class="text-body-small">
                    Body Small — Une information secondaire toujours nette.
                </p>
                <p class="text-caption">Caption — Mis à jour aujourd'hui à 09:30</p>
            </div>
        </section>

        <section
            class="ui-catalog__section"
            aria-labelledby="palette-title"
        >
            <div class="ui-catalog__section-header">
                <h2
                    id="palette-title"
                    class="text-h2"
                >
                    Palette sémantique
                </h2>
                <p>La couleur renforce le texte et ne porte jamais seule l'information.</p>
            </div>
            <div class="ui-catalog__swatches">
                <div
                    v-for="swatch in swatches"
                    :key="swatch.name"
                    class="ui-swatch"
                    :style="{ '--swatch': swatch.color, '--swatch-text': swatch.text }"
                >
                    {{ swatch.name }}
                </div>
            </div>
        </section>

        <section
            class="ui-catalog__section"
            aria-labelledby="buttons-title"
        >
            <div class="ui-catalog__section-header">
                <h2
                    id="buttons-title"
                    class="text-h2"
                >
                    Boutons et actions
                </h2>
                <p>Variantes, tailles et états cohérents, avec prévention du double submit.</p>
            </div>
            <div class="ui-catalog__surface ui-stack">
                <div class="ui-catalog__row">
                    <UiButton>Primaire</UiButton>
                    <UiButton variant="secondary">Secondaire</UiButton>
                    <UiButton variant="outline">Contour</UiButton>
                    <UiButton variant="ghost">Discret</UiButton>
                    <UiButton variant="danger">Danger</UiButton>
                </div>
                <div class="ui-catalog__row">
                    <UiButton size="sm">Petit</UiButton>
                    <UiButton size="md">Moyen</UiButton>
                    <UiButton size="lg">Grand</UiButton>
                    <UiButton loading>Enregistrement</UiButton>
                    <UiButton disabled>Désactivé</UiButton>
                    <UiTooltip text="Ajouter un élément">
                        <UiIconButton label="Ajouter un élément"
                            ><Plus
                                :size="19"
                                aria-hidden="true"
                        /></UiIconButton>
                    </UiTooltip>
                </div>
            </div>
        </section>

        <section
            class="ui-catalog__section"
            aria-labelledby="forms-title"
        >
            <div class="ui-catalog__section-header">
                <h2
                    id="forms-title"
                    class="text-h2"
                >
                    Champs de formulaire
                </h2>
                <p>Labels visibles, aide et erreur associées aux contrôles.</p>
            </div>
            <div class="ui-catalog__surface ui-catalog__grid">
                <UiInput
                    v-model="name"
                    label="Nom affiché"
                    required
                    help="Utilisé dans les interfaces internes."
                />
                <UiInput
                    v-model="email"
                    label="Adresse email"
                    type="email"
                    error="Utilisez une adresse professionnelle valide."
                />
                <UiSelect
                    v-model="language"
                    label="Langue"
                    :options="options"
                />
                <UiInput
                    label="Référence"
                    readonly
                    model-value="FOUNDATION-01"
                />
                <UiTextarea
                    v-model="notes"
                    label="Notes"
                    help="Restez concis et factuel."
                />
                <div class="ui-stack">
                    <UiCheckbox
                        v-model="accepted"
                        label="Option activée"
                        help="Exemple générique de choix binaire."
                    />
                    <UiRadio
                        v-model="radio"
                        name="density"
                        value="standard"
                        label="Densité standard"
                    />
                    <UiRadio
                        v-model="radio"
                        name="density"
                        value="compact"
                        label="Densité compacte"
                    />
                    <UiSwitch
                        v-model="notifications"
                        label="Notifications internes"
                        help="Aucun envoi externe dans cette démonstration."
                    />
                </div>
            </div>
        </section>

        <section
            class="ui-catalog__section"
            aria-labelledby="feedback-title"
        >
            <div class="ui-catalog__section-header">
                <h2
                    id="feedback-title"
                    class="text-h2"
                >
                    Feedback et statuts
                </h2>
                <p>États textuels explicites pour succès, attention, erreur et information.</p>
            </div>
            <div class="ui-catalog__grid">
                <div class="ui-stack">
                    <UiAlert
                        tone="info"
                        title="Information"
                        >Le catalogue est réservé au développement.</UiAlert
                    >
                    <UiAlert
                        tone="success"
                        title="Configuration valide"
                        >Les fondations sont prêtes.</UiAlert
                    >
                    <UiAlert
                        tone="warning"
                        title="Action à confirmer"
                        >Vérifiez les données avant de continuer.</UiAlert
                    >
                    <UiAlert
                        tone="danger"
                        title="Échec de validation"
                        >Corrigez les champs signalés.</UiAlert
                    >
                </div>
                <UiCard>
                    <template #header><h3 class="text-h3">Badges et identité</h3></template>
                    <div class="ui-catalog__row">
                        <UiBadge>Neutre</UiBadge><UiBadge tone="primary">Actif</UiBadge>
                        <UiBadge tone="success">Validé</UiBadge
                        ><UiBadge tone="warning">En attente</UiBadge>
                        <UiBadge tone="danger">Erreur</UiBadge
                        ><UiBadge tone="info">Information</UiBadge>
                        <UiAvatar name="Équipe EduXora" />
                    </div>
                </UiCard>
            </div>
        </section>

        <section
            class="ui-catalog__section"
            aria-labelledby="overlay-title"
        >
            <div class="ui-catalog__section-header">
                <h2
                    id="overlay-title"
                    class="text-h2"
                >
                    Surfaces interactives
                </h2>
                <p>Modal avec focus trap et fermeture Escape ; dropdown utilisable au clavier.</p>
            </div>
            <div class="ui-catalog__surface ui-catalog__row">
                <UiButton @click="modalOpen = true">Ouvrir la modal</UiButton>
                <UiDropdown label="Actions">
                    <button
                        class="ui-dropdown__item"
                        role="menuitem"
                    >
                        Consulter
                    </button>
                    <button
                        class="ui-dropdown__item"
                        role="menuitem"
                    >
                        Dupliquer
                    </button>
                </UiDropdown>
                <UiIconButton label="Plus d'options"
                    ><MoreHorizontal
                        :size="20"
                        aria-hidden="true"
                /></UiIconButton>
            </div>
        </section>

        <section
            class="ui-catalog__section"
            aria-labelledby="states-title"
        >
            <div class="ui-catalog__section-header">
                <h2
                    id="states-title"
                    class="text-h2"
                >
                    Loading, skeleton et empty state
                </h2>
                <p>Trois états transversaux prévus dès la fondation.</p>
            </div>
            <div class="ui-catalog__grid">
                <UiCard>
                    <div class="ui-catalog__row">
                        <UiSpinner /><span>Chargement des données</span>
                    </div>
                    <UiDivider />
                    <div class="ui-stack">
                        <UiSkeleton
                            width="55%"
                            height="1.25rem"
                        />
                        <UiSkeleton height=".875rem" />
                        <UiSkeleton
                            width="78%"
                            height=".875rem"
                        />
                    </div>
                </UiCard>
                <UiCard
                    ><UiEmptyState
                        title="Aucun élément"
                        message="Les futurs modules utiliseront cet état lorsqu'aucune donnée ne correspond au contexte."
                /></UiCard>
            </div>
        </section>

        <section
            class="ui-catalog__section"
            aria-labelledby="spacing-title"
        >
            <div class="ui-catalog__section-header">
                <h2
                    id="spacing-title"
                    class="text-h2"
                >
                    Échelle d'espacement
                </h2>
                <p>Une échelle courte limite les valeurs arbitraires.</p>
            </div>
            <div class="ui-catalog__surface ui-catalog__spacing">
                <div
                    v-for="space in spaces"
                    :key="space"
                    class="ui-catalog__spacing-item"
                >
                    <div
                        class="ui-catalog__spacing-block"
                        :style="{ width: `${space}px`, height: `${space}px` }"
                    />
                    {{ space }} px
                </div>
            </div>
        </section>

        <UiModal
            :open="modalOpen"
            title="Confirmer l'action"
            @close="modalOpen = false"
        >
            Cette modal démontre le focus initial, la boucle de tabulation, le retour du focus et la
            fermeture avec Escape.
            <template #footer>
                <UiButton
                    variant="ghost"
                    @click="modalOpen = false"
                    >Annuler</UiButton
                >
                <UiButton @click="modalOpen = false">Confirmer</UiButton>
            </template>
        </UiModal>
    </AppLayout>
</template>
