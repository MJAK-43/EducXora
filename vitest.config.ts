import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    test: {
        environment: 'jsdom',
        globals: true,
        include: ['tests/Frontend/**/*.spec.ts'],
        setupFiles: ['tests/Frontend/setup.ts'],
        coverage: {
            reporter: ['text', 'html'],
        },
    },
});
