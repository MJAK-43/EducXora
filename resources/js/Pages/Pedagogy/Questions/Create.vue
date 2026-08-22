<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import QuestionForm from '@/Components/Pedagogy/QuestionForm.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';

defineProps<{ levels: Array<{ value: string; label: string }> }>();
</script>

<template>
    <Head title="Nouvelle question" />
    <AppLayout page-title="Nouvelle question">
        <PageHeader
            eyebrow="Suivi pédagogique"
            title="Créer une question"
            description="Ajoutez une question allemande propre à l’organisation active."
        />
        <QuestionForm :levels="levels" />
    </AppLayout>
</template>
