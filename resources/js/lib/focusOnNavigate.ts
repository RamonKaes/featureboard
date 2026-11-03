import { router } from '@inertiajs/vue3';

/**
 * A page change in a single-page app is silent for screen readers and leaves the
 * keyboard focus on a removed element, so move the focus to the new content.
 * Visits that stay on the same path, like voting or filtering, keep the focus.
 */
export function initializeFocusOnNavigate(): void {
    let currentPath = window.location.pathname;

    router.on('navigate', () => {
        if (window.location.pathname === currentPath) {
            return;
        }

        currentPath = window.location.pathname;
        document.getElementById('main-content')?.focus({ preventScroll: true });
    });
}
