<?php

namespace Operations\Notification\Notifications;

use Operations\Notification\DTO\NotificationDataDTO;
use Operations\Notification\Interfaces\NotificationSender;
use Operations\Notification\MessagesClient;

/**
 * EmailNotificationSender class
 *
 * Separation of Concerns & Single Responsibility Principle
 * Purpose: Sends email notifications.
 */
class EmailNotificationSender implements NotificationSender
{
    public const MESSAGE_TYPE_EMAIL = 0; // Using a class constant

    public function send(NotificationDataDTO $data): bool
    {
        return MessagesClient::sendMessage([
            self::MESSAGE_TYPE_EMAIL => [
                'emailFrom' => $data->from,
                'emailTo' => $data->to,
                'subject' => $data->subject,
                'message' => $data->message,
            ],
        ], $data->resellerId, $data->clientId ?? 0, $data->event, $data->subEvent ?? '');
    }
}
