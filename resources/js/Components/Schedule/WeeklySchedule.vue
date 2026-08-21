<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import UiBadge from '@/Components/Ui/UiBadge.vue';

interface Session {
    uuid: string;
    group_name: string;
    teacher_name: string;
    room: string | null;
    date: string;
    date_label: string;
    start_time: string;
    end_time: string;
    status: 'scheduled' | 'cancelled';
}
const props = defineProps<{
    sessions: Session[];
    can: { update: boolean; cancel: boolean };
    weekStart: string;
}>();
const dayFormatter = new Intl.DateTimeFormat('fr-FR', {
    weekday: 'long',
    day: '2-digit',
    month: '2-digit',
    timeZone: 'UTC',
});
const days = computed(() =>
    Array.from({ length: 7 }, (_, index) => {
        const date = new Date(`${props.weekStart}T00:00:00Z`);
        date.setUTCDate(date.getUTCDate() + index);
        const value = date.toISOString().slice(0, 10);
        return {
            value,
            label: dayFormatter.format(date),
            sessions: props.sessions.filter((session) => session.date === value),
        };
    }),
);
function cancel(session: Session): void {
    if (window.confirm(`Annuler la séance de ${session.group_name} ?`))
        router.patch(`/schedule/${session.uuid}/cancel`, {}, { preserveScroll: true });
}
</script>

<template>
    <div
        class="weekly-schedule"
        aria-live="polite"
    >
        <section
            v-for="day in days"
            :key="day.value"
            class="schedule-day"
        >
            <h2>{{ day.label }}</h2>
            <p
                v-if="!day.sessions.length"
                class="schedule-day__empty"
            >
                Aucune séance
            </p>
            <article
                v-for="session in day.sessions"
                :key="session.uuid"
                class="session-card"
                :class="{ 'session-card--cancelled': session.status === 'cancelled' }"
            >
                <div class="session-card__time">
                    {{ session.start_time }}–{{ session.end_time }}
                </div>
                <strong>{{ session.group_name }}</strong>
                <span>{{ session.teacher_name }}</span
                ><span>{{ session.room || 'Salle non définie' }}</span>
                <UiBadge :tone="session.status === 'scheduled' ? 'success' : 'neutral'">{{
                    session.status === 'scheduled' ? 'Planifiée' : 'Annulée'
                }}</UiBadge>
                <div
                    v-if="session.status === 'scheduled'"
                    class="session-card__actions"
                >
                    <Link
                        v-if="can.update"
                        :href="`/schedule/${session.uuid}/edit`"
                        >Modifier</Link
                    >
                    <button
                        v-if="can.cancel"
                        type="button"
                        @click="cancel(session)"
                    >
                        Annuler
                    </button>
                </div>
            </article>
        </section>
    </div>
</template>

<style scoped>
.weekly-schedule {
    display: grid;
    gap: var(--space-4);
}
.schedule-day {
    min-width: 0;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--space-3);
    background: var(--color-surface);
}
.schedule-day h2 {
    margin: 0 0 var(--space-3);
    font-size: var(--font-size-body);
    text-transform: capitalize;
}
.schedule-day__empty {
    color: var(--color-text-muted);
    font-size: var(--font-size-caption);
}
.session-card {
    display: grid;
    gap: var(--space-1);
    margin-top: var(--space-3);
    border-left: 3px solid var(--color-primary);
    border-radius: var(--radius-md);
    padding: var(--space-3);
    background: var(--color-primary-soft);
}
.session-card--cancelled {
    border-left-color: var(--color-border-strong);
    background: var(--color-surface-secondary);
    opacity: 0.8;
}
.session-card span {
    overflow-wrap: anywhere;
    color: var(--color-text-secondary);
    font-size: var(--font-size-caption);
}
.session-card__time {
    font-weight: var(--font-weight-bold);
}
.session-card__actions {
    display: flex;
    gap: var(--space-3);
    margin-top: var(--space-2);
}
.session-card__actions button {
    border: 0;
    padding: 0;
    background: none;
    color: var(--color-danger);
    text-decoration: underline;
}
@media (min-width: 64rem) {
    .weekly-schedule {
        grid-template-columns: repeat(7, minmax(0, 1fr));
    }
}
</style>
