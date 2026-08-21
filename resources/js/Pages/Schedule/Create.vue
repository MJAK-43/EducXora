<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CourseSessionForm from '@/Components/Schedule/CourseSessionForm.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
interface Option {
    value: string;
    label: string;
}
defineProps<{ options: { groups: Option[]; teachers: Option[] }; initialDate: string }>();
</script>
<template>
    <Head title="Nouvelle séance" /><AppLayout page-title="Nouvelle séance"
        ><PageHeader
            eyebrow="Planning"
            title="Planifier une séance"
            description="Les horaires sont interprétés dans le fuseau de l’organisation." /><CourseSessionForm
            :options="options"
            :initial-date="initialDate"
    /></AppLayout>
</template>
