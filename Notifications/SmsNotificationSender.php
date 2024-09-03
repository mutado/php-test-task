<?php

namespace Operations\Notification\Notifications;

use Operations\Notification\DTO\NotificationData;
use Operations\Notification\Interfaces\NotificationSender;
use Operations\Notification\NotificationManager;

/**
 * SmsNotificationSender class
 *
 * Separation of Concerns & Single Responsibility Principle
 * Purpose: Sends SMS notifications.
 */
class SmsNotificationSender implements NotificationSender
{
    public function send(NotificationData $data): bool
    {
        if (!$data->resellerId) {
            $data->errorText = 'Reseller ID is required';
            return false;
        }
        return NotificationManager::send(
            $data->resellerId,
            $data->clientId,
            $data->event,
            $data->subEvent ?? '',
            $data->templateData,
            $data->errorText
        );
    }
}