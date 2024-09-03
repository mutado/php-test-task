<?php

namespace Operations\Notification\Services;

use Exception;
use Operations\Notification\Interfaces\NotificationSender;
use Operations\Notification\Notifications\EmailNotificationSender;
use Operations\Notification\Notifications\SmsNotificationSender;

/**
 * NotificationFactory class
 *
 * Pattern: Factory Method
 * Purpose: used to create the appropriate notification sender based on the type.
 * This allows for easy extension if new notification types are added in the future.
 */
class NotificationFactory
{
    public function createSender(string $type): NotificationSender
    {
        return match ($type) {
            'email' => new EmailNotificationSender(),
            'sms' => new SmsNotificationSender(),
            default => throw new Exception("Unknown notification type: $type"),
        };
    }
}