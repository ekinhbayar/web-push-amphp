self.addEventListener('push', function(event) {
    console.log('[Service Worker] Push Received.');

    const payload = event.data.json();

    const title = payload.title;

    // Check other options
    // https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration/showNotification
    const notificationOptions = {
        body: payload.body
    };

    const notificationPromise = self.registration.showNotification(title, notificationOptions);
    event.waitUntil(notificationPromise);
});

self.addEventListener('notificationclick', function(event) {
    console.log('[Service Worker] Notification click Received.');

    event.notification.close();

    event.waitUntil(
        clients.openWindow('https://developers.google.com/web/')
    );
});
