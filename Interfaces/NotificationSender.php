<?php

namespace Operations\Notification\Interfaces;

use Operations\Notification\DTO\NotificationData;

/**
 * NotificationSender interface
 *
 * Separation of Concerns & Single Responsibility Principle
 * Purpose: Defines the contract for sending notifications.
 */
interface NotificationSender
{
    public function send(NotificationData $data): bool;
}