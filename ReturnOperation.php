<?php

namespace Operations\Notification;

use Exception;

/**
 * NotificationSender interface
 * TODO: ideally all of this should be in a separate file, but for the sake of simplicity, I'm keeping it here.
 *
 * Separation of Concerns & Single Responsibility Principle
 * Purpose: Defines the contract for sending notifications.
 */
interface NotificationSender
{
    public function send(array $data): bool;
}

class EmailNotificationSender implements NotificationSender
{
    public function send(array $data): bool
    {
        return MessagesClient::sendMessage([
            0 => [ // Assuming 0 is MessageTypes::EMAIL
                'emailFrom' => $data['from'],
                'emailTo' => $data['to'],
                'subject' => $data['subject'],
                'message' => $data['message'],
            ],
        ], $data['resellerId'], $data['clientId'] ?? 0, $data['event'], $data['subEvent'] ?? '');
    }
}

class SmsNotificationSender implements NotificationSender
{
    public function send(array $data): bool
    {
        return NotificationManager::send(
            $data['resellerId'],
            $data['clientId'],
            $data['event'],
            $data['subEvent'] ?? '',
            $data['templateData'],
            $data['errorText']
        );
    }
}

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

/**
 * TemplateDataBuilder class
 *
 * Pattern: Builder
 * Purpose: Used to build the template data required for sending notifications.
 * This makes the process of building complex data structures more readable and less error-prone.
 */
class TemplateDataBuilder
{
    private array $data = [];

    public function setComplaint(int $id, string $number): self
    {
        $this->data['COMPLAINT_ID'] = $id;
        $this->data['COMPLAINT_NUMBER'] = $number;
        return $this;
    }

    public function setCreator(Employee $creator): self
    {
        $this->data['CREATOR_ID'] = $creator->id;
        $this->data['CREATOR_NAME'] = $creator->getFullName();
        return $this;
    }

    public function setExpert(Employee $expert): self
    {
        $this->data['EXPERT_ID'] = $expert->id;
        $this->data['EXPERT_NAME'] = $expert->getFullName();
        return $this;
    }

    public function setClient(Contractor $client): self
    {
        $this->data['CLIENT_ID'] = $client->id;
        $this->data['CLIENT_NAME'] = $client->getFullName() ?: $client->name;
        return $this;
    }

    public function setConsumption(int $id, string $number): self
    {
        $this->data['CONSUMPTION_ID'] = $id;
        $this->data['CONSUMPTION_NUMBER'] = $number;
        return $this;
    }

    public function setAgreement(string $number): self
    {
        $this->data['AGREEMENT_NUMBER'] = $number;
        return $this;
    }

    public function setDate(string $date): self
    {
        $this->data['DATE'] = $date;
        return $this;
    }

    public function setDifferences(string $differences): self
    {
        $this->data['DIFFERENCES'] = $differences;
        return $this;
    }

    public function build(): array
    {
        foreach ($this->data as $key => $value) {
            if (empty($value)) {
                throw new Exception("Template Data ({$key}) is empty!", 500);
            }
        }
        return $this->data;
    }
}

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

/**
 * ReturnOperation class
 *
 * Purpose: Handles the process of sending notifications for changes in return status
 * to both employees and clients.
 *
 * TODO: Possibly split this class into smaller classes for better readability and maintainability.
 * For example: DataProvider and EntityRetriever classes to handle data retrieval.
 */
class TsReturnOperation extends ReferencesOperation
{
    private const TYPE_NEW = 1;
    private const TYPE_CHANGE = 2;

    private NotificationFactory $notificationFactory;

    public function __construct(NotificationFactory $notificationFactory)
    {
        $this->notificationFactory = $notificationFactory;
    }

    public function doOperation(): array
    {
        try {
            $data = $this->validateAndeGetData();
            $reseller = $this->getReseller($data['resellerId']);
            $client = $this->getClient($data['clientId'], $reseller->id);
            $creator = $this->getEmployee($data['creatorId'], 'Creator');
            $expert = $this->getEmployee($data['expertId'], 'Expert');

            $templateData = $this->buildTemplateData($data, $client, $creator, $expert);
            $result = $this->initializeResult();

            $this->sendEmployeeNotifications($reseller->id, $templateData, $result);

            if ($data['notificationType'] === self::TYPE_CHANGE && !empty($data['differences']['to'])) {
                $this->sendClientNotifications($reseller->id, $client, $templateData, $data['differences']['to'], $result);
            }

            return $result;
        } catch (Exception $e) {
            error_log("Error in ReturnOperation: " . $e->getMessage());
            throw $e;
        }
    }

    private function validateAndGetData(): array
    {
        $data = $this->getRequest('data');
        if (!is_array($data)) {
            throw new Exception('Invalid data format', 400);
        }

        $requiredFields = ['resellerId', 'notificationType', 'clientId', 'creatorId', 'expertId', 'complaintId', 'complaintNumber', 'consumptionId', 'consumptionNumber', 'agreementNumber', 'date'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Empty {$field}", 400);
            }
        }

        $data['resellerId'] = (int)$data['resellerId'];
        $data['notificationType'] = (int)$data['notificationType'];

        return $data;
    }

    private function getReseller(int $resellerId): Seller
    {
        $reseller = Seller::getById($resellerId);
        if ($reseller === null) {
            throw new Exception('Seller not found!', 400);
        }
        return $reseller;
    }

    private function getClient(int $clientId, int $resellerId): Contractor
    {
        $client = Contractor::getById($clientId);
        if ($client === null || $client->type !== Contractor::TYPE_CUSTOMER || $client->Seller->id !== $resellerId) {
            throw new Exception('Client not found!', 400);
        }
        return $client;
    }

    private function getEmployee(int $employeeId, string $role): Employee
    {
        $employee = Employee::getById($employeeId);
        if ($employee === null) {
            throw new Exception("{$role} not found!", 400);
        }
        return $employee;
    }

    private function buildTemplateData(array $data, Contractor $client, Employee $creator, Employee $expert): array
    {
        $builder = new TemplateDataBuilder();
        return $builder
            ->setComplaint((int)$data['complaintId'], (string)$data['complaintNumber'])
            ->setCreator($creator)
            ->setExpert($expert)
            ->setClient($client)
            ->setConsumption((int)$data['consumptionId'], (string)$data['consumptionNumber'])
            ->setAgreement((string)$data['agreementNumber'])
            ->setDate((string)$data['date'])
            ->setDifferences($this->getDifferences($data))
            ->build();
    }

    private function getDifferences(array $data): string
    {
        if ($data['notificationType'] === NotificationType::NEW->value) {
            return __('NewPositionAdded', null, $data['resellerId']);
        }

        if ($data['notificationType'] === NotificationType::CHANGE->value && !empty($data['differences'])) {
            return __('PositionStatusHasChanged', [
                'FROM' => Status::getName((int)$data['differences']['from']),
                'TO' => Status::getName((int)$data['differences']['to']),
            ], $data['resellerId']);
        }

        return '';
    }

    private function initializeResult(): array
    {
        return [
            'notificationEmployeeByEmail' => false,
            'notificationClientByEmail' => false,
            'notificationClientBySms' => [
                'isSent' => false,
                'message' => '',
            ],
        ];
    }

    private function sendEmployeeNotifications(int $resellerId, array $templateData, array &$result): void
    {
        $emailFrom = getResellerEmailFrom($resellerId);
        $emails = getEmailsByPermit($resellerId, 'tsGoodsReturn');

        if (!empty($emailFrom) && !empty($emails)) {
            $emailSender = $this->notificationFactory->createSender('email');
            foreach ($emails as $email) {
                $emailSender->send([
                    'from' => $emailFrom,
                    'to' => $email,
                    'subject' => __('complaintEmployeeEmailSubject', $templateData, $resellerId),
                    'message' => __('complaintEmployeeEmailBody', $templateData, $resellerId),
                    'resellerId' => $resellerId,
                    'event' => NotificationEvents::CHANGE_RETURN_STATUS,
                ]);
            }
            $result['notificationEmployeeByEmail'] = true;
        }
    }

    private function sendClientNotifications(int $resellerId, Contractor $client, array $templateData, int $newStatus, array &$result): void
    {
        $emailFrom = getResellerEmailFrom($resellerId);

        if (!empty($emailFrom) && !empty($client->email)) {
            $emailSender = $this->notificationFactory->createSender('email');
            $emailSender->send([
                'from' => $emailFrom,
                'to' => $client->email,
                'subject' => __('complaintClientEmailSubject', $templateData, $resellerId),
                'message' => __('complaintClientEmailBody', $templateData, $resellerId),
                'resellerId' => $resellerId,
                'clientId' => $client->id,
                'event' => NotificationEvents::CHANGE_RETURN_STATUS,
                'subEvent' => $newStatus,
            ]);
            $result['notificationClientByEmail'] = true;
        }

        if (!empty($client->mobile)) {
            $smsSender = $this->notificationFactory->createSender('sms');
            $errorText = '';
            $smsResult = $smsSender->send([
                'resellerId' => $resellerId,
                'clientId' => $client->id,
                'event' => NotificationEvents::CHANGE_RETURN_STATUS,
                'subEvent' => $newStatus,
                'templateData' => $templateData,
                'errorText' => &$errorText,
            ]);
            $result['notificationClientBySms']['isSent'] = $smsResult;
            if (!empty($errorText)) {
                $result['notificationClientBySms']['message'] = $errorText;
            }
        }
    }
}