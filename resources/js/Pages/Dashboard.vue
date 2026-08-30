<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Activity, CalendarDays, CheckCircle2, GraduationCap, LibraryBig } from '@lucide/vue';
import UiBadge from '@/Components/Ui/UiBadge.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import UiEmptyState from '@/Components/Ui/UiEmptyState.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';

interface Session {
    uuid: string;
    groupName: string;
    teacherName: string;
    room: string | null;
    startsAt: string;
    endsAt: string;
    status: 'scheduled' | 'cancelled';
    attendanceUuid: string | null;
    attendanceStatus: 'not_started' | 'draft' | 'validated' | 'restricted';
}
interface Group {
    uuid: string;
    name: string;
    level: string;
    teacherName: string;
    learnerCount: number;
    capacity: number;
}
interface Learner {
    uuid: string;
    name: string;
    level: string;
    status: string;
    registeredOn: string;
}
interface ActivityItem {
    action: string;
    actor: string;
    occurredAt: string | null;
}
defineProps<{
    organization: { uuid: string; name: string; status: string };
    overview: {
        dateLabel: string;
        metrics: {
            activeLearners: number | null;
            activeGroups: number | null;
            sessionsToday: number;
            attendanceRate: number | null;
        };
        attendance: { present: number; absent: number; excused: number; pendingSessions: number };
        todaySessions: Session[];
        groups: Group[];
        recentLearners: Learner[];
        recentActivity: ActivityItem[];
        can: {
            viewLearners: boolean;
            viewGroups: boolean;
            viewSchedule: boolean;
            viewAttendance: boolean;
            viewAudit: boolean;
        };
    };
}>();
const attendanceLabels = {
    not_started: 'À pointer',
    draft: 'Brouillon',
    validated: 'Validée',
    restricted: 'Planifiée',
};
const attendanceTones = {
    not_started: 'neutral',
    draft: 'warning',
    validated: 'success',
    restricted: 'neutral',
} as const;
function humanAction(action: string): string {
    return action.replaceAll('.', ' · ').replaceAll('_', ' ');
}
</script>

<template>
    <Head title="Tableau de bord" />
    <AppLayout page-title="Tableau de bord">
        <PageHeader
            eyebrow="Pilotage quotidien"
            :title="organization.name"
            :description="`${overview.dateLabel} · Synthèse opérationnelle de votre organisation.`"
        >
            <template #actions
                ><Link
                    v-if="overview.can.viewSchedule"
                    class="dashboard-primary-link"
                    href="/schedule"
                    >Voir le planning</Link
                ></template
            >
        </PageHeader>

        <section
            class="dashboard-metrics"
            aria-label="Indicateurs clés"
        >
            <article
                v-if="overview.metrics.activeLearners !== null"
                class="dashboard-metric"
            >
                <span class="dashboard-icon"><GraduationCap aria-hidden="true" /></span>
                <div>
                    <span>Apprenants actifs</span
                    ><strong>{{ overview.metrics.activeLearners }}</strong>
                </div>
            </article>
            <article
                v-if="overview.can.viewGroups"
                class="dashboard-metric"
            >
                <span class="dashboard-icon"><LibraryBig aria-hidden="true" /></span>
                <div>
                    <span>Groupes actifs</span><strong>{{ overview.metrics.activeGroups }}</strong>
                </div>
            </article>
            <article
                v-if="overview.can.viewSchedule"
                class="dashboard-metric"
            >
                <span class="dashboard-icon"><CalendarDays aria-hidden="true" /></span>
                <div>
                    <span>Séances aujourd’hui</span
                    ><strong>{{ overview.metrics.sessionsToday }}</strong>
                </div>
            </article>
            <article
                v-if="overview.can.viewAttendance"
                class="dashboard-metric"
            >
                <span class="dashboard-icon"><CheckCircle2 aria-hidden="true" /></span>
                <div>
                    <span>Présence validée</span
                    ><strong>{{
                        overview.metrics.attendanceRate === null
                            ? '—'
                            : `${overview.metrics.attendanceRate} %`
                    }}</strong>
                </div>
            </article>
        </section>

        <div
            v-if="overview.can.viewSchedule || overview.can.viewAttendance"
            class="dashboard-grid dashboard-grid--primary"
        >
            <UiCard v-if="overview.can.viewSchedule">
                <template #header
                    ><div class="dashboard-heading">
                        <div>
                            <h2>Séances du jour</h2>
                            <p>{{ overview.attendance.pendingSessions }} feuille(s) à finaliser</p>
                        </div>
                        <Link
                            v-if="overview.can.viewSchedule"
                            href="/schedule"
                            >Tout voir</Link
                        >
                    </div></template
                >
                <div
                    v-if="overview.todaySessions.length"
                    class="dashboard-list"
                >
                    <article
                        v-for="session in overview.todaySessions"
                        :key="session.uuid"
                        class="dashboard-session"
                    >
                        <time
                            >{{ session.startsAt }}<small>{{ session.endsAt }}</small></time
                        >
                        <div>
                            <strong>{{ session.groupName }}</strong
                            ><span
                                >{{ session.teacherName
                                }}<template v-if="session.room">
                                    · {{ session.room }}</template
                                ></span
                            >
                        </div>
                        <UiBadge
                            :tone="
                                session.status === 'cancelled'
                                    ? 'danger'
                                    : attendanceTones[session.attendanceStatus]
                            "
                            >{{
                                session.status === 'cancelled'
                                    ? 'Annulée'
                                    : attendanceLabels[session.attendanceStatus]
                            }}</UiBadge
                        >
                        <Link
                            v-if="session.attendanceUuid && overview.can.viewAttendance"
                            :href="`/attendance/${session.attendanceUuid}`"
                            >Ouvrir</Link
                        >
                    </article>
                </div>
                <UiEmptyState
                    v-else
                    title="Aucune séance aujourd’hui"
                    message="Le planning de la journée est libre."
                />
            </UiCard>
            <UiCard v-if="overview.can.viewAttendance">
                <template #header
                    ><div class="dashboard-heading">
                        <div>
                            <h2>Présences du jour</h2>
                            <p>Feuilles validées uniquement</p>
                        </div>
                        <Link
                            v-if="overview.can.viewAttendance"
                            href="/attendance"
                            >Détail</Link
                        >
                    </div></template
                >
                <div class="attendance-summary">
                    <div>
                        <strong>{{ overview.attendance.present }}</strong
                        ><span>Présents</span>
                    </div>
                    <div>
                        <strong>{{ overview.attendance.absent }}</strong
                        ><span>Absents</span>
                    </div>
                    <div>
                        <strong>{{ overview.attendance.excused }}</strong
                        ><span>Justifiés</span>
                    </div>
                </div>
                <p
                    v-if="overview.metrics.attendanceRate === null"
                    class="dashboard-muted"
                >
                    Le taux sera affiché après validation d’une feuille.
                </p>
            </UiCard>
        </div>

        <div class="dashboard-grid dashboard-grid--secondary">
            <UiCard v-if="overview.can.viewGroups">
                <template #header
                    ><div class="dashboard-heading">
                        <div>
                            <h2>Groupes actifs</h2>
                            <p>Groupes les plus chargés</p>
                        </div>
                        <Link href="/groups">Tout voir</Link>
                    </div></template
                >
                <div
                    v-if="overview.groups.length"
                    class="dashboard-list"
                >
                    <Link
                        v-for="group in overview.groups"
                        :key="group.uuid"
                        class="dashboard-row"
                        :href="`/groups/${group.uuid}`"
                        ><div>
                            <strong>{{ group.name }}</strong
                            ><span>{{ group.level }} · {{ group.teacherName }}</span>
                        </div>
                        <span>{{ group.learnerCount }}/{{ group.capacity }}</span></Link
                    >
                </div>
                <UiEmptyState
                    v-else
                    title="Aucun groupe actif"
                    message="Les groupes actifs apparaîtront ici."
                />
            </UiCard>
            <UiCard v-if="overview.can.viewLearners">
                <template #header
                    ><div class="dashboard-heading">
                        <div>
                            <h2>Derniers apprenants</h2>
                            <p>Inscriptions récentes</p>
                        </div>
                        <Link href="/learners">Tout voir</Link>
                    </div></template
                >
                <div
                    v-if="overview.recentLearners.length"
                    class="dashboard-list"
                >
                    <Link
                        v-for="learner in overview.recentLearners"
                        :key="learner.uuid"
                        class="dashboard-row"
                        :href="`/learners/${learner.uuid}`"
                        ><div>
                            <strong>{{ learner.name }}</strong
                            ><span>Niveau {{ learner.level }} · {{ learner.registeredOn }}</span>
                        </div>
                        <UiBadge :tone="learner.status === 'active' ? 'success' : 'neutral'">{{
                            learner.status === 'active' ? 'Actif' : 'Archivé'
                        }}</UiBadge></Link
                    >
                </div>
                <UiEmptyState
                    v-else
                    title="Aucun apprenant"
                    message="Les inscriptions récentes apparaîtront ici."
                />
            </UiCard>
            <UiCard v-if="overview.can.viewAudit">
                <template #header
                    ><div class="dashboard-heading">
                        <div>
                            <h2>Activité récente</h2>
                            <p>Traçabilité de l’organisation</p>
                        </div>
                        <Link href="/organization/audit">Journal</Link>
                    </div></template
                >
                <div
                    v-if="overview.recentActivity.length"
                    class="dashboard-list"
                >
                    <article
                        v-for="(activity, index) in overview.recentActivity"
                        :key="`${activity.action}-${index}`"
                        class="dashboard-row"
                    >
                        <span class="dashboard-icon"><Activity aria-hidden="true" /></span>
                        <div>
                            <strong>{{ humanAction(activity.action) }}</strong
                            ><span>{{ activity.actor }} · {{ activity.occurredAt }}</span>
                        </div>
                    </article>
                </div>
                <UiEmptyState
                    v-else
                    title="Aucune activité récente"
                    message="Les actions auditées apparaîtront ici."
                />
            </UiCard>
        </div>
    </AppLayout>
</template>

<style scoped>
.dashboard-primary-link {
    display: inline-flex;
    min-height: 2.75rem;
    align-items: center;
    border-radius: var(--radius-md);
    padding: 0 var(--space-4);
    background: var(--color-primary);
    color: #fff;
    font-weight: var(--font-weight-semibold);
    text-decoration: none;
}
.dashboard-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: var(--space-4);
    margin-bottom: var(--space-6);
}
.dashboard-metric {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: var(--space-4);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--space-5);
    background: var(--color-surface);
    box-shadow: var(--shadow-sm);
}
.dashboard-icon {
    display: grid;
    width: 2.75rem;
    height: 2.75rem;
    flex: 0 0 auto;
    place-items: center;
    border-radius: var(--radius-md);
    background: var(--color-primary-soft);
    color: var(--color-primary);
}
.dashboard-icon :deep(svg) {
    width: 1.25rem;
}
.dashboard-metric div {
    display: grid;
    gap: var(--space-1);
}
.dashboard-metric span,
.dashboard-heading p,
.dashboard-list span,
.dashboard-muted {
    color: var(--color-text-secondary);
    font-size: var(--font-size-body-small);
}
.dashboard-metric strong {
    font-size: var(--font-size-heading-2);
    line-height: 1;
}
.dashboard-grid {
    display: grid;
    gap: var(--space-5);
    margin-bottom: var(--space-5);
}
.dashboard-grid--primary {
    grid-template-columns: minmax(0, 2fr) minmax(17rem, 1fr);
}
.dashboard-grid--secondary {
    grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr));
}
.dashboard-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}
.dashboard-heading h2,
.dashboard-heading p {
    margin: 0;
}
.dashboard-heading h2 {
    font-size: var(--font-size-heading-3);
}
.dashboard-heading p {
    margin-top: var(--space-1);
}
.dashboard-list {
    display: grid;
}
.dashboard-session,
.dashboard-row {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: var(--space-3);
    border-bottom: 1px solid var(--color-border);
    padding: var(--space-3) 0;
}
.dashboard-list > :last-child {
    border-bottom: 0;
}
.dashboard-session time {
    display: grid;
    width: 3.25rem;
    flex: 0 0 auto;
    font-weight: var(--font-weight-bold);
}
.dashboard-session time small {
    color: var(--color-text-muted);
    font-weight: normal;
}
.dashboard-session > div,
.dashboard-row > div {
    display: grid;
    min-width: 0;
    flex: 1;
}
.dashboard-row {
    color: var(--color-text-primary);
    text-decoration: none;
}
.dashboard-row:hover strong {
    color: var(--color-primary);
}
.attendance-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-3);
}
.attendance-summary div {
    display: grid;
    gap: var(--space-1);
    border-radius: var(--radius-md);
    padding: var(--space-4);
    background: var(--color-surface-secondary);
    text-align: center;
}
.attendance-summary strong {
    font-size: var(--font-size-heading-2);
}
.attendance-summary span {
    color: var(--color-text-secondary);
    font-size: var(--font-size-caption);
}
@media (max-width: 69.99rem) {
    .dashboard-metrics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .dashboard-grid--primary {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 39.99rem) {
    .dashboard-metrics {
        grid-template-columns: 1fr;
    }
    .dashboard-session {
        align-items: flex-start;
        flex-wrap: wrap;
    }
    .dashboard-grid--secondary {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
