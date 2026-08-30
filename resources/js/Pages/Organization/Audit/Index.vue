<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import UiBadge from '@/Components/Ui/UiBadge.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import UiEmptyState from '@/Components/Ui/UiEmptyState.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
interface LogItem {
    id: number;
    action: string;
    actor: { name: string; email: string } | null;
    resourceType: string;
    resourceId: string | null;
    occurredAt: string | null;
}
interface Paginated<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
}
const props = defineProps<{
    logs: Paginated<LogItem>;
    filters: { search: string; action: string; from: string; to: string };
}>();
const filters = reactive({ ...props.filters });
function apply(): void {
    router.get('/organization/audit', filters, { preserveState: true, replace: true });
}
function clear(): void {
    Object.assign(filters, { search: '', action: '', from: '', to: '' });
    apply();
}
function paginationLabel(label: string): string {
    return label.replace('&laquo;', '‹').replace('&raquo;', '›');
}
function humanAction(action: string): string {
    return action.replaceAll('.', ' · ').replaceAll('_', ' ');
}
</script>

<template>
    <Head title="Journal d’audit" />
    <AppLayout page-title="Journal d’audit">
        <PageHeader
            eyebrow="Administration"
            title="Journal d’audit"
            description="Consultez les actions sensibles de votre organisation. Ce journal est en lecture seule."
        />
        <UiCard>
            <form
                class="audit-filters"
                aria-label="Filtres du journal"
                @submit.prevent="apply"
            >
                <label
                    ><span>Recherche</span
                    ><input
                        v-model="filters.search"
                        type="search"
                        placeholder="Action, ressource ou auteur"
                /></label>
                <label
                    ><span>Action exacte</span
                    ><input
                        v-model="filters.action"
                        type="text"
                        placeholder="learner.created"
                /></label>
                <label
                    ><span>Du</span
                    ><input
                        v-model="filters.from"
                        type="date"
                /></label>
                <label
                    ><span>Au</span
                    ><input
                        v-model="filters.to"
                        type="date"
                /></label>
                <div class="audit-filters__actions">
                    <button type="submit">Filtrer</button
                    ><button
                        type="button"
                        class="audit-secondary"
                        @click="clear"
                    >
                        Effacer
                    </button>
                </div>
            </form>
        </UiCard>
        <div
            v-if="logs.data.length"
            class="audit-list"
            aria-live="polite"
        >
            <UiCard
                v-for="log in logs.data"
                :key="log.id"
            >
                <article class="audit-entry">
                    <div class="audit-entry__main">
                        <strong>{{ humanAction(log.action) }}</strong
                        ><span
                            >{{ log.actor?.name ?? 'Système'
                            }}<template v-if="log.actor?.email">
                                · {{ log.actor.email }}</template
                            ></span
                        >
                    </div>
                    <div class="audit-entry__resource">
                        <UiBadge tone="neutral">{{ log.resourceType || 'Système' }}</UiBadge
                        ><span v-if="log.resourceId">#{{ log.resourceId }}</span>
                    </div>
                    <time>{{ log.occurredAt }}</time>
                </article>
            </UiCard>
        </div>
        <UiCard
            v-else
            class="audit-empty"
            ><UiEmptyState
                title="Aucune activité trouvée"
                message="Modifiez les filtres ou revenez après une action auditable."
        /></UiCard>
        <nav
            v-if="logs.links.length > 3"
            class="audit-pagination"
            aria-label="Pagination du journal"
        >
            <Link
                v-for="link in logs.links"
                :key="link.label"
                :href="link.url || ''"
                :aria-current="link.active ? 'page' : undefined"
                :class="{ active: link.active, disabled: !link.url }"
                >{{ paginationLabel(link.label) }}</Link
            >
        </nav>
    </AppLayout>
</template>

<style scoped>
.audit-filters {
    display: grid;
    grid-template-columns: minmax(12rem, 2fr) minmax(10rem, 1fr) repeat(2, minmax(9rem, 1fr));
    gap: var(--space-4);
    align-items: end;
}
.audit-filters label {
    display: grid;
    gap: var(--space-1);
    color: var(--color-text-secondary);
    font-size: var(--font-size-body-small);
}
.audit-filters input {
    min-height: 2.75rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 0 var(--space-3);
    background: var(--color-surface);
    color: var(--color-text-primary);
}
.audit-filters__actions {
    display: flex;
    gap: var(--space-2);
    grid-column: 1/-1;
}
.audit-filters button,
.audit-pagination a {
    min-height: 2.75rem;
    border: 1px solid var(--color-primary);
    border-radius: var(--radius-md);
    padding: 0 var(--space-4);
    background: var(--color-primary);
    color: #fff;
    font-weight: var(--font-weight-semibold);
}
.audit-filters .audit-secondary {
    background: var(--color-surface);
    color: var(--color-primary);
}
.audit-list {
    display: grid;
    gap: var(--space-3);
    margin-top: var(--space-5);
}
.audit-entry {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(10rem, 1fr) auto;
    gap: var(--space-4);
    align-items: center;
}
.audit-entry__main {
    display: grid;
    gap: var(--space-1);
    min-width: 0;
}
.audit-entry__main span,
.audit-entry__resource span,
.audit-entry time {
    color: var(--color-text-secondary);
    font-size: var(--font-size-body-small);
}
.audit-entry__resource {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
    align-items: center;
}
.audit-empty {
    margin-top: var(--space-5);
}
.audit-pagination {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
    margin-top: var(--space-5);
}
.audit-pagination a {
    display: grid;
    min-width: 2.75rem;
    place-items: center;
    background: var(--color-surface);
    color: var(--color-text-primary);
    text-decoration: none;
}
.audit-pagination a.active {
    background: var(--color-primary);
    color: #fff;
}
.audit-pagination a.disabled {
    pointer-events: none;
    opacity: 0.45;
}
@media (max-width: 63.99rem) {
    .audit-filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .audit-entry {
        grid-template-columns: 1fr auto;
    }
    .audit-entry time {
        grid-column: 2;
        grid-row: 1;
    }
    .audit-entry__resource {
        grid-column: 1/-1;
    }
}
@media (max-width: 39.99rem) {
    .audit-filters {
        grid-template-columns: 1fr;
    }
    .audit-entry {
        grid-template-columns: 1fr;
    }
    .audit-entry time,
    .audit-entry__resource {
        grid-column: 1;
        grid-row: auto;
    }
    .audit-filters__actions,
    .audit-filters button {
        width: 100%;
    }
}
</style>
