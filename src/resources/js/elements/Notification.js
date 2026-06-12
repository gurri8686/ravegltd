import React from 'react';
import 'react-notifications/lib/notifications.css';
import { NotificationContainer, NotificationManager } from 'react-notifications';

function Notification(type, message) {
    return () => {
        switch (type) {
            case 'info':
                NotificationManager.info(message, 3000);
                break;
            case 'success':
                NotificationManager.success(message, 'Success', 3000);
                break;
            case 'warning':
                NotificationManager.warning(message, 'Warning', 3000);
                break;
            case 'error':
                NotificationManager.error(message, 'Error', 3000, () => {
                    //alert('callback');
                });
                break;
        }
    };
}

export default Notification;