const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],

    daisyui: {
        themes: [{
            mytheme: {
                "primary": "#0b4d1d",
                "secondary": "#233303",
                "accent": "#5a8c5e",
                "neutral": "#3D372B",
                "base-100": "#F8FCF0",
                "info": "#91DAFA",
                "success": "#1CD945",
                "warning": "#FFB700",
                "error": "#F74931",
            },
        },
        {
            main: {
                "primary": "#263640",
                "secondary": "#263640",
                "accent": "rgba(0,255,0,255)",
                "accent-focus": "#66ff66",
                "neutral": "#3D372B",
                "base-100": "#F7FAFC",
                "info": "#91DAFA",
                "success": "#1CD945",
                "warning": "#FFB700",
                "error": "#F74931",
            },
        }, "dark"],
    },

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                'glow': '0 0 20px 1px',
                'lightGlow': '0 0 20px -7px',
            },
            colors: {
                tertiary: '#FFBF00',
                button: '#95B3C6',
                buttonFocus: '#AFC6D4',
                statusOpen: '#00ff00',
                statusClosed: '#cc0000',
                statusReview: '#ffe500',
                statusComplete: '#1500D6',
            }
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/aspect-ratio'),
        require("daisyui")
    ],
};
