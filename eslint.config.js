import eslint from '@eslint/js';
import eslintConfigPrettier from 'eslint-config-prettier';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    { ignores: ['node_modules', 'public/build', 'vendor', 'storage', 'coverage'] },
    eslint.configs.recommended,
    ...tseslint.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    eslintConfigPrettier,
    {
        files: ['resources/**/*.{ts,vue}', 'tests/Frontend/**/*.ts'],
        languageOptions: {
            globals: globals.browser,
            parserOptions: {
                parser: tseslint.parser,
                projectService: true,
                extraFileExtensions: ['.vue'],
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/require-default-prop': 'off',
            '@typescript-eslint/no-misused-promises': 'off',
        },
    },
    {
        files: ['*.{js,ts}', 'vite.config.js', 'vitest.config.ts'],
        languageOptions: {
            globals: globals.node,
        },
    },
);
