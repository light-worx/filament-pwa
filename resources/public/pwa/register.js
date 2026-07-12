// resources/public/pwa/register.js

if ('serviceWorker' in navigator) {
    const version = document.querySelector('meta[name="app-version"]')?.content ?? '1.0.0';

    navigator.serviceWorker.register(`/service-worker.js?v=${encodeURIComponent(version)}`)
        .then(registration => {

            // A new SW appears mid-session (tab left open across a deploy)
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;
                if (!newWorker) return;

                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        showUpdateButton(registration);
                    }
                });
            });

            // A new SW was already waiting from a previous page load
            if (registration.waiting && navigator.serviceWorker.controller) {
                showUpdateButton(registration);
            }
        });

    // Reload once the new SW actually takes control
    let refreshing = false;
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (refreshing) return;
        refreshing = true;
        window.location.reload();
    });
}

function showUpdateButton(registration) {
    const btn = document.getElementById('updateBtn');
    if (!btn) return;

    btn.classList.remove('d-none');
    btn.addEventListener('click', () => {
        registration.waiting?.postMessage({ type: 'SKIP_WAITING' });
    }, { once: true });
}