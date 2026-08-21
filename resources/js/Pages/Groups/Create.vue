<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import GroupForm from '@/Components/Groups/GroupForm.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
interface Option {
    value: string;
    label: string;
}
defineProps<{ options: { levels: Option[]; languages: Option[]; teachers: Option[] } }>();
</script>
<template>
    <Head title="Nouveau groupe" />
    <AppLayout page-title="Nouveau groupe">
        <PageHeader
            eyebrow="Groupes"
            title="Créer un groupe"
            description="Constituez un groupe homogène dans l’organisation active."
        />
        <GroupForm :options="options" />
    </AppLayout>
</template>
