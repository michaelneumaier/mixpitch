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
        }, "dark"],
    },

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/aspect-ratio'),
        require("daisyui")
    ],
};
