<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, Plus, Search } from '@lucide/vue';
import { reactive } from 'vue';
import UiBadge from '@/Components/Ui/UiBadge.vue';
import UiButton from '@/Components/Ui/UiButton.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import UiEmptyState from '@/Components/Ui/UiEmptyState.vue';
import UiInput from '@/Components/Ui/UiInput.vue';
import UiSelect from '@/Components/Ui/UiSelect.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';

interface Option {
    value: string;
    label: string;
}
interface Learner {
    uuid: string;
    full_name: string;
    phone: string;
    email: string | null;
    language_label: string;
    initial_level: string;
    registered_on_label: string;
    status: string;
}
interface LinkValue {
    url: string | null;
    label: string;
    active: boolean;
}
const props = defineProps<{
    learners: {
        data: Learner[];
        links: LinkValue[];
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: { search: string; status: string; level: string; language: string };
    options: { levels: Option[]; languages: Option[] };
    can: { create: boolean; archive: boolean; restore: boolean; export: boolean };
}>();
const filters = reactive({ ...props.filters });
const statusOptions: Option[] = [
    { value: 'active', label: 'Actifs' },
    { value: 'archived', label: 'Archivés' },
    { value: 'all', label: 'Tous' },
];
const levelOptions = [{ value: '', label: 'Tous les niveaux' }, ...props.options.levels];

function search(): void {
    router.get('/learners', filters, { preserveState: true, replace: true });
}
function changeStatus(): void {
    search();
}
function archiveLearner(learner: Learner): void {
    if (window.confirm(`Archiver ${learner.full_name} ?`))
        router.patch(`/learners/${learner.uuid}/archive`, {}, { preserveScroll: true });
}
function restoreLearner(learner: Learner): void {
    if (window.confirm(`Restaurer ${learner.full_name} ?`))
        router.patch(`/learners/${learner.uuid}/restore`, {}, { preserveScroll: true });
}
const exportUrl = () => `/learners/export?${new URLSearchParams(filters).toString()}`;
function paginationLabel(label: string): string {
    return label.replace('&laquo; Previous', 'Précédent').replace('Next &raquo;', 'Suivant');
}
</script>

<template>
    <Head title="Apprenants" />
    <AppLayout page-title="Apprenants">
        <PageHeader
            eyebrow="Administration"
            title="Apprenants"
            description="Recherchez, consultez et maintenez les dossiers de l’organisation active."
        >
            <template #actions>
                <div class="inline-actions">
                    <a
                        v-if="can.export"
                        class="learner-action-link"
                        :href="exportUrl()"
                        ><Download
                            :size="17"
                            aria-hidden="true"
                        />Exporter CSV</a
                    >
                    <Link
                        v-if="can.create"
                        class="learner-action-link learner-action-link--primary"
                        href="/learners/create"
                        ><Plus
                            :size="17"
                            aria-hidden="true"
                        />Nouvel apprenant</Link
                    >
                </div>
            </template>
        </PageHeader>
        <UiCard>
            <form
                class="learner-filters"
                role="search"
                @submit.prevent="search"
            >
                <UiInput
                    v-model="filters.search"
                    label="Recherche"
                    placeholder="Nom, téléphone ou e-mail"
                />
                <UiSelect
                    v-model="filters.status"
                    label="Statut"
                    :options="statusOptions"
                    @update:model-value="changeStatus"
                />
                <UiSelect
                    v-model="filters.level"
                    label="Niveau"
                    :options="levelOptions"
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
            v-if="!learners.data.length"
            title="Aucun apprenant trouvé"
            message="Modifiez les filtres ou créez le premier apprenant de cette organisation."
        />
        <div
            v-else
            class="learner-list"
            aria-live="polite"
        >
            <p class="learner-list__count">
                {{ learners.from }}–{{ learners.to }} sur {{ learners.total }} apprenant(s)
            </p>
            <div class="data-table-wrap learner-table-wrap">
                <table class="data-table learner-table">
                    <thead>
                        <tr>
                            <th>Apprenant</th>
                            <th>Contact</th>
                            <th>Langue / niveau</th>
                            <th>Inscription</th>
                            <th>Statut</th>
                            <th><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="learner in learners.data"
                            :key="learner.uuid"
                        >
                            <td data-label="Apprenant">
                                <Link
                                    class="learner-name"
                                    :href="`/learners/${learner.uuid}`"
                                    >{{ learner.full_name }}</Link
                                >
                            </td>
                            <td data-label="Contact">
                                <span>{{ learner.phone }}</span
                                ><br /><span class="muted">{{
                                    learner.email || 'Aucun e-mail'
                                }}</span>
                            </td>
                            <td data-label="Langue / niveau">
                                {{ learner.language_label }} · {{ learner.initial_level }}
                            </td>
                            <td data-label="Inscription">{{ learner.registered_on_label }}</td>
                            <td data-label="Statut">
                                <UiBadge
                                    :tone="learner.status === 'active' ? 'success' : 'neutral'"
                                    >{{
                                        learner.status === 'active' ? 'Actif' : 'Archivé'
                                    }}</UiBadge
                                >
                            </td>
                            <td data-label="Actions">
                                <div class="inline-actions">
                                    <Link
                                        class="learner-text-link"
                                        :href="`/learners/${learner.uuid}`"
                                        >Voir</Link
                                    ><button
                                        v-if="learner.status === 'active' && can.archive"
                                        class="learner-text-button"
                                        type="button"
                                        @click="archiveLearner(learner)"
                                    >
                                        Archiver</button
                                    ><button
                                        v-if="learner.status === 'archived' && can.restore"
                                        class="learner-text-button"
                                        type="button"
                                        @click="restoreLearner(learner)"
                                    >
                                        Restaurer
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <nav
                v-if="learners.links.length > 3"
                class="learner-pagination"
                aria-label="Pagination des apprenants"
            >
                <template
                    v-for="link in learners.links"
                    :key="link.label"
                    ><Link
                        v-if="link.url"
                        class="learner-pagination__link"
                        :class="{ 'learner-pagination__link--active': link.active }"
                        :href="link.url"
                        >{{ paginationLabel(link.label) }}</Link
                    ><span
                        v-else
                        class="learner-pagination__link learner-pagination__link--disabled"
                        >{{ paginationLabel(link.label) }}</span
                    ></template
                >
            </nav>
        </div>
    </AppLayout>
</template>

<style scoped>
.learner-filters {
    display: grid;
    gap: var(--space-4);
    align-items: end;
}
.learner-filters .ui-button {
    min-height: 2.75rem;
}
.learner-action-link {
    display: inline-flex;
    gap: var(--space-2);
    align-items: center;
    min-height: 2.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 0 var(--space-4);
    color: var(--color-text-primary);
    text-decoration: none;
}
.learner-action-link--primary {
    border-color: var(--color-primary);
    background: var(--color-primary);
    color: white;
}
.learner-list {
    margin-top: var(--space-6);
}
.learner-list__count,
.muted {
    color: var(--color-text-secondary);
}
.learner-name,
.learner-text-link {
    color: var(--color-primary);
    font-weight: var(--font-weight-semibold);
}
.learner-text-button {
    border: 0;
    padding: 0;
    background: none;
    color: var(--color-primary);
    text-decoration: underline;
}
.learner-pagination {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
    margin-top: var(--space-5);
}
.learner-pagination__link {
    min-width: 2.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: var(--space-2);
    color: var(--color-text-primary);
    text-align: center;
    text-decoration: none;
}
.learner-pagination__link--active {
    border-color: var(--color-primary);
    background: var(--color-primary-soft);
}
.learner-pagination__link--disabled {
    color: var(--color-text-muted);
}
@media (min-width: 48rem) {
    .learner-filters {
        grid-template-columns: 2fr 1fr 1fr auto;
    }
}
@media (max-width: 47.99rem) {
    .learner-table-wrap {
        overflow: visible;
        border: 0;
        background: transparent;
    }
    .learner-table,
    .learner-table tbody {
        display: block;
    }
    .learner-table thead {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
    }
    .learner-table tr {
        display: grid;
        gap: var(--space-3);
        margin-bottom: var(--space-4);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: var(--space-4);
        background: var(--color-surface);
    }
    .learner-table td {
        display: grid;
        grid-template-columns: minmax(7rem, 0.6fr) 1fr;
        gap: var(--space-3);
        border: 0;
        padding: 0;
    }
    .learner-table td::before {
        content: attr(data-label);
        color: var(--color-text-secondary);
        font-size: var(--font-size-caption);
        font-weight: var(--font-weight-semibold);
    }
    .learner-table td:first-child {
        display: block;
    }
    .learner-table td:first-child::before {
        display: none;
    }
}
</style>
