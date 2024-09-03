<?php

namespace Operations\Notification\Services;

use Operations\Notification\Contractor;
use Operations\Notification\DTO\NotificationDataDTO;
use Operations\Notification\DTO\TemplateDataDTO;
use Operations\Notification\NotificationEvents;
use function Operations\Notification\getEmailsByPermit;
use function Operations\Notification\getResellerEmailFrom;

class NotificationSendingService
{
    private NotificationFactory $notificationFactory;

    public function __construct(NotificationFactory $notificationFactory)
    {
        $this->notificationFactory = $notificationFactory;
    }

    public function sendEmployeeNotifications(int $resellerId, TemplateDataDTO $templateData): bool
    {
        $emailFrom = getResellerEmailFrom($resellerId);
        $emails = getEmailsByPermit($resellerId, 'tsGoodsReturn');

        if (empty($emailFrom) || empty($emails)) {
            return false;
        }

        $emailSender = $this->notificationFactory->createSender('email');
        foreach ($emails as $email) {
            $emailSender->send(new NotificationDataDTO(
                from: $emailFrom,
                to: $email,
                subject: __('complaintEmployeeEmailSubject', (array)$templateData, $resellerId),
                message: __('complaintEmployeeEmailBody', (array)$templateData, $resellerId),
                resellerId: $resellerId,
                event: NotificationEvents::CHANGE_RETURN_STATUS
            ));
        }

        return true;
    }

    public function sendClientNotifications(int $resellerId, Contractor $client, TemplateDataDTO $templateData, int $newStatus): array
    {
        $result = [
            'email' => false,
            'sms' => [
                'isSent' => false,
                'message' => '',
            ],
        ];

        $emailFrom = getResellerEmailFrom($resellerId);

        if (!empty($emailFrom) && !empty($client->email)) {
            $emailSender = $this->notificationFactory->createSender('email');
            $emailSender->send(new NotificationDataDTO(
                from: $emailFrom,
                to: $client->email,
                subject: __('complaintClientEmailSubject', (array)$templateData, $resellerId),
                message: __('complaintClientEmailBody', (array)$templateData, $resellerId),
                resellerId: $resellerId,
                clientId: $client->id,
                event: NotificationEvents::CHANGE_RETURN_STATUS,
                subEvent: $newStatus
            ));
            $result['email'] = true;
        }

        if (!empty($client->mobile)) {
            $smsSender = $this->notificationFactory->createSender('sms');
            $errorText = '';
            $smsResult = $smsSender->send(new NotificationDataDTO(
                resellerId: $resellerId,
                clientId: $client->id,
                event: NotificationEvents::CHANGE_RETURN_STATUS,
                subEvent: $newStatus,
                templateData: (array)$templateData,
                errorText: $errorText
            ));
            $result['sms']['isSent'] = $smsResult;
            $result['sms']['message'] = $errorText;
        }

        return $result;
    }
}