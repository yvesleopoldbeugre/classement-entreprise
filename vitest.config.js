import { defineConfig } from 'vitest/config';

// Tests du JavaScript front (resources/js) dans un DOM simulé : sans serveur ni navigateur.
export default defineConfig({
    test: {
        environment: 'jsdom',
        include: ['tests/js/**/*.test.js'],
        restoreMocks: true,
    },
});
