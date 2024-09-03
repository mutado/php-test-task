<?php

namespace Operations\Notification\DTO;

class NotificationData
{
    public function __construct(
        public ?string $from = null,
        public ?string $to = null,
        public ?string $subject = null,
        public ?string $message = null,
        public ?int $resellerId = null,
        public ?int $clientId = null,
        public ?string $event = null,
        public ?string $subEvent = null,
        public ?array $templateData = null,
        public ?string &$errorText = null
    ) {}
}