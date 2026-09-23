import { router } from '@inertiajs/vue3';

const SPOOFED_METHODS = ['put', 'patch', 'delete'];

// The production host delivers PUT/PATCH/DELETE to PHP as GET, so they are sent
// as POST and Laravel restores the real method from X-HTTP-Method-Override.
export function initializeMethodOverride(): void {
    router.on('before', (event) => {
        const visit = event.detail.visit;

        if (!SPOOFED_METHODS.includes(visit.method)) {
            return;
        }

        visit.headers = {
            ...visit.headers,
            'X-HTTP-Method-Override': visit.method.toUpperCase(),
        };
        visit.method = 'post';
    });
}
