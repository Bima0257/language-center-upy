import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: 'var(--color-primary)',
                'primary-container': 'var(--color-primary-container)',
                secondary: 'var(--color-secondary)',
                'secondary-container': 'var(--color-secondary-container)',
                'deep-space': 'var(--color-deep-space)',
                'pastel-blue': 'var(--color-pastel-blue)',
                'pastel-purple': 'var(--color-pastel-purple)',
                'pastel-peach': 'var(--color-pastel-peach)',
                'text-heading': 'var(--color-text-heading)',
                'text-body': 'var(--color-text-body)',
                'text-muted': 'var(--color-text-muted)',
                surface: 'var(--color-surface)',
                'surface-white': 'var(--color-surface-white)',
                'surface-container': 'var(--color-surface-container)',
                'surface-container-low': 'var(--color-surface-container-low)',
                'surface-container-highest': 'var(--color-surface-container-highest)',
                'surface-container-lowest': 'var(--color-surface-container-lowest)',
                'outline-variant': 'var(--color-outline-variant)',
                'on-primary': 'var(--color-on-primary)',
                'on-primary-container': 'var(--color-on-primary-container)',
                background: 'var(--color-background)',
                canvas: 'var(--color-canvas)',
                'track-neutral': 'var(--color-track-neutral)',
                'error-red': 'var(--color-error-red)',
                'text-pastel': 'var(--color-text-pastel)',
                'icon-pastel': 'var(--color-icon-pastel)',
            },
            borderRadius: {
                '2xl': '20px',
            },
            spacing: {
                gutter: '1rem',
                'card-gap': '1.5rem',
                'inner-padding': '1.25rem',
                'app-margin': '2.5rem',
            },
            fontSize: {
                'title-lg': ['18px', { lineHeight: '1.4', fontWeight: '600' }],
                'body-md': ['14px', { lineHeight: '1.6', fontWeight: '400' }],
                'label-md': ['13px', { lineHeight: '1', fontWeight: '500' }],
                'headline-lg': [
                    '32px',
                    { lineHeight: '1.2', letterSpacing: '-0.02em', fontWeight: '700' },
                ],
                'headline-md': ['24px', { lineHeight: '1.3', fontWeight: '700' }],
                'headline-lg-mobile': [
                    '24px',
                    { lineHeight: '1.2', fontWeight: '700' },
                ],
            },
            boxShadow: {
                'standard': '0 4px 20px rgba(0, 0, 0, 0.04)',
                'soft': '0 4px 20px rgba(0,0,0,0.04)',
                'app-frame': '0 20px 80px rgba(0,0,0,0.2)',
            },
        },
    },

    plugins: [forms, typography],
};
