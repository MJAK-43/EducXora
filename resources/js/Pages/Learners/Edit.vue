<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import LearnerForm from '@/Components/Learners/LearnerForm.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
interface Option {
    value: string;
    label: string;
}
interface Learner {
    uuid: string;
    first_name: string;
    last_name: string;
    full_name: string;
    birth_date: string;
    phone: string;
    email: string | null;
    language: string;
    initial_level: string;
    has_photo: boolean;
    photo_url: string | null;
}
defineProps<{ learner: Learner; options: { levels: Option[]; languages: Option[] } }>();
</script>
<template>
    <Head :title="`Modifier ${learner.full_name}`" />
    <AppLayout page-title="Modifier l’apprenant">
        <PageHeader
            eyebrow="Apprenants"
            :title="learner.full_name"
            description="Mettez à jour uniquement les données administratives de base."
        />
        <LearnerForm
            :learner="learner"
            :options="options"
        />
    </AppLayout>
</template>
