<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import UiBadge from '@/Components/Ui/UiBadge.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import GroupMemberManager from '@/Components/Groups/GroupMemberManager.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
interface Member {
    uuid: string;
    full_name: string;
    phone: string;
    assigned_at_label: string;
}
interface Candidate {
    value: string;
    label: string;
    phone: string;
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
    members: Member[];
}
interface AttendanceSummary {
    sessions: number;
    records: number;
    present: number;
    absent: number;
    excused: number;
    rate: number;
}
const props = defineProps<{
    group: Group;
    candidates: Candidate[];
    attendanceSummary: AttendanceSummary | null;
    can: { update: boolean; archive: boolean; restore: boolean; manageLearners: boolean };
}>();
function archive(): void {
    if (window.confirm(`Archiver ${props.group.name} ?`))
        router.patch(`/groups/${props.group.uuid}/archive`);
}
function restore(): void {
    router.patch(`/groups/${props.group.uuid}/restore`);
}
</script>
<template>
    <Head :title="group.name" />
    <AppLayout :page-title="group.name">
        <PageHeader
            eyebrow="Groupes"
            :title="group.name"
            description="Composition et responsabilité pédagogique du groupe."
        >
            <template #actions
                ><div class="inline-actions">
                    <Link
                        v-if="can.update && group.status === 'active'"
                        class="outline-link"
                        :href="`/groups/${group.uuid}/edit`"
                        >Modifier</Link
                    ><button
                        v-if="can.archive && group.status === 'active'"
                        class="danger-link"
                        type="button"
                        @click="archive"
                    >
                        Archiver</button
                    ><button
                        v-if="can.restore && group.status === 'archived'"
                        class="outline-link"
                        type="button"
                        @click="restore"
                    >
                        Restaurer
                    </button>
                </div></template
            >
        </PageHeader>
        <UiCard
            v-if="attendanceSummary"
            class="attendance-summary"
            ><template #header><h2>Présences validées</h2></template>
            <div>
                <p>
                    <strong>{{ attendanceSummary.rate }} %</strong><span>Taux de présence</span>
                </p>
                <p>
                    <strong>{{ attendanceSummary.sessions }}</strong
                    ><span>Séances</span>
                </p>
                <p>
                    <strong>{{ attendanceSummary.present }}</strong
                    ><span>Présent</span>
                </p>
                <p>
                    <strong>{{ attendanceSummary.absent }}</strong
                    ><span>Absent</span>
                </p>
                <p>
                    <strong>{{ attendanceSummary.excused }}</strong
                    ><span>Excusé</span>
                </p>
            </div></UiCard
        >
        <div class="group-detail">
            <UiCard
                ><template #header><h2>Informations</h2></template>
                <dl>
                    <div>
                        <dt>Statut</dt>
                        <dd>
                            <UiBadge :tone="group.status === 'active' ? 'success' : 'neutral'">{{
                                group.status === 'active' ? 'Actif' : 'Archivé'
                            }}</UiBadge>
                        </dd>
                    </div>
                    <div>
                        <dt>Langue / niveau</dt>
                        <dd>{{ group.language_label }} · {{ group.level }}</dd>
                    </div>
                    <div>
                        <dt>Enseignant</dt>
                        <dd>{{ group.teacher_name }}</dd>
                    </div>
                    <div>
                        <dt>Effectif</dt>
                        <dd>{{ group.members_count }} / {{ group.capacity }}</dd>
                    </div>
                </dl></UiCard
            >
            <UiCard
                ><template #header><h2>Apprenants</h2></template
                ><GroupMemberManager
                    v-if="can.manageLearners"
                    :group-uuid="group.uuid"
                    :members="group.members"
                    :candidates="candidates"
                    :disabled="group.status === 'archived'"
                />
                <div v-else>
                    <article
                        v-for="member in group.members"
                        :key="member.uuid"
                        class="readonly-member"
                    >
                        <strong>{{ member.full_name }}</strong
                        ><span>{{ member.phone }}</span>
                    </article>
                    <p
                        v-if="!group.members.length"
                        class="muted"
                    >
                        Aucun apprenant.
                    </p>
                </div></UiCard
            >
        </div>
    </AppLayout>
</template>
<style scoped>
.group-detail {
    display: grid;
    gap: var(--space-6);
}
.attendance-summary {
    margin-bottom: var(--space-6);
}
.attendance-summary > div {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(7rem, 1fr));
    gap: var(--space-3);
}
.attendance-summary p,
.attendance-summary strong,
.attendance-summary span {
    display: block;
    margin: 0;
}
.attendance-summary span {
    color: var(--color-text-secondary);
}
.group-detail dl {
    display: grid;
    gap: var(--space-4);
    margin: 0;
}
.group-detail dt {
    color: var(--color-text-secondary);
    font-size: var(--font-size-caption);
}
.group-detail dd {
    margin: var(--space-1) 0 0;
    font-weight: var(--font-weight-semibold);
}
.outline-link,
.danger-link {
    display: inline-flex;
    align-items: center;
    min-height: 2.5rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    padding: 0 var(--space-4);
    background: var(--color-surface);
    color: var(--color-text-primary);
    text-decoration: none;
}
.danger-link {
    border-color: var(--color-danger);
    color: var(--color-danger);
}
.readonly-member {
    display: flex;
    justify-content: space-between;
    gap: var(--space-3);
    border-bottom: 1px solid var(--color-border);
    padding: var(--space-3) 0;
}
.readonly-member span,
.muted {
    color: var(--color-text-secondary);
}
@media (min-width: 64rem) {
    .group-detail {
        grid-template-columns: minmax(16rem, 0.7fr) minmax(0, 2fr);
    }
}
</style>
