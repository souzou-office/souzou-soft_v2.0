import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{ts,tsx}',
        './app/Http/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', '"Yu Gothic"', '"Noto Sans JP"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', 'Consolas', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                brand: {
                    navy: '#1A3A5C',
                    blue: '#1565C0',
                },
                status: {
                    completed: '#10B981',
                    'in-progress': '#3B82F6',
                    warning: '#F59E0B',
                    danger: '#EF4444',
                    'not-started': '#D1D5DB',
                    'not-applicable': '#F3F4F6',
                },
                cell: {
                    'completed-bg': '#DCFCE7',
                    'in-progress-bg': '#DBEAFE',
                    'next-bg': '#FEF3C7',
                    'overdue-bg': '#FEE2E2',
                    'na-bg': '#F3F4F6',
                },
            },
            spacing: {
                row: '36px',
            },
        },
    },
    plugins: [],
};
