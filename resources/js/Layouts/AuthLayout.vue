<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Landmark } from '@lucide/vue';
</script>

<template>
    <main class="auth-shell">
        <section
            class="auth-panel"
            aria-labelledby="auth-title"
        >
            <Link
                href="/"
                class="auth-brand"
                aria-label="EduXora — accueil"
            >
                <span class="app-sidebar__mark"
                    ><Landmark
                        :size="20"
                        aria-hidden="true"
                /></span>
                <span>EduXora</span>
            </Link>
            <slot />
        </section>
    </main>
</template>
