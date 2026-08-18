<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Boxes, CheckCircle2, ShieldCheck } from '@lucide/vue';

import UiBadge from '@/Components/Ui/UiBadge.vue';
import UiCard from '@/Components/Ui/UiCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Layouts/PageHeader.vue';
</script>

<template>
    <Head title="Foundation" />
    <AppLayout page-title="Foundation">
        <PageHeader
            eyebrow="Phase 1"
            title="Le socle EduXora est opérationnel"
            description="Une fondation sobre, accessible et prête à accueillir les futurs domaines métier, sans anticiper leur implémentation."
        />

        <section
            class="foundation-hero"
            aria-labelledby="foundation-title"
        >
            <div class="foundation-hero__content">
                <UiBadge tone="success">Foundation build</UiBadge>
                <h2
                    id="foundation-title"
                    class="text-display"
                    style="margin-top: var(--space-5)"
                >
                    Gestion intelligente des centres de langue
                </h2>
                <p class="foundation-hero__lead">
                    Laravel, Vue, Inertia et l'infrastructure locale partagent désormais un socle
                    cohérent et vérifiable.
                </p>
                <div
                    class="foundation-hero__meta"
                    aria-label="État du socle"
                >
                    <UiBadge tone="info">Laravel 13</UiBadge>
                    <UiBadge tone="info">Vue 3 + TypeScript</UiBadge>
                    <UiBadge tone="info">PostgreSQL + Redis</UiBadge>
                </div>
            </div>
        </section>

        <div class="foundation-grid">
            <UiCard>
                <div class="foundation-status">
                    <span class="foundation-status__icon"
                        ><CheckCircle2
                            :size="21"
                            aria-hidden="true"
                    /></span>
                    <h2>Exécutable</h2>
                    <p>
                        Le backend, Inertia et le build frontend sont prêts pour un workflow
                        reproductible.
                    </p>
                </div>
            </UiCard>
            <UiCard>
                <div class="foundation-status">
                    <span class="foundation-status__icon"
                        ><Boxes
                            :size="21"
                            aria-hidden="true"
                    /></span>
                    <h2>Composable</h2>
                    <p>
                        Les composants fondamentaux et les tokens évitent les variations visuelles
                        dispersées.
                    </p>
                </div>
            </UiCard>
            <UiCard>
                <div class="foundation-status">
                    <span class="foundation-status__icon"
                        ><ShieldCheck
                            :size="21"
                            aria-hidden="true"
                    /></span>
                    <h2>Prêt à sécuriser</h2>
                    <p>
                        Les garde-fous Foundation précèdent l'authentification et l'isolation de la
                        Phase 2.
                    </p>
                </div>
            </UiCard>
        </div>
    </AppLayout>
</template>
