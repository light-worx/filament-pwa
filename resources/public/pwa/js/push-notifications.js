// push-notifications.js - Complete version for PWA

console.log('📱 Push notifications script loading...');

// Get VAPID key from meta tag
const VAPID_PUBLIC_KEY = document.querySelector('meta[name="vapid-key"]')?.content || '';

if (!VAPID_PUBLIC_KEY) {
    console.error('❌ VAPID public key not found in meta tag');
    console.log('Available meta tags:', Array.from(document.querySelectorAll('meta')).map(m => m.name));
} else {
    console.log('✅ VAPID key loaded:', VAPID_PUBLIC_KEY.substring(0, 20) + '...');
}

/**
 * Convert VAPID key from base64 to Uint8Array
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

/**
 * Request notification permission and subscribe to push notifications
 */
async function subscribeToPushNotifications() {
    try {
        console.log('🔔 Starting push notification subscription...');
        
        // Check if service workers are supported
        if (!('serviceWorker' in navigator)) {
            console.error('❌ Service Workers not supported');
            throw new Error('Service Workers not supported in this browser');
        }

        // Check if Push API is supported
        if (!('PushManager' in window)) {
            console.error('❌ Push notifications not supported');
            throw new Error('Push notifications not supported in this browser');
        }

        if (!VAPID_PUBLIC_KEY) {
            console.error('❌ VAPID key missing');
            throw new Error('VAPID public key not configured');
        }

        console.log('📱 Requesting notification permission...');
        // Request notification permission
        const permission = await Notification.requestPermission();
        console.log('Permission result:', permission);
        
        if (permission !== 'granted') {
            console.log('❌ Notification permission denied');
            throw new Error('Notification permission denied');
        }

        console.log('⏳ Waiting for service worker...');
        // Wait for service worker to be ready
        const registration = await navigator.serviceWorker.ready;
        console.log('✅ Service worker ready:', registration);

        // Check if already subscribed
        let subscription = await registration.pushManager.getSubscription();

        if (!subscription) {
            console.log('📝 Creating new subscription...');
            // Subscribe to push notifications
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
            });

            console.log('✅ Push notification subscription created');
        } else {
            console.log('✅ Already subscribed to push notifications');
        }

        // Send subscription to your Laravel backend
        await saveSubscription(subscription);

        return subscription;

    } catch (error) {
        console.error('❌ Error subscribing to push notifications:', error);
        throw error;
    }
}

/**
 * Save the subscription to your Laravel backend
 */
async function saveSubscription(subscription) {
    try {
        console.log('💾 Saving subscription to server...');
        const response = await fetch('/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify(subscription.toJSON())
        });

        if (!response.ok) {
            throw new Error('Failed to save subscription: ' + response.status);
        }

        const data = await response.json();
        console.log('✅ Subscription saved successfully:', data);

    } catch (error) {
        console.error('❌ Error saving subscription:', error);
        throw error;
    }
}

/**
 * Unsubscribe from push notifications
 */
async function unsubscribeFromPushNotifications() {
    try {
        console.log('🔕 Unsubscribing from push notifications...');
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription) {
            await subscription.unsubscribe();
            
            // Remove subscription from backend
            await fetch('/push/unsubscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint
                })
            });

            console.log('✅ Unsubscribed from push notifications');
        }
    } catch (error) {
        console.error('❌ Error unsubscribing:', error);
        throw error;
    }
}

/**
 * Check current subscription status
 */
async function checkSubscriptionStatus() {
    try {
        if (!('serviceWorker' in navigator)) {
            return { subscribed: false, permission: 'default' };
        }

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        
        return {
            subscribed: !!subscription,
            permission: Notification.permission
        };
    } catch (error) {
        console.error('❌ Error checking subscription:', error);
        return { subscribed: false, permission: 'default' };
    }
}

// Export functions for use in your app
window.pushNotifications = {
    subscribe: subscribeToPushNotifications,
    unsubscribe: unsubscribeFromPushNotifications,
    checkStatus: checkSubscriptionStatus
};

console.log('✅ window.pushNotifications initialized:', window.pushNotifications);