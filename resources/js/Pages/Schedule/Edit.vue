<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CourseSessionForm from '@/Components/Schedule/CourseSessionForm.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
interface Option {
    value: string;
    label: string;
}
interface Session {
    uuid: string;
    group_uuid: string;
    group_name: string;
    teacher_membership_uuid: string;
    room: string | null;
    date: string;
    start_time: string;
    end_time: string;
}
defineProps<{ session: Session; options: { groups: Option[]; teachers: Option[] } }>();
</script>
<template>
    <Head :title="`Modifier ${session.group_name}`" /><AppLayout page-title="Modifier la séance"
        ><PageHeader
            eyebrow="Planning"
            :title="session.group_name"
            description="Toute collision de groupe, d’enseignant ou de salle sera refusée." /><CourseSessionForm
            :session="session"
            :options="options"
    /></AppLayout>
</template>
