import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';

export default [
    {
        ignores: [
            'public/**',
            'storage/**',
            'vendor/**',
            'node_modules/**',
            'dist/**',
            'build/**',
            'resources/js/ziggy.js',
        ],
    },
    js.configs.recommended,
    ...pluginVue.configs['flat/essential'],
    {
        files: ['resources/js/**/*.{js,vue}'],
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.node,
                route: 'readonly',
            },
            ecmaVersion: 'latest',
            sourceType: 'module',
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/no-mutating-props': 'off',
            'vue/no-v-text-v-html-on-component': 'off',
            'no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
        },
    },
];
