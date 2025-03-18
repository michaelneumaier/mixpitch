import preset from '../../../../vendor/filament/filament/tailwind.config.preset';

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter var', 'sans-serif'],
            },
            colors: {
                danger: {
                    50: '#FFF5F5',
                    100: '#FED7D7',
                    200: '#FEB2B2',
                    300: '#FC8181',
                    400: '#F56565',
                    500: '#E53E3E',
                    600: '#C53030',
                    700: '#9B2C2C',
                    800: '#822727',
                    900: '#63171B',
                },
            },
        },
    },
}; 