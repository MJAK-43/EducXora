<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search } from '@lucide/vue';
import { reactive } from 'vue';
import UiBadge from '@/Components/Ui/UiBadge.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import UiEmptyState from '@/Components/Ui/UiEmptyState.vue';
import UiInput from '@/Components/Ui/UiInput.vue';
import UiSelect from '@/Components/Ui/UiSelect.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';

interface Question {
    uuid: string;
    source: string;
    source_label: string;
    level: string;
    prompt: string;
    status: string;
    readonly: boolean;
}
interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}
const props = defineProps<{
    questions: { data: Question[]; links: PageLink[]; total: number };
    filters: { search: string; level: string; source: string; status: string };
    levels: Array<{ value: string; label: string }>;
    can: { create: boolean };
}>();
const filters = reactive({ ...props.filters });
const sourceOptions = [
    { value: '', label: 'Toutes les sources' },
    { value: 'system', label: 'Système' },
    { value: 'organization', label: 'Organisation' },
];
const statusOptions = [
    { value: '', label: 'Tous les statuts' },
    { value: 'active', label: 'Actives' },
    { value: 'inactive', label: 'Inactives' },
];
function search(): void {
    router.get('/pedagogy/questions', filters, { preserveState: true, replace: true });
}
function paginationLabel(label: string): string {
    return label.replace('&laquo; Previous', 'Précédent').replace('Next &raquo;', 'Suivant');
}
</script>

<template>
    <Head title="Banque de questions" />
    <AppLayout page-title="Banque de questions">
        <PageHeader
            eyebrow="Suivi pédagogique"
            title="Banque de questions"
            description="Questions allemandes du système et de l’organisation active."
        >
            <template #actions>
                <Link
                    v-if="can.create"
                    class="primary-link"
                    href="/pedagogy/questions/create"
                >
                    <Plus
                        :size="17"
                        aria-hidden="true"
                    />Nouvelle question
                </Link>
            </template>
        </PageHeader>
        <UiCard>
            <form
                class="question-filters"
                role="search"
                @submit.prevent="search"
            >
                <UiInput
                    v-model="filters.search"
                    label="Recherche"
                    placeholder="Texte de l’énoncé"
                />
                <UiSelect
                    v-model="filters.level"
                    label="Niveau"
                    :options="[{ value: '', label: 'Tous les niveaux' }, ...levels]"
                    @update:model-value="search"
                />
                <UiSelect
                    v-model="filters.source"
                    label="Source"
                    :options="sourceOptions"
                    @update:model-value="search"
                />
                <UiSelect
                    v-model="filters.status"
                    label="Statut"
                    :options="statusOptions"
                    @update:model-value="search"
                />
                <UiButton type="submit"
                    ><template #icon
                        ><Search
                            :size="17"
                            aria-hidden="true" /></template
                    >Rechercher</UiButton
                >
            </form>
        </UiCard>
        <UiEmptyState
            v-if="!questions.data.length"
            title="Aucune question"
            message="Aucune question ne correspond aux filtres sélectionnés."
        />
        <div
            v-else
            class="question-results"
        >
            <p class="muted">{{ questions.total }} question(s)</p>
            <div class="question-grid">
                <UiCard
                    v-for="question in questions.data"
                    :key="question.uuid"
                >
                    <div class="question-card__meta">
                        <UiBadge :tone="question.source === 'system' ? 'info' : 'neutral'">{{
                            question.source_label
                        }}</UiBadge>
                        <UiBadge>{{ question.level }}</UiBadge>
                        <UiBadge :tone="question.status === 'active' ? 'success' : 'neutral'">{{
                            question.status === 'active' ? 'Active' : 'Inactive'
                        }}</UiBadge>
                    </div>
                    <h2 class="question-card__title">{{ question.prompt }}</h2>
                    <Link :href="`/pedagogy/questions/${question.uuid}/edit`">{{
                        question.readonly ? 'Consulter' : 'Modifier'
                    }}</Link>
                </UiCard>
            </div>
            <nav
                v-if="questions.links.length > 3"
                class="pagination"
                aria-label="Pagination des questions"
            >
                <template
                    v-for="link in questions.links"
                    :key="link.label"
                >
                    <Link
                        v-if="link.url"
                        class="pagination__link"
                        :class="{ 'pagination__link--active': link.active }"
                        :href="link.url"
                        >{{ paginationLabel(link.label) }}</Link
                    >
                    <span
                        v-else
                        class="pagination__link pagination__link--disabled"
                        >{{ paginationLabel(link.label) }}</span
                    >
                </template>
            </nav>
        </div>
    </AppLayout>
</template>

<style scoped>
.question-filters {
    display: grid;
    align-items: end;
    gap: var(--space-4);
}
.question-results {
    margin-top: var(--space-6);
}
.question-grid {
    display: grid;
    gap: var(--space-4);
}
.question-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}
.question-card__title {
    margin: var(--space-4) 0;
    font-size: var(--font-size-body);
}
.muted {
    color: var(--color-text-secondary);
}
.primary-link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    min-height: 2.5rem;
    border-radius: var(--radius-md);
    padding: 0 var(--space-4);
    background: var(--color-primary);
    color: white;
    text-decoration: none;
}
.pagination {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
    margin-top: var(--space-5);
}
.pagination__link {
    min-width: 2.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: var(--space-2);
    text-align: center;
    text-decoration: none;
}
.pagination__link--active {
    border-color: var(--color-primary);
    background: var(--color-primary-soft);
}
.pagination__link--disabled {
    color: var(--color-text-muted);
}
@media (min-width: 48rem) {
    .question-filters {
        grid-template-columns: 1.5fr repeat(3, minmax(8rem, 1fr)) auto;
    }
    .question-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
