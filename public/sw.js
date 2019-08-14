self.addEventListener('push', function (event) {
  if (!(self.Notification && self.Notification.permission === 'granted')) {
      return;
  }

  var data = {};
  console.log(event);
  if (event.data) {
      console.log(event.data);
      data = event.data.json();
  }

  console.log('Notification Received:');
  console.log(data);

  var title = data.title;
  var message = data.message;
  var icon = "images/push-icon.jpg";
  
  event.waitUntil(self.registration.showNotification(title, {
      body: message,
      icon: icon,
      badge: icon
  }));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
});
