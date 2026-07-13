// resources/public/register.js
if ('serviceWorker' in navigator) {
    const version = document.querySelector('meta[name="app-version"]')?.content || '1.0.0';

    // A worker was already controlling this page BEFORE this registration —
    // distinguishes a genuine update from the very first install, which
    // also fires 'controllerchange' but has nothing to update FROM.
    const hadController = !!navigator.serviceWorker.controller;

    navigator.serviceWorker.register('/service-worker.js?v=' + encodeURIComponent(version))
        .then(registration => {
            // Don't wait for the browser's own update-check schedule —
            // check against the network right away.
            registration.update();

            // Re-check whenever the tab regains focus, so a long-lived
            // open tab still notices a deploy that happened while
            // backgrounded.
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    registration.update();
                }
            });
        });

    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (! hadController) return; // first install — nothing to prompt about

        const btn = document.getElementById('updateBtn');
        if (btn) btn.classList.remove('d-none');
    });
}