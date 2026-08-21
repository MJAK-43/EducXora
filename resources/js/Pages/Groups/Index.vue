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
interface Option {
    value: string;
    label: string;
}
interface Group {
    uuid: string;
    name: string;
    language_label: string;
    level: string;
    capacity: number;
    members_count: number;
    teacher_name: string;
    status: string;
}
interface PageLink {
    url: string | null;
    label: string;
    active: boolean;
}
const props = defineProps<{
    groups: {
        data: Group[];
        links: PageLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: { search: string; status: string; level: string; teacher: string };
    options: { levels: Option[]; teachers: Option[] };
    can: { create: boolean; archive: boolean; restore: boolean };
}>();
const filters = reactive({ ...props.filters });
const statusOptions = [
    { value: 'active', label: 'Actifs' },
    { value: 'archived', label: 'Archivés' },
    { value: 'all', label: 'Tous' },
];
const levelOptions = [{ value: '', label: 'Tous les niveaux' }, ...props.options.levels];
const teacherOptions = [{ value: '', label: 'Tous les enseignants' }, ...props.options.teachers];
function search(): void {
    router.get('/groups', filters, { preserveState: true, replace: true });
}
function archive(group: Group): void {
    if (window.confirm(`Archiver ${group.name} ?`))
        router.patch(`/groups/${group.uuid}/archive`, {}, { preserveScroll: true });
}
function restore(group: Group): void {
    if (window.confirm(`Restaurer ${group.name} ?`))
        router.patch(`/groups/${group.uuid}/restore`, {}, { preserveScroll: true });
}
function paginationLabel(label: string): string {
    return label.replace('&laquo; Previous', 'Précédent').replace('Next &raquo;', 'Suivant');
}
</script>

<template>
    <Head title="Groupes" />
    <AppLayout page-title="Groupes">
        <PageHeader
            eyebrow="Organisation pédagogique"
            title="Groupes"
            description="Effectifs, niveaux et enseignants responsables de l’organisation active."
        >
            <template #actions
                ><Link
                    v-if="can.create"
                    class="primary-link"
                    href="/groups/create"
                    ><Plus
                        :size="17"
                        aria-hidden="true"
                    />Nouveau groupe</Link
                ></template
            >
        </PageHeader>
        <UiCard>
            <form
                class="group-filters"
                role="search"
                @submit.prevent="search"
            >
                <UiInput
                    v-model="filters.search"
                    label="Recherche"
                    placeholder="Nom du groupe"
                />
                <UiSelect
                    v-model="filters.status"
                    label="Statut"
                    :options="statusOptions"
                    @update:model-value="search"
                />
                <UiSelect
                    v-model="filters.level"
                    label="Niveau"
                    :options="levelOptions"
                    @update:model-value="search"
                />
                <UiSelect
                    v-model="filters.teacher"
                    label="Enseignant"
                    :options="teacherOptions"
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
            v-if="!groups.data.length"
            title="Aucun groupe trouvé"
            message="Modifiez les filtres ou créez le premier groupe."
        />
        <div
            v-else
            class="group-results"
            aria-live="polite"
        >
            <p class="muted">{{ groups.from }}–{{ groups.to }} sur {{ groups.total }} groupe(s)</p>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Groupe</th>
                            <th>Langue / niveau</th>
                            <th>Enseignant</th>
                            <th>Effectif</th>
                            <th>Statut</th>
                            <th><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="group in groups.data"
                            :key="group.uuid"
                        >
                            <td data-label="Groupe">
                                <Link
                                    class="name-link"
                                    :href="`/groups/${group.uuid}`"
                                    >{{ group.name }}</Link
                                >
                            </td>
                            <td data-label="Langue / niveau">
                                {{ group.language_label }} · {{ group.level }}
                            </td>
                            <td data-label="Enseignant">{{ group.teacher_name }}</td>
                            <td data-label="Effectif">
                                {{ group.members_count }} / {{ group.capacity }}
                            </td>
                            <td data-label="Statut">
                                <UiBadge
                                    :tone="group.status === 'active' ? 'success' : 'neutral'"
                                    >{{ group.status === 'active' ? 'Actif' : 'Archivé' }}</UiBadge
                                >
                            </td>
                            <td data-label="Actions">
                                <div class="inline-actions">
                                    <Link :href="`/groups/${group.uuid}`">Voir</Link
                                    ><button
                                        v-if="group.status === 'active' && can.archive"
                                        class="text-button"
                                        type="button"
                                        @click="archive(group)"
                                    >
                                        Archiver</button
                                    ><button
                                        v-if="group.status === 'archived' && can.restore"
                                        class="text-button"
                                        type="button"
                                        @click="restore(group)"
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
                v-if="groups.links.length > 3"
                class="pagination"
                aria-label="Pagination des groupes"
            >
                <template
                    v-for="link in groups.links"
                    :key="link.label"
                    ><Link
                        v-if="link.url"
                        class="pagination__link"
                        :class="{ 'pagination__link--active': link.active }"
                        :href="link.url"
                        >{{ paginationLabel(link.label) }}</Link
                    ><span
                        v-else
                        class="pagination__link pagination__link--disabled"
                        >{{ paginationLabel(link.label) }}</span
                    ></template
                >
            </nav>
        </div>
    </AppLayout>
</template>

<style scoped>
.group-filters {
    display: grid;
    align-items: end;
    gap: var(--space-4);
}
.group-results {
    margin-top: var(--space-6);
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
.name-link {
    color: var(--color-primary);
    font-weight: var(--font-weight-semibold);
}
.text-button {
    border: 0;
    padding: 0;
    background: none;
    color: var(--color-primary);
    text-decoration: underline;
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
    color: var(--color-text-primary);
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
    .group-filters {
        grid-template-columns: 1.4fr repeat(3, minmax(8rem, 1fr)) auto;
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
        grid-template-columns: 7.5rem 1fr;
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
