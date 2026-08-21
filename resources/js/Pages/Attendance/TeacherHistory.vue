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
    date_label: string;
    start_time: string;
    end_time: string;
    attendance_uuid: string | null;
    attendance_status: string;
    teacher_attendance: string | null;
    teacher_attendance_label: string;
}
interface Page<T> {
    data: T[];
}
const props = defineProps<{
    teacher: { uuid: string; name: string };
    sessions: Page<Session>;
    filters: { from: string; to: string };
}>();
const filters = reactive({ ...props.filters });
function apply(): void {
    router.get(`/attendance/teachers/${props.teacher.uuid}`, filters, {
        preserveState: true,
        replace: true,
    });
}
</script>
<template>
    <Head :title="`Séances · ${teacher.name}`" />
    <AppLayout page-title="Historique enseignant">
        <PageHeader
            eyebrow="Présences"
            :title="teacher.name"
            description="Séances planifiées et présence enseignant sur la période."
            ><template #actions
                ><Link
                    class="outline-link"
                    href="/attendance"
                    >Retour</Link
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
        <div class="sessions">
            <UiCard
                v-for="session in sessions.data"
                :key="session.uuid"
                ><article>
                    <div>
                        <strong>{{ session.group_name }}</strong>
                        <p>
                            {{ session.date_label }} · {{ session.start_time }}–{{
                                session.end_time
                            }}
                        </p>
                    </div>
                    <div>
                        <UiBadge
                            :tone="
                                session.teacher_attendance === `present`
                                    ? `success`
                                    : session.teacher_attendance === `absent`
                                      ? `danger`
                                      : `neutral`
                            "
                            >{{ session.teacher_attendance_label }}</UiBadge
                        ><Link
                            v-if="session.attendance_uuid"
                            :href="`/attendance/${session.attendance_uuid}`"
                            >Voir</Link
                        >
                    </div>
                </article></UiCard
            ><UiCard v-if="!sessions.data.length"
                ><p class="muted">Aucune séance sur cette période.</p></UiCard
            >
        </div>
    </AppLayout>
</template>
<style scoped>
.filters,
.sessions article,
.sessions article > div {
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
.outline-link,
.sessions a {
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
.sessions {
    display: grid;
    gap: var(--space-4);
    margin-top: var(--space-5);
}
.sessions article {
    justify-content: space-between;
    align-items: center;
}
.sessions article > div {
    align-items: center;
}
.sessions p {
    margin: var(--space-1) 0;
}
.muted {
    color: var(--color-text-secondary);
}
@media (max-width: 39.99rem) {
    .filters,
    .sessions article {
        align-items: stretch;
        flex-direction: column;
    }
    .filters > * {
        width: 100%;
    }
}
</style>
