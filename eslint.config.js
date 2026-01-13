import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';
import prettier from 'eslint-config-prettier/flat';
import importPlugin from 'eslint-plugin-import';
import perfectionist from 'eslint-plugin-perfectionist';
import unicorn from 'eslint-plugin-unicorn';
import vue from 'eslint-plugin-vue';

export default defineConfigWithVueTs(
    vue.configs['flat/essential'],
    vueTsConfigs.recommended,
    {
        ignores: ['vendor', 'node_modules', 'public', 'bootstrap/ssr', 'tailwind.config.js', 'resources/js/components/ui/*'],
    },
    {
        plugins: {
            import: importPlugin,
            unicorn,
            perfectionist,
        },
        rules: {
            // Vue rules
            'vue/multi-word-component-names': 'off',
            'vue/no-unused-vars': 'error',
            'vue/no-mutating-props': 'error',
            'vue/no-v-html': 'warn',
            'vue/require-default-prop': 'error',
            'vue/no-setup-props-destructure': 'error',
            'vue/block-order': ['error', {
                order: ['script', 'template', 'style'],
            }],

            // TypeScript rules
            '@typescript-eslint/no-explicit-any': 'off', // Keep off for now, can enable later
            '@typescript-eslint/consistent-type-imports': ['error', {
                prefer: 'type-imports',
                fixStyle: 'inline-type-imports',
            }],
            '@typescript-eslint/no-floating-promises': 'error',
            '@typescript-eslint/array-type': ['error', {
                default: 'array',
            }],

            // Import hygiene
            'import/order': 'off', // Disabled in favor of perfectionist/sort-imports
            'import/no-cycle': ['error', {
                maxDepth: 10,
            }],

            // Unicorn (modern JS)
            'unicorn/prefer-optional-catch-binding': 'error',
            'unicorn/no-array-reduce': 'warn',
            'unicorn/prefer-switch': ['error', {
                minimumCases: 3,
            }],
            'unicorn/filename-case': ['error', {
                case: 'kebabCase',
                ignore: [/^[A-Z]/, /\.vue$/],
            }],

            // Perfectionist (sorting)
            'perfectionist/sort-imports': ['error', {
                type: 'natural',
                order: 'asc',
                groups: [
                    'type',
                    ['builtin', 'external'],
                    'internal',
                    ['parent', 'sibling', 'index'],
                ],
                newlinesBetween: 1,
            }],
            'perfectionist/sort-named-imports': ['error', {
                type: 'natural',
                order: 'asc',
            }],
        },
    },
    prettier,
);
