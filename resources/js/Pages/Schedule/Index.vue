<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarPlus, ChevronLeft, ChevronRight } from '@lucide/vue';
import { reactive } from 'vue';
import WeeklySchedule from '@/Components/Schedule/WeeklySchedule.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import UiSelect from '@/Components/Ui/UiSelect.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
interface Option {
    value: string;
    label: string;
}
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
    week: { start: string; end: string; previous: string; next: string };
    filters: { group: string; teacher: string };
    options: { groups: Option[]; teachers: Option[] };
    can: { create: boolean; update: boolean; cancel: boolean };
}>();
const filters = reactive({ ...props.filters });
const groupOptions = [{ value: '', label: 'Tous les groupes' }, ...props.options.groups];
const teacherOptions = [{ value: '', label: 'Tous les enseignants' }, ...props.options.teachers];
function applyFilters(): void {
    router.get(
        '/schedule',
        { week: props.week.start, ...filters },
        { preserveState: true, replace: true },
    );
}
function weekUrl(week: string): string {
    const query = new URLSearchParams({ week, ...filters });
    return `/schedule?${query.toString()}`;
}
function label(date: string): string {
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(`${date}T00:00:00Z`));
}
</script>
<template>
    <Head title="Planning" />
    <AppLayout page-title="Planning">
        <PageHeader
            eyebrow="Organisation pédagogique"
            title="Planning hebdomadaire"
            :description="`Du ${label(week.start)} au ${label(week.end)}`"
        >
            <template #actions
                ><Link
                    v-if="can.create"
                    class="primary-link"
                    href="/schedule/create"
                    ><CalendarPlus
                        :size="17"
                        aria-hidden="true"
                    />Nouvelle séance</Link
                ></template
            >
        </PageHeader>
        <UiCard
            ><div class="schedule-toolbar">
                <nav
                    class="week-navigation"
                    aria-label="Changer de semaine"
                >
                    <Link
                        :href="weekUrl(week.previous)"
                        aria-label="Semaine précédente"
                        ><ChevronLeft aria-hidden="true" /></Link
                    ><Link :href="weekUrl(new Date().toISOString().slice(0, 10))">Aujourd’hui</Link
                    ><Link
                        :href="weekUrl(week.next)"
                        aria-label="Semaine suivante"
                        ><ChevronRight aria-hidden="true"
                    /></Link>
                </nav>
                <div class="schedule-filters">
                    <UiSelect
                        v-model="filters.group"
                        label="Groupe"
                        :options="groupOptions"
                        @update:model-value="applyFilters"
                    /><UiSelect
                        v-model="filters.teacher"
                        label="Enseignant"
                        :options="teacherOptions"
                        @update:model-value="applyFilters"
                    />
                </div></div
        ></UiCard>
        <WeeklySchedule
            :sessions="sessions"
            :can="can"
            :week-start="week.start"
        />
    </AppLayout>
</template>
<style scoped>
.schedule-toolbar,
.schedule-filters,
.week-navigation {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    gap: var(--space-3);
}
.schedule-toolbar {
    justify-content: space-between;
}
.schedule-filters {
    flex: 1;
    justify-content: flex-end;
}
.schedule-filters > * {
    min-width: min(100%, 14rem);
}
.week-navigation a,
.primary-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    min-height: 2.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 0 var(--space-3);
    color: var(--color-text-primary);
    text-decoration: none;
}
.primary-link {
    border-color: var(--color-primary);
    background: var(--color-primary);
    color: white;
}
@media (max-width: 39.99rem) {
    .schedule-toolbar,
    .schedule-filters {
        align-items: stretch;
        flex-direction: column;
    }
    .week-navigation {
        justify-content: space-between;
    }
}
</style>
