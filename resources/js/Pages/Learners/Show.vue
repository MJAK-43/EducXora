<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import UiAlert from '@/Components/Ui/UiAlert.vue';
import UiBadge from '@/Components/Ui/UiBadge.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
interface Learner {
    uuid: string;
    full_name: string;
    first_name: string;
    last_name: string;
    birth_date_label: string;
    phone: string;
    email: string | null;
    language_label: string;
    initial_level: string;
    current_level: string;
    registered_on_label: string;
    status: string;
    photo_url: string | null;
}
const props = defineProps<{
    learner: Learner;
    can: {
        update: boolean;
        archive: boolean;
        restore: boolean;
        viewAttendanceHistory: boolean;
        viewPedagogy: boolean;
    };
}>();
const page = usePage();
const flash = page.props.flash as { status?: string } | undefined;
function archiveLearner(): void {
    if (window.confirm(`Archiver ${props.learner.full_name} ?`))
        router.patch(`/learners/${props.learner.uuid}/archive`);
}
function restoreLearner(): void {
    if (window.confirm(`Restaurer ${props.learner.full_name} ?`))
        router.patch(`/learners/${props.learner.uuid}/restore`);
}
</script>

<template>
    <Head :title="learner.full_name" />
    <AppLayout :page-title="learner.full_name">
        <PageHeader
            eyebrow="Apprenants"
            :title="learner.full_name"
            description="Dossier administratif de base dans l’organisation active."
        >
            <template #actions
                ><div class="inline-actions">
                    <Link
                        class="learner-outline-link"
                        href="/learners"
                        >Retour</Link
                    ><Link
                        v-if="can.update"
                        class="learner-primary-link"
                        :href="`/learners/${learner.uuid}/edit`"
                        >Modifier</Link
                    ><UiButton
                        v-if="learner.status === 'active' && can.archive"
                        variant="outline"
                        @click="archiveLearner"
                        >Archiver</UiButton
                    ><UiButton
                        v-if="learner.status === 'archived' && can.restore"
                        @click="restoreLearner"
                        >Restaurer</UiButton
                    >
                </div></template
            >
        </PageHeader>
        <UiCard
            v-if="can.viewAttendanceHistory"
            class="attendance-entry"
            ><strong>Présences</strong>
            <p>Consultez l’historique validé et le taux de présence de cet apprenant.</p>
            <Link
                class="learner-primary-link"
                :href="`/learners/${learner.uuid}/attendance`"
                >Voir les présences</Link
            ></UiCard
        >
        <UiCard
            v-if="can.viewPedagogy"
            class="attendance-entry"
            ><strong>Suivi pédagogique</strong>
            <p>
                Consultez le niveau actuel, les tests de positionnement et l’historique des
                décisions.
            </p>
            <Link
                class="learner-primary-link"
                :href="`/learners/${learner.uuid}/pedagogy`"
                >Ouvrir le suivi pédagogique</Link
            ></UiCard
        >
        <UiAlert
            v-if="flash?.status"
            tone="success"
            :title="flash.status"
        />
        <div class="learner-detail">
            <UiCard class="learner-profile">
                <img
                    v-if="learner.photo_url"
                    class="learner-profile__photo"
                    :src="learner.photo_url"
                    :alt="`Photo de ${learner.full_name}`"
                />
                <div
                    v-else
                    class="learner-profile__initials"
                    aria-hidden="true"
                >
                    {{ learner.first_name.charAt(0) }}{{ learner.last_name.charAt(0) }}
                </div>
                <h2>{{ learner.full_name }}</h2>
                <UiBadge :tone="learner.status === 'active' ? 'success' : 'neutral'">{{
                    learner.status === 'active' ? 'Actif' : 'Archivé'
                }}</UiBadge>
            </UiCard>
            <UiCard>
                <template #header><h2>Informations</h2></template>
                <dl class="learner-data">
                    <div>
                        <dt>Date de naissance</dt>
                        <dd>{{ learner.birth_date_label }}</dd>
                    </div>
                    <div>
                        <dt>Téléphone</dt>
                        <dd>{{ learner.phone }}</dd>
                    </div>
                    <div>
                        <dt>E-mail</dt>
                        <dd>{{ learner.email || 'Non renseigné' }}</dd>
                    </div>
                    <div>
                        <dt>Langue</dt>
                        <dd>{{ learner.language_label }}</dd>
                    </div>
                    <div>
                        <dt>Niveau initial</dt>
                        <dd>{{ learner.initial_level }}</dd>
                    </div>
                    <div>
                        <dt>Niveau actuel</dt>
                        <dd>{{ learner.current_level }}</dd>
                    </div>
                    <div>
                        <dt>Inscription</dt>
                        <dd>{{ learner.registered_on_label }}</dd>
                    </div>
                </dl>
            </UiCard>
        </div>
        <UiCard class="learner-future">
            <template #header><h2>Parcours de l’apprenant</h2></template>
            <p>
                Les groupes, le planning, les présences, les évaluations et les paiements seront
                disponibles dans leurs phases dédiées.
            </p>
            <div
                class="learner-tabs"
                aria-label="Fonctionnalités futures"
            >
                <span
                    v-for="label in [
                        'Groupes',
                        'Planning',
                        'Présences',
                        'Évaluations',
                        'Paiements',
                    ]"
                    :key="label"
                    aria-disabled="true"
                    >{{ label }}</span
                >
            </div>
        </UiCard>
    </AppLayout>
</template>

<style scoped>
.learner-detail {
    display: grid;
    gap: var(--space-6);
}
.learner-profile {
    text-align: center;
}
.learner-profile__photo,
.learner-profile__initials {
    width: 7rem;
    height: 7rem;
    margin: 0 auto var(--space-4);
    border-radius: 50%;
    object-fit: cover;
}
.learner-profile__initials {
    display: grid;
    place-items: center;
    background: var(--color-primary-soft);
    color: var(--color-primary);
    font-size: 2rem;
    font-weight: var(--font-weight-bold);
}
.learner-data {
    display: grid;
    gap: var(--space-5);
    margin: 0;
}
.learner-data div {
    min-width: 0;
}
.learner-data dt {
    color: var(--color-text-secondary);
    font-size: var(--font-size-caption);
}
.learner-data dd {
    margin: var(--space-1) 0 0;
    overflow-wrap: anywhere;
    font-weight: var(--font-weight-semibold);
}
.learner-future {
    margin-top: var(--space-6);
}
.attendance-entry {
    margin-bottom: var(--space-6);
}
.learner-future p {
    color: var(--color-text-secondary);
}
.learner-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
    margin-top: var(--space-4);
}
.learner-tabs span {
    border: 1px dashed var(--color-border);
    border-radius: var(--radius-md);
    padding: var(--space-2) var(--space-3);
    color: var(--color-text-muted);
}
.learner-outline-link,
.learner-primary-link {
    display: inline-flex;
    align-items: center;
    min-height: 2.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 0 var(--space-4);
    color: var(--color-text-primary);
    text-decoration: none;
}
.learner-primary-link {
    border-color: var(--color-primary);
    background: var(--color-primary);
    color: white;
}
@media (min-width: 48rem) {
    .learner-detail {
        grid-template-columns: minmax(14rem, 0.65fr) minmax(0, 2fr);
    }
    .learner-data {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
