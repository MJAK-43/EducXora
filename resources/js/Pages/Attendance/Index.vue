<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import UiBadge from '@/Components/Ui/UiBadge.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';

interface Session {
    uuid: string;
    group_name: string;
    teacher_name: string;
    date_label: string;
    start_time: string;
    end_time: string;
    session_status: string;
    attendance_uuid: string | null;
    attendance_status: 'non_pointed' | 'draft' | 'validated';
    can_start: boolean;
    can_open: boolean;
}
interface Page<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
}
const props = defineProps<{
    sessions: Page<Session>;
    filters: { from: string; to: string; status: string };
}>();
const filters = reactive({ ...props.filters });
const labels = { non_pointed: 'Non pointé', draft: 'Brouillon', validated: 'Validé' };
function apply(): void {
    router.get('/attendance', filters, { preserveState: true, replace: true });
}
function paginationLabel(label: string): string {
    return label.replace('&laquo;', '‹').replace('&raquo;', '›');
}
</script>

<template>
    <Head title="Présences" />
    <AppLayout page-title="Présences">
        <PageHeader
            eyebrow="Suivi pédagogique"
            title="Feuilles de présence"
            description="Pointages par séance, validation et historique auditable."
        />
        <UiCard>
            <form
                class="filters"
                @submit.prevent="apply"
            >
                <label
                    >Du<input
                        v-model="filters.from"
                        type="date"
                /></label>
                <label
                    >Au<input
                        v-model="filters.to"
                        type="date"
                /></label>
                <label
                    >État<select v-model="filters.status">
                        <option value="">Tous</option>
                        <option value="non_pointed">Non pointé</option>
                        <option value="draft">Brouillon</option>
                        <option value="validated">Validé</option>
                    </select></label
                >
                <button type="submit">Filtrer</button>
            </form>
        </UiCard>
        <div class="session-list">
            <UiCard
                v-for="session in sessions.data"
                :key="session.uuid"
            >
                <article class="session">
                    <div>
                        <strong>{{ session.group_name }}</strong>
                        <p>
                            {{ session.date_label }} · {{ session.start_time }}–{{
                                session.end_time
                            }}
                        </p>
                        <small>{{ session.teacher_name }}</small>
                    </div>
                    <div class="session__actions">
                        <UiBadge
                            :tone="
                                session.attendance_status === `validated`
                                    ? `success`
                                    : session.attendance_status === `draft`
                                      ? `warning`
                                      : `neutral`
                            "
                            >{{ labels[session.attendance_status] }}</UiBadge
                        >
                        <Link
                            v-if="session.can_open && session.attendance_uuid"
                            :href="`/attendance/${session.attendance_uuid}`"
                            >Ouvrir</Link
                        >
                        <Link
                            v-else-if="session.can_start && session.session_status !== `cancelled`"
                            method="post"
                            as="button"
                            :href="`/attendance/sessions/${session.uuid}`"
                            >Pointer</Link
                        >
                    </div>
                </article>
            </UiCard>
            <UiCard v-if="!sessions.data.length"
                ><p class="muted">Aucune séance pour cette période.</p></UiCard
            >
        </div>
        <nav
            v-if="sessions.links.length > 3"
            class="pagination"
            aria-label="Pagination"
        >
            <Link
                v-for="link in sessions.links"
                :key="link.label"
                :href="link.url || ``"
                :class="{ active: link.active, disabled: !link.url }"
                >{{ paginationLabel(link.label) }}</Link
            >
        </nav>
    </AppLayout>
</template>

<style scoped>
.filters,
.session,
.session__actions,
.pagination {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
    align-items: end;
}
.filters label {
    display: grid;
    gap: var(--space-1);
    color: var(--color-text-secondary);
}
input,
select,
.filters button,
.session__actions a,
.session__actions button,
.pagination a {
    min-height: 2.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 0 var(--space-3);
    background: var(--color-surface);
    color: var(--color-text-primary);
}
.filters button,
.session__actions a,
.session__actions button {
    background: var(--color-primary);
    color: white;
    text-decoration: none;
}
.session-list {
    display: grid;
    gap: var(--space-4);
    margin-top: var(--space-5);
}
.session {
    justify-content: space-between;
    align-items: center;
}
.session p {
    margin: var(--space-1) 0;
}
.session small,
.muted {
    color: var(--color-text-secondary);
}
.session__actions {
    align-items: center;
}
.pagination {
    margin-top: var(--space-5);
}
.pagination a {
    display: grid;
    place-items: center;
    text-decoration: none;
}
.pagination .active {
    border-color: var(--color-primary);
}
.pagination .disabled {
    pointer-events: none;
    opacity: 0.5;
}
@media (max-width: 39.99rem) {
    .filters,
    .session,
    .session__actions {
        align-items: stretch;
        flex-direction: column;
    }
    .filters > * {
        width: 100%;
    }
}
</style>
