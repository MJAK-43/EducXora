<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import UiBadge from '@/Components/Ui/UiBadge.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
interface Correction {
    before_label: string;
    after_label: string;
    reason: string;
    actor: string;
}
interface Entry {
    date_label: string;
    group_name: string;
    session_uuid: string;
    status: string;
    status_label: string;
    corrections: Correction[];
}
interface Page<T> {
    data: T[];
}
interface Learner {
    uuid: string;
    full_name: string;
}
interface Summary {
    sessions: number;
    present: number;
    absent: number;
    excused: number;
    rate: number;
}
const props = defineProps<{
    learner: Learner;
    history: Page<Entry>;
    summary: Summary;
    filters: { from: string; to: string };
}>();
const filters = reactive({ ...props.filters });
function apply(): void {
    router.get(`/learners/${props.learner.uuid}/attendance`, filters, {
        preserveState: true,
        replace: true,
    });
}
function tone(status: string): 'success' | 'danger' | 'warning' {
    return status === 'present' ? 'success' : status === 'absent' ? 'danger' : 'warning';
}
</script>
<template>
    <Head :title="`Présences · ${learner.full_name}`" />
    <AppLayout page-title="Historique des présences">
        <PageHeader
            eyebrow="Apprenant"
            :title="learner.full_name"
            description="Historique validé et taux de présence sur la période."
            ><template #actions
                ><Link
                    class="outline-link"
                    :href="`/learners/${learner.uuid}`"
                    >Retour au dossier</Link
                ></template
            ></PageHeader
        >
        <UiCard
            ><form
                class="filters"
                @submit.prevent="apply"
            >
                <label
                    >Du<input
                        v-model="filters.from"
                        type="date" /></label
                ><label
                    >Au<input
                        v-model="filters.to"
                        type="date" /></label
                ><button type="submit">Actualiser</button>
            </form></UiCard
        >
        <div class="stats">
            <UiCard
                ><strong>{{ summary.rate }} %</strong><span>Taux de présence</span></UiCard
            ><UiCard
                ><strong>{{ summary.present }}</strong
                ><span>Présent</span></UiCard
            ><UiCard
                ><strong>{{ summary.absent }}</strong
                ><span>Absent</span></UiCard
            ><UiCard
                ><strong>{{ summary.excused }}</strong
                ><span>Excusé</span></UiCard
            >
        </div>
        <UiCard class="history"
            ><template #header
                ><h2>{{ summary.sessions }} séance(s) validée(s)</h2></template
            >
            <article
                v-for="entry in history.data"
                :key="`${entry.session_uuid}-${entry.date_label}`"
            >
                <div>
                    <strong>{{ entry.date_label }} · {{ entry.group_name }}</strong>
                    <details v-if="entry.corrections.length">
                        <summary>{{ entry.corrections.length }} correction(s)</summary>
                        <p
                            v-for="item in entry.corrections"
                            :key="`${item.reason}-${item.actor}`"
                        >
                            {{ item.before_label }} → {{ item.after_label }} · {{ item.reason }} ·
                            {{ item.actor }}
                        </p>
                    </details>
                </div>
                <UiBadge :tone="tone(entry.status)">{{ entry.status_label }}</UiBadge>
            </article>
            <p
                v-if="!history.data.length"
                class="muted"
            >
                Aucune présence validée sur cette période.
            </p></UiCard
        >
    </AppLayout>
</template>
<style scoped>
.filters,
.stats,
.history article {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
    align-items: end;
}
.filters label {
    display: grid;
    gap: var(--space-1);
}
input,
.filters button,
.outline-link {
    min-height: 2.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 0 var(--space-3);
    background: var(--color-surface);
    color: var(--color-text-primary);
    text-decoration: none;
}
.filters button {
    background: var(--color-primary);
    color: white;
}
.stats {
    margin-top: var(--space-5);
    align-items: stretch;
}
.stats > * {
    flex: 1;
    min-width: 9rem;
}
.stats strong,
.stats span {
    display: block;
}
.stats strong {
    font-size: var(--font-size-title);
}
.stats span,
.muted,
details {
    color: var(--color-text-secondary);
}
.history {
    margin-top: var(--space-5);
}
.history article {
    justify-content: space-between;
    align-items: center;
    padding: var(--space-3) 0;
    border-bottom: 1px solid var(--color-border);
}
details p {
    font-size: var(--font-size-caption);
}
@media (max-width: 39.99rem) {
    .filters {
        align-items: stretch;
        flex-direction: column;
    }
    .filters > * {
        width: 100%;
    }
}
</style>
