import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
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
                primary: '#010020',
                'primary-container': '#191a3b',
                secondary: '#5647c8',
                'secondary-container': '#6f62e2',
                'deep-space': '#1e1b3b',
                'pastel-blue': '#d8e9fb',
                'pastel-purple': '#e7defb',
                'pastel-peach': '#fbe1c6',
                'text-heading': '#161328',
                'text-body': '#6e6e85',
                'text-muted': '#9b9ab0',
                surface: '#f9f9fc',
                'surface-white': '#ffffff',
                'surface-container': '#edeef1',
                'surface-container-low': '#f3f3f6',
                'surface-container-highest': '#e2e2e5',
                'outline-variant': '#c8c5cf',
                'on-primary': '#ffffff',
                'on-primary-container': '#8282a9',
                background: '#191a3b',
                canvas: '#f9f9fc',
                'track-neutral': '#e3e3ea',
                'error-red': '#ff5a5f',
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

    plugins: [forms],
};
