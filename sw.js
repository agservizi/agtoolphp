self.addEventListener('push', function(event) {
    let data = {};
    if (event.data) {
        data = event.data.json();
    }
    const title = data.title || 'Notifica AGTool Finance';
    const options = {
        body: data.body || 'Hai una nuova notifica.',
        icon: '/assets/img/logo-192.png',
        badge: '/assets/img/badge-72.png',
        data: data.url || '/notifications'
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(function(clientList) {
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if (client.url === event.notification.data && 'focus' in client)
                    return client.focus();
            }
            if (clients.openWindow) {
                return clients.openWindow(event.notification.data);
            }
        })
    );
});
