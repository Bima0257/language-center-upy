import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    theme: {
        extend: {
            fontFamily: {
                sans: ['Urbanist', ...defaultTheme.fontFamily.sans],
                serif: ['Lora', ...defaultTheme.fontFamily.serif],
            },
            borderRadius: {
                '2xl': '12px',
            },
            spacing: {
                gutter: '1rem',
                'card-gap': '1.5rem',
                'inner-padding': '1.25rem',
                'app-margin': '2.5rem',
            },
            fontSize: {
                xs: ['14px', { lineHeight: '1.45' }],
                sm: ['15px', { lineHeight: '1.5' }],
                'title-lg': ['20px', { lineHeight: '1.4', fontWeight: '600' }],
                'body-md': ['16px', { lineHeight: '1.6', fontWeight: '400' }],
                'label-md': ['14px', { lineHeight: '1.1', fontWeight: '500' }],
                'headline-lg': [
                    '36px',
                    { lineHeight: '1.2', letterSpacing: '-0.02em', fontWeight: '700' },
                ],
                'headline-md': ['28px', { lineHeight: '1.3', fontWeight: '700' }],
                'headline-lg-mobile': [
                    '28px',
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
};
