<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import QuestionForm from '@/Components/Pedagogy/QuestionForm.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';

interface Question {
    uuid: string;
    source: string;
    source_label: string;
    level: string;
    prompt: string;
    choices: Record<'A' | 'B' | 'C' | 'D', string>;
    correct_choice: string;
    status: string;
    readonly: boolean;
}
defineProps<{
    question: Question;
    levels: Array<{ value: string; label: string }>;
    can: { update: boolean; disable: boolean; enable: boolean };
}>();
</script>

<template>
    <Head title="Question de positionnement" />
    <AppLayout page-title="Question de positionnement">
        <PageHeader
            eyebrow="Banque de questions"
            :title="question.readonly ? 'Question système' : 'Question de l’organisation'"
            :description="`${question.source_label} · ${question.level} · ${question.status === 'active' ? 'Active' : 'Inactive'}`"
        />
        <QuestionForm
            :question="question"
            :levels="levels"
            :can="can"
        />
    </AppLayout>
</template>
