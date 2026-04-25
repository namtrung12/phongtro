self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
  let payload = {};
  if (event.data) {
    try {
      payload = event.data.json();
    } catch (error) {
      payload = { body: event.data.text() };
    }
  }

  const title = typeof payload.title === 'string' && payload.title
    ? payload.title
    : 'Có người vừa quan tâm phòng';
  const body = typeof payload.body === 'string' && payload.body
    ? payload.body
    : 'Mở mục Thông báo để xem lead mới.';
  const data = payload && typeof payload.data === 'object' && payload.data
    ? payload.data
    : {};

  if (!data.url) {
    data.url = './?route=notifications';
  }

  event.waitUntil(self.registration.showNotification(title, {
    body,
    icon: './favicon.png',
    badge: './favicon.png',
    tag: typeof payload.tag === 'string' && payload.tag ? payload.tag : 'lead-interest',
    renotify: true,
    data,
  }));
});

self.addEventListener('pushsubscriptionchange', (event) => {
  event.waitUntil((async () => {
    const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const client of clients) {
      client.postMessage({ type: 'PUSH_SUBSCRIPTION_CHANGED' });
    }
  })());
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const data = event.notification.data || {};
  const targetUrl = typeof data.url === 'string' && data.url ? data.url : './?route=notifications';

  event.waitUntil((async () => {
    const allClients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const client of allClients) {
      if ('focus' in client) {
        try {
          await client.navigate(targetUrl);
        } catch (error) {
          // Ignore navigation errors and still try to focus.
        }
        await client.focus();
        return;
      }
    }

    if (self.clients.openWindow) {
      await self.clients.openWindow(targetUrl);
    }
  })());
});
