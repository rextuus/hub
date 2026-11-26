// Nur die Journey nutzt Tailwind (config/packages/symfonycasts_tailwind.yaml). Keine Anpassungen: wie das CDN.
/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ['./templates/tool/journey/**/*.html.twig'],
    theme: {
        extend: {},
    },
    plugins: [],
};
