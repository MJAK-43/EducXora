<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import LearnerForm from '@/Components/Learners/LearnerForm.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
interface Option {
    value: string;
    label: string;
}
defineProps<{ options: { levels: Option[]; languages: Option[] } }>();
</script>
<template>
    <Head title="Nouvel apprenant" />
    <AppLayout page-title="Nouvel apprenant">
        <PageHeader
            eyebrow="Apprenants"
            title="Créer un apprenant"
            description="Les informations sont enregistrées uniquement dans l’organisation active."
        />
        <LearnerForm :options="options" />
    </AppLayout>
</template>
