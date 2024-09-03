<?php

namespace Operations\Notification;

use Exception;
use Operations\Notification\Builders\TemplateDataBuilder;
use Operations\Notification\DTO\TemplateDataDTO;
use Operations\Notification\Enums\NotificationType;
use Operations\Notification\Services\NotificationFactory;
use Operations\Notification\Services\NotificationSendingService;
use Operations\Notification\Validators\ReturnOperationValidator;

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
    private ReturnOperationValidator $validator;
    private NotificationSendingService $notificationService;

    public function __construct(
        ReturnOperationValidator   $validator,
        NotificationSendingService $notificationService
    )
    {
        $this->validator = $validator;
        $this->notificationService = $notificationService;
    }

    public function doOperation(): array
    {
        try {
            $validationResult = $this->validator->validateData($this->getRequest('data'));
            if (!$validationResult->isValid) {
                throw new Exception(implode(', ', $validationResult->errors), 400);
            }

            $data = $validationResult->validatedData;
            $reseller = $this->getReseller($data['resellerId']);
            $client = $this->getClient($data['clientId'], $reseller->id);
            $creator = $this->getEmployee($data['creatorId'], 'Creator');
            $expert = $this->getEmployee($data['expertId'], 'Expert');

            $templateData = $this->buildTemplateData($data, $client, $creator, $expert);
            $result = $this->initializeResult();

            $result['notificationEmployeeByEmail'] = $this->notificationService->sendEmployeeNotifications($reseller->id, $templateData);

            if ($data['notificationType'] === NotificationType::CHANGE->value && !empty($data['differences']['to'])) {
                $clientNotifications = $this->notificationService->sendClientNotifications($reseller->id, $client, $templateData, $data['differences']['to']);
                $result['notificationClientByEmail'] = $clientNotifications['email'];
                $result['notificationClientBySms'] = $clientNotifications['sms'];
            }

            return $result;
        } catch (Exception $e) {
            error_log("Error in ReturnOperation: " . $e->getMessage());
            throw $e;
        }
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

    private function buildTemplateData(array $data, Contractor $client, Employee $creator, Employee $expert): TemplateDataDTO
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
}