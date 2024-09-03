<?php

namespace Operations\Notification\Interfaces;

use Operations\Notification\DTO\NotificationDataDTO;

/**
 * NotificationSender interface
 *
 * Separation of Concerns & Single Responsibility Principle
 * Purpose: Defines the contract for sending notifications.
 */
interface NotificationSender
{
    public function send(NotificationDataDTO $data): bool;
}