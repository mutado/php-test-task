<?php

namespace Operations\Notification\Enums;

/**
 * NotificationType enum
 *
 * Depending on the version of PHP, enums may not be available.
 * In this case, we can use constants or a class with constants to achieve the same result.
 * Purpose: To define the different types of notifications.
 */
enum NotificationType: int {
    case NEW = 1;
    case CHANGE = 2;
}