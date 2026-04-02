function fetchNotifications() {
    fetch("../../?p=notification_api").then(response => response.json()).then(data => {
        data.forEach(function(e) {
            self.registration.showNotification("sourceDESK", {
                body: e
            });
        });
    });
}

setInterval(fetchNotifications, 10000);