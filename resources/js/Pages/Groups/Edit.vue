<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import GroupForm from '@/Components/Groups/GroupForm.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
interface Option {
    value: string;
    label: string;
}
interface Group {
    uuid: string;
    name: string;
    language: string;
    level: string;
    capacity: number;
    members_count: number;
    teacher_membership_uuid: string;
}
defineProps<{
    group: Group;
    options: { levels: Option[]; languages: Option[]; teachers: Option[] };
}>();
</script>
<template>
    <Head :title="`Modifier ${group.name}`" />
    <AppLayout page-title="Modifier le groupe">
        <PageHeader
            eyebrow="Groupes"
            :title="group.name"
            description="La capacité ne peut pas devenir inférieure à l’effectif actuel."
        />
        <GroupForm
            :group="group"
            :options="options"
        />
    </AppLayout>
</template>
